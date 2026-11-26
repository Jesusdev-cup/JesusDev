<?php
/**
 * Activity log: the last N admin-facing events in a bounded option.
 * An option ring buffer is the right-sized store here — the plugin only
 * needs "who changed the SEO settings, and when".
 *
 * @package arc-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEO_Audit {

	const OPTION = 'arc_seo_activity';
	const LIMIT  = 50;

	/**
	 * Append an event. Silent by design — logging must never break a request.
	 *
	 * @param string $event   Event key (settings_saved, meta_updated...).
	 * @param array  $context Small context map, capped at 500 chars.
	 */
	public static function log( $event, $context = array() ) {
		$events   = (array) get_option( self::OPTION, array() );
		$events[] = array(
			'ts'      => time(),
			'event'   => substr( sanitize_key( $event ), 0, 60 ),
			'user'    => get_current_user_id(),
			'context' => substr( (string) wp_json_encode( array_map( 'strval', $context ) ), 0, 500 ),
		);
		// autoload=false: read on the admin screen only — never inflates the
		// autoload bundle that every front-end request pays for.
		update_option( self::OPTION, array_slice( $events, -self::LIMIT ), false );
	}

	/**
	 * Most recent events first.
	 *
	 * @return array<int,array{ts:int,event:string,user:int,context:string}>
	 */
	public static function recent( $limit = 10 ) {
		return array_reverse( array_slice( (array) get_option( self::OPTION, array() ), -max( 1, (int) $limit ) ) );
	}
}
