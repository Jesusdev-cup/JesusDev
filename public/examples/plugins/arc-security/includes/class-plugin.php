<?php
/**
 * Orchestrator + settings store.
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings live in a single autoloaded option, merged with defaults.
 */
class ARC_SEC_Plugin {

	private static $instance = null;
	private $settings         = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function defaults() {
		return array(
			// Login & auth (SEC-0002, 0034, 0036, 0065, 0794, 0796).
			'login_lockout'         => 1,
			'lockout_threshold'     => 5,
			'lockout_minutes'       => 15,
			'generic_login_errors'  => 1,
			'strong_passwords'      => 1,
			// Site policy: only the listed accounts may enter wp-admin
			// (admin-post.php and admin-ajax.php are exempt — the portal's
			// partner actions live there).
			'wpadmin_lockdown'      => 1,
			'wpadmin_users'         => 'admin',
			// wp-login.php never shows its own form — /partner-login/ is the
			// only login surface (POST and action flows still work there).
			'branded_login_only'    => 1,
			// Core hardening (SEC-0037-0039, 0041-0042, 0750, 0775, 0782-0788, 0797).
			'disable_xmlrpc'        => 1,
			'block_author_scan'     => 1,
			'rest_users_protect'    => 1,
			'hide_version'          => 1,
			'disable_file_editor'   => 1,
			'disable_app_passwords' => 1,
			'disable_pingbacks'     => 1,
			'exclude_portal_sitemap' => 1,
			// Headers & transport (SEC-0562, 0566-0570, 0577, 0600).
			'security_headers'      => 1,
			'csp_enabled'           => 0,
			'csp_policy'            => "default-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'",
			// Request firewall (SEC-0063, 0064, 0747, 0762).
			'firewall'              => 1,
			'max_input_len'         => 8192,
			'login_post_limit'      => 30,
			// Audit (SEC-0866-0895).
			'audit_enabled'         => 1,
			'audit_retention_days'  => 90,
			// Comma-separated IPs allowed to send X-Forwarded-For (SEC-0370, 0864).
			'trusted_proxies'       => '',
		);
	}

	public function settings() {
		if ( null === $this->settings ) {
			$this->settings = wp_parse_args( (array) get_option( 'arc_sec_settings', array() ), self::defaults() );
		}
		return $this->settings;
	}

	public static function get( $key ) {
		$settings = self::instance()->settings();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : null;
	}

	public static function save_settings( $settings ) {
		$clean          = wp_parse_args( (array) $settings, self::defaults() );
		self::instance()->settings = $clean;
		// autoload = true: read on every request — folding it into the
		// autoload bundle removes a dedicated SELECT per page load.
		update_option( 'arc_sec_settings', $clean, true );
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( 'arc_sec_settings', 'on' );
		}
	}

	/**
	 * Client IP for rate limits and lockouts. REMOTE_ADDR by default;
	 * X-Forwarded-For is only honored when the immediate peer is in the
	 * trusted_proxies list (SEC-0370 / catalog finding H8).
	 */
	public static function client_ip() {
		$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( '' === $remote ) {
			return 'unknown';
		}
		$trusted = array_filter( array_map( 'trim', explode( ',', (string) self::get( 'trusted_proxies' ) ) ) );
		if ( $trusted && in_array( $remote, $trusted, true ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded = explode( ',', (string) wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$client    = trim( (string) $forwarded[0] );
			if ( filter_var( $client, FILTER_VALIDATE_IP ) ) {
				return $client;
			}
		}
		return $remote;
	}
}
