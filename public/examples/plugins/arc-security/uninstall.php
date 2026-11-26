<?php
/**
 * Full cleanup: settings, scan results, audit table, cron and transients.
 *
 * @package arc-security
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'arc_sec_settings' );
delete_option( 'arc_sec_scan' );
delete_option( 'arc_sec_schema' );
delete_option( 'arc_sec_last_purge' );
wp_clear_scheduled_hook( 'arc_sec_daily' );

// Transients created by the login lockout / rate limiter.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_arc_sec_%' OR option_name LIKE '_transient_timeout_arc_sec_%'" );

// Audit table.
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'arc_sec_audit' );
