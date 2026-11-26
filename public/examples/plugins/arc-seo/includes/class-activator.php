<?php
/**
 * Activation: seed default settings. No tables or cron needed —
 * deactivation is a no-op because nothing persists outside the option.
 *
 * @package arc-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEO_Activator {

	public static function activate() {
		if ( false === get_option( 'arc_seo_settings' ) ) {
			add_option( 'arc_seo_settings', ARC_SEO_Plugin::defaults() );
		}
		add_option( ARC_SEO_Upgrade::OPTION, ARC_SEO_Upgrade::VERSION );
	}
}
