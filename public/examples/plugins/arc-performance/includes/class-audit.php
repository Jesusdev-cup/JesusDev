<?php
/**
 * Activity log: the last N admin-facing events in a bounded option.
 * arc-security keeps its forensic trail in a table — performance only
 * needs "who purged/optimized/saved what, and when", so an option ring
 * buffer is the right-sized store.
 *
 * @package arc-performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_PERF_Audit {

	const OPTION = 'arc_perf_activity';
	const LIMIT  = 50;

	/**
	 * Append an event. Silent by design — logging must never break a request.
	 *
	 * @param string $event   Event key (settings_saved, cache_purged...).
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
		// autoload=false: the log is read on the admin screen only — keeping
		// it out of the autoload bundle costs nothing per front-end request.
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
