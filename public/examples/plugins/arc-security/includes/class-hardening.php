<?php
/**
 * Core hardening toggles (SEC-0037-0042, 0750, 0775, 0782-0788, 0797, 0808).
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Hardening {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// SEC-0037/0038/0750/0782: kill XML-RPC entirely. The xmlrpc_enabled and
		// xmlrpc_methods filters only cut authenticated/wp.* calls — system.*
		// methods (listMethods, multicall) still answer, so the file itself is
		// denied here, at the earliest point a plugin can run.
		if ( ARC_SEC_Plugin::get( 'disable_xmlrpc' ) ) {
			if ( isset( $_SERVER['SCRIPT_NAME'] ) && 'xmlrpc.php' === basename( (string) wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) {
				ARC_SEC_Audit::log( 'xmlrpc_blocked', 'warning' );
				status_header( 403 );
				die( 'Forbidden' );
			}
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'xmlrpc_methods', '__return_empty_array' );
			remove_action( 'wp_head', 'rsd_link' );
		}

		// SEC-0039/0797: application passwords off unless explicitly needed.
		if ( ARC_SEC_Plugin::get( 'disable_app_passwords' ) ) {
			add_filter( 'wp_is_application_passwords_available', '__return_false' );
		}

		// SEC-0775: no plugin/theme editor in wp-admin.
		if ( ARC_SEC_Plugin::get( 'disable_file_editor' ) && ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true );
		}

		// SEC-0788/0054: no WP version leakage.
		if ( ARC_SEC_Plugin::get( 'hide_version' ) ) {
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );
		}

		// SEC-0786/0787: trim discovery surface in wp_head.
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );

		// SEC-0041/0785: ?author=N enumeration.
		if ( ARC_SEC_Plugin::get( 'block_author_scan' ) ) {
			add_action( 'template_redirect', array( $this, 'block_author_scan' ), 0 );
		}

		// SEC-0042/0784: /wp-json/wp/v2/users* hidden from anonymous callers.
		if ( ARC_SEC_Plugin::get( 'rest_users_protect' ) ) {
			add_filter( 'rest_endpoints', array( $this, 'protect_users_endpoints' ) );
		}

		// SEC-0043/0754: the public oEmbed response leaks author_name +
		// author_url (/author/<login>/), and /oembed/1.0/proxy is an open
		// fetch relay. Strip the author fields and drop the proxy route.
		add_filter( 'oembed_response_data', array( $this, 'scrub_oembed_author' ) );
		add_filter( 'rest_endpoints', array( $this, 'drop_oembed_proxy' ) );

		// SEC-0808: portal + login pages stay out of wp-sitemap.xml.
		if ( ARC_SEC_Plugin::get( 'exclude_portal_sitemap' ) ) {
			add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'exclude_portal_pages' ), 10, 2 );
		}

		// Site RBAC: only whitelisted accounts may open wp-admin pages.
		if ( ARC_SEC_Plugin::get( 'wpadmin_lockdown' ) ) {
			add_action( 'admin_init', array( $this, 'wpadmin_lockdown' ), 0 );
		}
	}

	/**
	 * wp-admin is reserved for the listed usernames (default: admin).
	 * admin-post.php and admin-ajax.php are exempt: the portal's partner
	 * actions (nopriv + session) and every plugin's AJAX live there.
	 */
	public function wpadmin_lockdown() {
		if ( wp_doing_ajax() ) {
			return;
		}
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( (string) wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';
		if ( 'admin-post.php' === $script ) {
			return;
		}
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return; // Anonymous traffic is handled by core auth_redirect.
		}
		$allowed = array_filter( array_map( 'strtolower', array_map( 'trim', explode( ',', (string) ARC_SEC_Plugin::get( 'wpadmin_users' ) ) ) ) );
		if ( ! in_array( strtolower( $user->user_login ), $allowed, true ) ) {
			ARC_SEC_Audit::log( 'wpadmin_denied', 'warning', array( 'user_login' => $user->user_login ) );
			wp_die( 'Access restricted.', 'Forbidden', array( 'response' => 403 ) );
		}
	}

	/**
	 * ?author=N reveals login names — bounce it home before the author
	 * archive resolves (SEC-0041).
	 */
	public function block_author_scan() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only inspection.
		$author = isset( $_GET['author'] ) ? $_GET['author'] : null;
		if ( is_string( $author ) && '' !== $author && preg_match( '/^\d+$/', $author ) ) {
			ARC_SEC_Audit::log( 'enum_author_blocked', 'warning', array( 'author_id' => $author ) );
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}

	/**
	 * Drop the public users routes for anonymous traffic; logged-in callers
	 * (editors, admin screens) keep them (SEC-0042).
	 *
	 * @param array $endpoints REST route map.
	 * @return array
	 */
	public function protect_users_endpoints( $endpoints ) {
		if ( ! is_user_logged_in() ) {
			foreach ( array_keys( $endpoints ) as $route ) {
				if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
					unset( $endpoints[ $route ] );
				}
			}
		}
		return $endpoints;
	}

	/**
	 * SEC-0043: oEmbed embeds author_name/author_url which reveal the
	 * login name — same enumeration ?author=N blocks. Keep the embed,
	 * drop the identity fields.
	 *
	 * @param array $data oEmbed response data.
	 * @return array
	 */
	public function scrub_oembed_author( $data ) {
		if ( is_array( $data ) ) {
			unset( $data['author_name'], $data['author_url'] );
		}
		return $data;
	}

	/**
	 * SEC-0754: /oembed/1.0/proxy proxies arbitrary remote fetches with
	 * the site's origin — unused by this site, removed entirely.
	 *
	 * @param array $endpoints REST route map.
	 * @return array
	 */
	public function drop_oembed_proxy( $endpoints ) {
		unset( $endpoints['/oembed/1.0/proxy'] );
		return $endpoints;
	}

	/**
	 * SEC-0808: the branded login and portal pages must never be indexed —
	 * they also drop out of wp-sitemap.xml so crawlers never learn the URLs.
	 *
	 * @param array  $args      WP_Query args for the sitemap.
	 * @param string $post_type Post type being listed.
	 * @return array
	 */
	public function exclude_portal_pages( $args, $post_type ) {
		if ( 'page' !== $post_type ) {
			return $args;
		}
		$excluded = array_filter(
			array(
				(int) get_option( 'arc_rpp_portal_page_id' ),
				(int) get_option( 'arc_rpp_login_page_id' ),
			)
		);
		if ( $excluded ) {
			$args['post__not_in'] = isset( $args['post__not_in'] ) && is_array( $args['post__not_in'] )
				? array_merge( $args['post__not_in'], $excluded )
				: $excluded;
		}
		return $args;
	}
}
