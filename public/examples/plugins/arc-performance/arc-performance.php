<?php
/**
 * Plugin Name: ARC Performance
 * Description: Anonymous page cache, image lazy-loading, safe HTML minification and asset bloat removal for the ARC site. Never caches logged-in traffic, wp-admin/REST/login, portal pages or any ?rp= token URL, and honors Cache-Control: no-store.
 * Version: 0.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: ARC
 * Text Domain: arc-performance
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'ARC_PERF_VERSION', '0.2.0' );
define( 'ARC_PERF_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARC_PERF_URL', plugin_dir_url( __FILE__ ) );
define( 'ARC_PERF_CACHE_DIR', WP_CONTENT_DIR . '/cache/arc-perf/' );

/**
 * ARC_PERF_Foo_Bar -> includes/class-foo-bar.php
 */
spl_autoload_register(
	function ( $class ) {
		if ( 0 !== strpos( $class, 'ARC_PERF_' ) ) {
			return;
		}
		$path = ARC_PERF_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', substr( $class, 10 ) ) ) . '.php';
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, array( 'ARC_PERF_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ARC_PERF_Deactivator', 'deactivate' ) );

function arc_perf_boot() {
	// Schema stamp first — a pending migration runs before any module reads
	// the data it owns.
	ARC_PERF_Upgrade::maybe_upgrade();
	ARC_PERF_Plugin::instance();
	ARC_PERF_Cache::instance();
	ARC_PERF_Optimize::instance();
	ARC_PERF_Database::instance();
	ARC_PERF_Rest::register();
	if ( is_admin() ) {
		ARC_PERF_Admin::instance();
	}
}
add_action( 'plugins_loaded', 'arc_perf_boot', 1 );

function arc_perf_load_textdomain() {
	load_plugin_textdomain( 'arc-performance', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'arc_perf_load_textdomain' );
