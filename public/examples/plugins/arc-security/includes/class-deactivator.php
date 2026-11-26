<?php
/**
 * Deactivation: scheduled purge removed. Data is kept (uninstall.php wipes it).
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Deactivator {

	public static function deactivate() {
		wp_clear_scheduled_hook( 'arc_sec_daily' );
	}
}
