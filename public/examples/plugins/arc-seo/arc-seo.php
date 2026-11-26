<?php
/**
 * Plugin Name: ARC SEO
 * Description: Meta descriptions, Open Graph/Twitter cards, JSON-LD schema and per-page SEO fields for the public ARC pages. The partner portal and branded login are always noindex.
 * Version: 0.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: ARC
 * Text Domain: arc-seo
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'ARC_SEO_VERSION', '0.2.0' );
define( 'ARC_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARC_SEO_URL', plugin_dir_url( __FILE__ ) );

/**
 * ARC_SEO_Foo_Bar -> includes/class-foo-bar.php
 */
spl_autoload_register(
	function ( $class ) {
		if ( 0 !== strpos( $class, 'ARC_SEO_' ) ) {
			return;
		}
		$path = ARC_SEO_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', substr( $class, 9 ) ) ) . '.php';
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, array( 'ARC_SEO_Activator', 'activate' ) );

function arc_seo_boot() {
	// Schema stamp first — a pending migration runs before any module reads
	// the data it owns.
	ARC_SEO_Upgrade::maybe_upgrade();
	ARC_SEO_Plugin::instance();
	ARC_SEO_Meta::instance();
	ARC_SEO_Schema::instance();
	ARC_SEO_Rest::register();
	if ( is_admin() ) {
		// Settings are read on every front-end request — flip legacy
		// autoload='off' once here so they join the autoload bundle.
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( 'arc_seo_settings', 'on' );
		}
		ARC_SEO_Admin::instance();
		ARC_SEO_Metabox::instance();
	}
}
add_action( 'plugins_loaded', 'arc_seo_boot' );

function arc_seo_load_textdomain() {
	load_plugin_textdomain( 'arc-seo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'arc_seo_load_textdomain' );
