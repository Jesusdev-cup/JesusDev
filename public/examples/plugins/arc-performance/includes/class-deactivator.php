<?php
/**
 * Deactivation: drop all cached pages.
 *
 * @package arc-performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_PERF_Deactivator {

	public static function deactivate() {
		ARC_PERF_Cache::purge_all();
		ARC_PERF_Database::unschedule();
	}
}
