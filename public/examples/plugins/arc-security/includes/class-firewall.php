<?php
/**
 * Request firewall (SEC-0063, 0064, 0747, 0762).
 *
 * Cheap request sanity checks that run before WordPress does real work:
 * array-typed or oversized credential fields, and null bytes. Deliberately
 * narrow — this is not a WAF, it rejects malformed login input early so it
 * can never reach auth or plugin handlers (see SEC-0680/H7 for the same
 * class of bug inside the portal).
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Firewall {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( ARC_SEC_Plugin::get( 'firewall' ) ) {
			add_action( 'init', array( $this, 'inspect' ), 0 );
		}
	}

	public function inspect() {
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
			return;
		}
		$max = max( 1024, (int) ARC_SEC_Plugin::get( 'max_input_len' ) );

		foreach ( array( 'log', 'pwd' ) as $field ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- inspecting, not processing.
			if ( ! isset( $_POST[ $field ] ) ) {
				continue;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$value = $_POST[ $field ];
			$bad   = is_array( $value )
				|| strlen( (string) $value ) > $max
				|| false !== strpos( (string) $value, "\0" );
			if ( $bad ) {
				ARC_SEC_Audit::log( 'request_denied', 'warning', array( 'field' => $field ) );
				wp_die( 'Invalid request.', 'Bad Request', array( 'response' => 400 ) );
			}
		}
	}
}
