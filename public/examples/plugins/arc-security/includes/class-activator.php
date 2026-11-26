<?php
/**
 * Activation: audit table, default settings, daily purge cron, pingbacks off.
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Activator {

	public static function activate() {
		self::create_tables();

		if ( false === get_option( 'arc_sec_settings' ) ) {
			add_option( 'arc_sec_settings', ARC_SEC_Plugin::defaults() );
		}
		add_option( ARC_SEC_Upgrade::OPTION, ARC_SEC_Upgrade::VERSION );
		if ( ! wp_next_scheduled( 'arc_sec_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'arc_sec_daily' );
		}
		// SEC-0751 / SEC-0783: pingbacks and trackbacks off.
		update_option( 'default_pingback_flag', 0 );
		update_option( 'default_ping_status', 0 );

		ARC_SEC_Audit::log( 'plugin_activated', 'info' );
	}

	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table            = $wpdb->prefix . ARC_SEC_AUDIT_TABLE;
		$charset_collate  = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				event varchar(60) NOT NULL,
				severity varchar(10) NOT NULL DEFAULT 'info',
				ip_hash char(64) NOT NULL DEFAULT '',
				user_login varchar(60) NOT NULL DEFAULT '',
				context text NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY event (event),
				KEY created_at (created_at)
			) {$charset_collate};"
		);
	}
}
