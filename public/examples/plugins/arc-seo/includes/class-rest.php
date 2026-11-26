<?php
/**
 * REST contract: the plugin's public API surface. The admin screen uses
 * admin-post.php + nonces; external consumers use these routes — one
 * contract, two entry points, same domain functions.
 *
 * Mutations honor X-Request-Id: a retry with the same id replays the
 * stored result instead of running twice (idempotent writes).
 *
 * @package arc-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEO_Rest {

	const NS = 'arc-seo/v1';

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
			'/recommended',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'recommended' ),
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
		return new WP_REST_Response( ARC_SEO_Diagnostics::snapshot(), 200 );
	}

	/**
	 * Idempotency: the same X-Request-Id within 24h replays the stored
	 * result — retries and double submits never run a mutation twice.
	 *
	 * @param WP_REST_Request $req Incoming request.
	 * @return WP_REST_Response
	 */
	public static function recommended( WP_REST_Request $req ) {
		$rid = trim( (string) $req->get_header( 'X-Request-Id' ) );
		$key = 'arc_seo_req_' . md5( $rid );
		if ( '' !== $rid ) {
			$hit = get_transient( $key );
			if ( false !== $hit ) {
				return new WP_REST_Response( array( 'deduped' => true, 'result' => $hit ), 200 );
			}
		}
		ARC_SEO_Plugin::save_settings( ARC_SEO_Plugin::defaults() );
		ARC_SEO_Audit::log( 'recommended_applied', array( 'via' => 'rest' ) );
		$result = array( 'applied' => true );
		if ( '' !== $rid ) {
			set_transient( $key, $result, DAY_IN_SECONDS );
		}
		return new WP_REST_Response( array( 'deduped' => false, 'result' => $result ), 200 );
	}
}
