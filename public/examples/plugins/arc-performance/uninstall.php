<?php
/**
 * Full cleanup: settings, schema stamp, activity log, cron health and
 * every cached page file.
 *
 * @package arc-performance
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'arc_perf_settings' );
delete_option( 'arc_perf_schema' );
delete_option( 'arc_perf_activity' );
delete_option( 'arc_perf_db_last_run' );

// Transients created by the REST idempotency layer.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_arc_perf_%' OR option_name LIKE '_transient_timeout_arc_perf_%'" );

$dir = WP_CONTENT_DIR . '/cache/arc-perf/';
if ( is_dir( $dir ) ) {
	foreach ( glob( $dir . '*.html' ) as $file ) {
		@unlink( $file );
	}
	@rmdir( $dir );
}
