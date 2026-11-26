<?php
/**
 * Activation: cache directory + default settings.
 *
 * @package arc-performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_PERF_Activator {

	public static function activate() {
		if ( ! is_dir( ARC_PERF_CACHE_DIR ) ) {
			wp_mkdir_p( ARC_PERF_CACHE_DIR );
		}
		if ( false === get_option( 'arc_perf_settings' ) ) {
			add_option( 'arc_perf_settings', ARC_PERF_Plugin::defaults() );
		}
		add_option( ARC_PERF_Upgrade::OPTION, ARC_PERF_Upgrade::VERSION );
		ARC_PERF_Database::schedule();
	}
}
