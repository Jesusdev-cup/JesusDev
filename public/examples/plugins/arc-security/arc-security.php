<?php
/**
 * Plugin Name: ARC Security
 * Description: Site-level hardening, login lockout, central audit log and an automated security scanner mapped to the ARC security test catalog (test.txt, SEC-0001 to SEC-1000). Protects arc-referral-portal, arc-templates and the site itself.
 * Version: 0.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: ARC
 * Text Domain: arc-security
 * Domain Path: /languages
 *
 * Scope note: this plugin covers the SITE-LEVEL tests of the catalog
 * (login brute force, XML-RPC, user enumeration, headers, cookies,
 * core hardening, audit, DoS surface). Tests that assert the internal
 * behavior of arc-referral-portal (tokens, RBAC, nonces, escaping,
 * idempotency) remain the responsibility of that plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'ARC_SEC_VERSION', '0.2.0' );
define( 'ARC_SEC_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARC_SEC_URL', plugin_dir_url( __FILE__ ) );
// Concatenated with $wpdb->prefix -> wp_arc_sec_audit.
define( 'ARC_SEC_AUDIT_TABLE', 'arc_sec_audit' );

/**
 * Class autoloader: ARC_SEC_Foo_Bar -> includes/class-foo-bar.php.
 * Same convention as arc-referral-portal.
 */
spl_autoload_register(
	function ( $class ) {
		if ( 0 !== strpos( $class, 'ARC_SEC_' ) ) {
			return;
		}
		$path = ARC_SEC_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', substr( $class, 9 ) ) ) . '.php';
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, array( 'ARC_SEC_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ARC_SEC_Deactivator', 'deactivate' ) );

/**
 * Boot on the earliest plugin hook so hardening constants (DISALLOW_FILE_EDIT,
 * XML-RPC, application passwords) are in place before anything else runs.
 */
function arc_sec_boot() {
	// Schema stamp first — a pending migration runs before any module reads
	// the data it owns.
	ARC_SEC_Upgrade::maybe_upgrade();
	ARC_SEC_Plugin::instance();
	ARC_SEC_Firewall::instance();
	ARC_SEC_Login::instance();
	ARC_SEC_Hardening::instance();
	ARC_SEC_Headers::instance();
	ARC_SEC_Rest::register();
	if ( is_admin() ) {
		// Older installs stored settings autoload='off' — update_option()
		// does not flip the flag on identical values, so use the API.
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( 'arc_sec_settings', 'on' );
		}
		ARC_SEC_Admin::instance();
	}
}
add_action( 'plugins_loaded', 'arc_sec_boot', 1 );

/**
 * External audit bridge: other plugins in the ecosystem emit their own
 * audit events on their own hooks — security observes and mirrors the
 * security-relevant ones into the central log. The emitting plugin
 * stays dependency-free (it never calls this plugin); the listener
 * lives here where the central log is owned.
 *
 * @param string $event Event key emitted by the source plugin.
 * @param string $actor Actor string ('admin', 'partner:{id}'...).
 * @param int    $id_a  First related id (meaning depends on the source).
 * @param int    $id_b  Second related id.
 * @param int    $id_c  Third related id.
 * @param array  $meta  Extra context from the source.
 */
function arc_sec_mirror_rpp_event( $event, $actor, $id_a, $id_b, $id_c, $meta ) {
	unset( $actor, $id_a, $id_b, $id_c );
	if ( 0 === strpos( (string) $event, 'impersonate_' ) ) {
		ARC_SEC_Audit::log( (string) $event, 'warning', (array) $meta );
	}
}
add_action( 'arc_rpp_audit_event', 'arc_sec_mirror_rpp_event', 10, 6 );

/**
 * SEC-0480: the security test catalog (test.txt) and any helper script left
 * at the plugins ROOT is world-readable but is not part of any plugin.
 * One-time self-cleanup — production only, so the local working copies
 * under dev.test survive.
 */
function arc_sec_cleanup_strays() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? (string) wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
	if ( 'arc.example.com' !== $host && 'www.arc.example.com' !== $host ) {
		return;
	}
	foreach ( array( 'test.txt', 'markall.php', 'hello.php' ) as $stray ) {
		$path = WP_PLUGIN_DIR . '/' . $stray;
		if ( file_exists( $path ) ) {
			@unlink( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}
}
add_action( 'plugins_loaded', 'arc_sec_cleanup_strays', 0 );

/**
 * Daily housekeeping: purge audit rows past retention (SEC-0884).
 */
add_action( 'arc_sec_daily', array( 'ARC_SEC_Audit', 'purge_old' ) );

function arc_sec_load_textdomain() {
	load_plugin_textdomain( 'arc-security', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'arc_sec_load_textdomain' );
