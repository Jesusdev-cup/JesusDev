<?php
/**
 * Security headers (SEC-0562, 0566-0570, 0577, 0598, 0600).
 *
 * Emitted on the frontend (send_headers), on wp-login.php (login_init) and
 * on REST responses (rest_post_dispatch) — the three surfaces send_headers
 * alone does not cover. All values are fixed strings or sanitized settings,
 * never raw input (SEC-0600).
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Headers {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'send_headers', array( $this, 'send' ) );
		add_action( 'login_init', array( $this, 'send' ) );
		add_filter( 'rest_post_dispatch', array( $this, 'rest_headers' ) );
		// SEC-0577/0598/0834: PHP version out of X-Powered-By.
		@ini_set( 'expose_php', '0' );
	}

	public function send() {
		if ( headers_sent() ) {
			return;
		}
		// SEC-0577/0598: expose_php=0 alone is not enough — the header is
		// already queued when plugins run, so it must be removed outright.
		header_remove( 'X-Powered-By' );
		if ( ! ARC_SEC_Plugin::get( 'security_headers' ) ) {
			return;
		}
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()' );
		if ( is_ssl() ) {
			// SEC-0562: HSTS only exists over HTTPS; >= 6 months.
			header( 'Strict-Transport-Security: max-age=15552000; includeSubDomains' );
		}
		if ( ARC_SEC_Plugin::get( 'csp_enabled' ) ) {
			$policy = preg_replace( '/[\r\n]+/', ' ', (string) ARC_SEC_Plugin::get( 'csp_policy' ) );
			if ( '' !== trim( $policy ) ) {
				header( 'Content-Security-Policy: ' . trim( $policy ) );
			}
		}
	}

	/**
	 * REST responses never hit send_headers — attach the same base headers
	 * to the response object (SEC-0574/0571 family).
	 *
	 * @param WP_REST_Response $response Response object.
	 * @return WP_REST_Response
	 */
	public function rest_headers( $response ) {
		if ( ARC_SEC_Plugin::get( 'security_headers' ) && is_object( $response ) && method_exists( $response, 'header' ) ) {
			$response->header( 'X-Content-Type-Options', 'nosniff' );
			$response->header( 'X-Frame-Options', 'SAMEORIGIN' );
		}
		return $response;
	}
}
