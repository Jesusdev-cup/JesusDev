<?php
/**
 * REST contract: the plugin's public API surface. The admin screen uses
 * admin-post.php + nonces; external consumers (uptime monitors, a status
 * dashboard) use these routes — one contract, two entry points, same
 * domain functions.
 *
 * Mutations honor X-Request-Id: a retry with the same id replays the
 * stored result instead of running twice (idempotent writes). Read routes
 * never trigger the scanner — GET stays cheap.
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Rest {

	const NS = 'arc-sec/v1';

	public static function register() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route(
			self::NS,
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'status' ),
				'permission_callback' => array( __CLASS__, 'can' ),
			)
		);
		register_rest_route(
			self::NS,
			'/scan',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'scan' ),
				'permission_callback' => array( __CLASS__, 'can' ),
			)
		);
	}

	/**
	 * Single permission gate — the same one the admin screen uses.
	 */
	public static function can() {
		return current_user_can( 'manage_options' );
	}

	public static function status() {
		return new WP_REST_Response( ARC_SEC_Diagnostics::snapshot(), 200 );
	}

	/**
	 * Run the catalog scan and store the result — same operation as the
	 * Run security scan button. Idempotent via X-Request-Id.
	 *
	 * @param WP_REST_Request $req Incoming request.
	 * @return WP_REST_Response
	 */
	public static function scan( WP_REST_Request $req ) {
		$rid = trim( (string) $req->get_header( 'X-Request-Id' ) );
		$key = 'arc_sec_req_' . md5( $rid );
		if ( '' !== $rid ) {
			$hit = get_transient( $key );
			if ( false !== $hit ) {
				return new WP_REST_Response( array( 'deduped' => true, 'result' => $hit ), 200 );
			}
		}
		$result = array(
			'ran_at' => gmdate( 'Y-m-d H:i:s' ),
			'items'  => ARC_SEC_Scanner::run(),
		);
		update_option( 'arc_sec_scan', $result, false );
		ARC_SEC_Audit::log( 'scan_run', 'info', array( 'via' => 'rest' ) );
		if ( '' !== $rid ) {
			set_transient( $key, array( 'ran_at' => $result['ran_at'], 'items' => count( $result['items'] ) ), DAY_IN_SECONDS );
		}
		return new WP_REST_Response(
			array(
				'deduped' => false,
				'result'  => array( 'ran_at' => $result['ran_at'], 'items' => count( $result['items'] ) ),
			),
			200
		);
	}
}
