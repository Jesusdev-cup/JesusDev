<?php
/**
 * Cleanup: settings, schema stamp, activity log, REST transients and
 * per-post SEO meta.
 *
 * @package arc-seo
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'arc_seo_settings' );
delete_option( 'arc_seo_schema' );
delete_option( 'arc_seo_activity' );
delete_metadata( 'post', 0, '_arc_seo_title', '', true );
delete_metadata( 'post', 0, '_arc_seo_desc', '', true );

// Transients created by the REST idempotency layer.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_arc_seo_%' OR option_name LIKE '_transient_timeout_arc_seo_%'" );
