<?php
/**
 * REST contract: the plugin's public API surface. The admin screen uses
 * admin-post.php + nonces; external consumers (monitoring, WP-CLI over
 * REST, a future dashboard) use these routes — one contract, two entry
 * points, same domain functions.
 *
 * Mutations honor X-Request-Id: a retry with the same id replays the
 * stored result instead of running twice (idempotent writes).
 *
 * @package arc-performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_PERF_Rest {

	const NS = 'arc-perf/v1';

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
		foreach ( array( 'purge', 'optimize', 'recommended' ) as $action ) {
			register_rest_route(
				self::NS,
				'/' . $action,
				array(
					'methods'             => 'POST',
					'callback'            => function ( $req ) use ( $action ) {
						return self::run( $action, $req );
					},
					'permission_callback' => array( __CLASS__, 'can' ),
				)
			);
		}
	}

	/**
	 * Single permission gate — the same one the admin screen uses. If the
	 * rule ever changes, it changes here and in Admin, nowhere else.
	 */
	public static function can() {
		return current_user_can( 'manage_options' );
	}

	public static function status() {
		return new WP_REST_Response( ARC_PERF_Diagnostics::snapshot(), 200 );
	}

	/**
	 * Dispatch a mutation through the idempotency layer.
	 *
	 * @param string          $action Route tail (purge|optimize|recommended).
	 * @param WP_REST_Request $req    Incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	private static function run( $action, WP_REST_Request $req ) {
		return self::dedupe(
			$req,
			function () use ( $action ) {
				switch ( $action ) {
					case 'purge':
						return array( 'files_deleted' => ARC_PERF_Cache::purge_all() );
					case 'optimize':
						return ARC_PERF_Database::optimize();
					case 'recommended':
						ARC_PERF_Plugin::save_settings( ARC_PERF_Plugin::defaults() );
						ARC_PERF_Cache::purge_all();
						ARC_PERF_Audit::log( 'recommended_applied', array( 'via' => 'rest' ) );
						return array( 'applied' => true );
				}
				return new WP_Error( 'arc_perf_action', 'Unknown action.', array( 'status' => 404 ) );
			}
		);
	}

	/**
	 * Idempotency: the same X-Request-Id within 24h replays the stored
	 * result — retries and double submits never run a mutation twice.
	 *
	 * @param WP_REST_Request $req  Incoming request.
	 * @param callable        $work Mutation to run once per request id.
	 * @return WP_REST_Response|WP_Error
	 */
	private static function dedupe( WP_REST_Request $req, $work ) {
		$rid = trim( (string) $req->get_header( 'X-Request-Id' ) );
		$key = 'arc_perf_req_' . md5( $rid );
		if ( '' !== $rid ) {
			$hit = get_transient( $key );
			if ( false !== $hit ) {
				return new WP_REST_Response( array( 'deduped' => true, 'result' => $hit ), 200 );
			}
		}
		$result = $work();
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( '' !== $rid ) {
			set_transient( $key, $result, DAY_IN_SECONDS );
		}
		return new WP_REST_Response( array( 'deduped' => false, 'result' => $result ), 200 );
	}
}
