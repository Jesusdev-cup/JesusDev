<?php
/**
 * Schema versioning: the plugin stamps the version of its stored data and
 * migrates in ordered steps — new changes append a step, shipped steps
 * never change, so storage grows without rewrites.
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Upgrade {

	const VERSION = '1';
	const OPTION  = 'arc_sec_schema';

	/**
	 * Migration steps keyed by the version they produce. A step runs only
	 * when the stored version is older — the map is append-only.
	 *
	 * @return array<string,callable>
	 */
	public static function migrations() {
		return array(
			'1' => function () {
				// Baseline: settings in arc_sec_settings, the audit table
				// created on activation. dbDelta keeps it upgrade-safe —
				// rerunning create_tables() converges any drifted column.
				ARC_SEC_Activator::create_tables();
			},
		);
	}

	/**
	 * Runs on every request — the stamped option is autoloaded so the check
	 * costs nothing; a migration only fires when a release ships a newer
	 * VERSION.
	 */
	public static function maybe_upgrade() {
		$stored = (string) get_option( self::OPTION, '0' );
		if ( version_compare( $stored, self::VERSION, '>=' ) ) {
			return;
		}
		foreach ( self::migrations() as $to => $step ) {
			if ( version_compare( $stored, $to, '<' ) ) {
				$step();
			}
		}
		update_option( self::OPTION, self::VERSION, true );
	}
}
