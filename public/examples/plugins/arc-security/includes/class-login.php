<?php
/**
 * Login protection (SEC-0002, 0034, 0036, 0065, 0794, 0796, 0877, 0878).
 *
 * - Lockout per IP+username after N failures (fixed window via transients).
 * - Global POST rate limit on wp-login.php -> 429.
 * - Generic error message so user existence is not enumerable.
 * - Strong-password enforcement for elevated roles (administrator, arc_admin).
 * - Audit of successes, failures, lockouts and rate-limit hits.
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Login {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'authenticate', array( $this, 'maybe_block' ), 1, 3 );
		add_action( 'wp_login_failed', array( $this, 'on_fail' ) );
		add_action( 'wp_login', array( $this, 'on_success' ), 10, 2 );
		add_action( 'login_init', array( $this, 'force_branded_login' ), 0 );
		add_action( 'login_init', array( $this, 'rate_limit_login_posts' ) );

		if ( ARC_SEC_Plugin::get( 'generic_login_errors' ) ) {
			add_filter( 'login_errors', array( $this, 'generic_error' ) );
		}
		if ( ARC_SEC_Plugin::get( 'strong_passwords' ) ) {
			add_action( 'user_profile_update_errors', array( $this, 'enforce_strong_password' ), 10, 3 );
		}
	}

	private function attempts_key( $username ) {
		return 'arc_sec_fail_' . md5( ARC_SEC_Plugin::client_ip() . '|' . strtolower( (string) $username ) );
	}

	private function attempts( $username ) {
		$state = get_transient( $this->attempts_key( $username ) );
		return is_array( $state ) ? $state : array( 'fails' => 0, 'until' => 0 );
	}

	/**
	 * Short-circuit wp_authenticate when the IP+username pair is locked.
	 *
	 * @param mixed $user Previous auth result.
	 * @return mixed WP_User|WP_Error|null
	 */
	public function maybe_block( $user, $username = '' ) {
		if ( ! ARC_SEC_Plugin::get( 'login_lockout' ) || '' === trim( (string) $username ) ) {
			return $user;
		}
		$state = $this->attempts( $username );
		if ( $state['until'] > time() ) {
			ARC_SEC_Audit::log( 'login_lockout_deny', 'warning', array( 'user_login' => $username ) );
			return new WP_Error(
				'arc_sec_locked',
				sprintf(
					/* translators: %d: minutes until the lockout expires. */
					__( '<strong>Error:</strong> Too many failed login attempts. Try again in %d minutes.', 'arc-security' ),
					(int) ARC_SEC_Plugin::get( 'lockout_minutes' )
				)
			);
		}
		return $user;
	}

	/**
	 * SEC-0036/0065/0796 + SEC-0877: count failures, lock after threshold, audit.
	 */
	public function on_fail( $username ) {
		ARC_SEC_Audit::log( 'login_fail', 'warning', array( 'user_login' => $username ) );

		if ( ! ARC_SEC_Plugin::get( 'login_lockout' ) ) {
			return;
		}
		$threshold = max( 1, (int) ARC_SEC_Plugin::get( 'lockout_threshold' ) );
		$minutes   = max( 1, (int) ARC_SEC_Plugin::get( 'lockout_minutes' ) );
		$state     = $this->attempts( $username );
		$state['fails']++;

		if ( $state['fails'] >= $threshold ) {
			$state['until'] = time() + $minutes * MINUTE_IN_SECONDS;
			ARC_SEC_Audit::log(
				'login_lockout_start',
				'critical',
				array( 'user_login' => $username, 'minutes' => $minutes )
			);
		}
		set_transient( $this->attempts_key( $username ), $state, $minutes * MINUTE_IN_SECONDS );
	}

	/**
	 * SEC-0878: successful logins are audited; the failure counter resets.
	 */
	public function on_success( $user_login, $user ) {
		delete_transient( $this->attempts_key( $user_login ) );
		ARC_SEC_Audit::log( 'login_success', 'info', array( 'user_login' => $user_login ) );
	}

	/**
	 * Global POST rate limit on wp-login.php (SEC-0747/0750 family):
	 * login_post_limit posts per 5 minutes per IP -> 429 + Retry-After.
	 */
	public function rate_limit_login_posts() {
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
			return;
		}
		$limit = max( 5, (int) ARC_SEC_Plugin::get( 'login_post_limit' ) );
		$key   = 'arc_sec_lpost_' . md5( ARC_SEC_Plugin::client_ip() );
		$hits  = (int) get_transient( $key ) + 1;
		set_transient( $key, $hits, 5 * MINUTE_IN_SECONDS );

		if ( $hits > $limit ) {
			ARC_SEC_Audit::log( 'rate_limited', 'warning', array( 'route' => 'wp-login.php' ) );
			status_header( 429 );
			header( 'Retry-After: 300' );
			wp_die( 'Too many requests.', '429', array( 'response' => 429 ) );
		}
	}

	/**
	 * wp-login.php never renders its own form: GET visitors are bounced to
	 * the branded partner-login page (redirect_to preserved). POST stays
	 * open — the branded form submits to wp-login.php — and core action
	 * flows (logout, password reset, postpass, email confirm) keep working.
	 */
	public function force_branded_login() {
		if ( ! ARC_SEC_Plugin::get( 'branded_login_only' ) ) {
			return;
		}
		if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- flow control only.
		$action  = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		$allowed = array( 'logout', 'lostpassword', 'retrievepassword', 'rp', 'resetpass', 'postpass', 'confirm_admin_email', 'confirmaction', 'register' );
		if ( $action && in_array( $action, $allowed, true ) ) {
			return;
		}
		$target = home_url( '/' );
		$page   = (int) get_option( 'arc_rpp_login_page_id' );
		if ( $page ) {
			$permalink = get_permalink( $page );
			if ( $permalink ) {
				$target = $permalink;
			}
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['redirect_to'] ) ) {
			$redirect = wp_validate_redirect( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ), '' );
			if ( $redirect ) {
				$target = add_query_arg( 'redirect_to', urlencode( $redirect ), $target );
			}
		}
		wp_safe_redirect( $target, 302 );
		exit;
	}

	/**
	 * SEC-0002: one message for every failure so "bad user" vs "bad password"
	 * is not enumerable from the outside.
	 */
	public function generic_error() {
		return __( '<strong>Error:</strong> Invalid username or password.', 'arc-security' );
	}

	/**
	 * SEC-0034/0794: elevated roles must set passwords of >= 12 chars with
	 * mixed case + digits. Applies to profile updates and user creation.
	 *
	 * @param WP_Error $errors Error bag to append to.
	 * @param bool     $update Whether this is an update.
	 * @param WP_User  $user   Target user.
	 */
	public function enforce_strong_password( $errors, $update, $user ) {
		if ( ! isset( $_POST['pass1'] ) || ! is_string( $_POST['pass1'] ) || '' === $_POST['pass1'] ) {
			return;
		}
		$roles    = isset( $user->roles ) && is_array( $user->roles ) ? $user->roles : array();
		$elevated = array_intersect( $roles, array( 'administrator', 'arc_admin' ) );
		if ( ! $elevated ) {
			return;
		}
		$pass = (string) wp_unslash( $_POST['pass1'] );
		$weak = strlen( $pass ) < 12
			|| ! preg_match( '/[a-z]/', $pass )
			|| ! preg_match( '/[A-Z]/', $pass )
			|| ! preg_match( '/\d/', $pass );
		if ( $weak ) {
			$errors->add(
				'arc_sec_weak_password',
				__( 'Accounts with elevated roles require a password of at least 12 characters including uppercase, lowercase and a number.', 'arc-security' )
			);
		}
	}
}
