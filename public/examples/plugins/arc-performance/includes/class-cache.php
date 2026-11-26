<?php
/**
 * Anonymous full-page cache.
 *
 * Hard exclusions: non-GET, any auth/user cookie, wp-admin/wp-login/REST/
 * cron/xmlrpc/feed, ?rp= portal tokens, the portal + branded-login pages,
 * previews, and configured path prefixes. Belt & suspenders: a response that
 * emits Cache-Control no-store/no-cache is never written (SEC-0053/0573).
 *
 * @package arc-performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_PERF_Cache {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Priority 9: just before redirect_canonical and other handlers at 10,
		// and before the portal's standalone document handler (its URIs are
		// excluded anyway). Serving later would skip canonical redirects.
		add_action( 'template_redirect', array( $this, 'handle' ), 9 );

		// Purge triggers — a single content change can affect nav/widgets
		// everywhere, so invalidation is global by design.
		foreach ( array( 'save_post', 'deleted_post', 'trashed_post', 'switch_theme', 'wp_update_nav_menu', 'customize_save_after', 'update_option_sidebars_widgets', 'comment_post', 'wp_set_comment_status' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'purge_all' ) );
		}
	}

	private static function request_uri() {
		return isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	}

	public static function cache_path() {
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? (string) wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
		return ARC_PERF_CACHE_DIR . md5( $scheme . '|' . $host . '|' . self::request_uri() ) . '.html';
	}

	public static function is_cacheable_request() {
		if ( ! ARC_PERF_Plugin::get( 'cache_enabled' ) ) {
			return false;
		}
		if ( 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' ) ) {
			return false;
		}
		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
			return false;
		}
		if ( is_user_logged_in() ) {
			return false;
		}
		$uri = self::request_uri();

		// Any auth/session cookie -> personalized traffic.
		foreach ( (array) $_COOKIE as $name => $v ) {
			if ( preg_match( '/wordpress_logged_in|wp-postpass|comment_author|wp_woocommerce_session|wp-resetpass/i', (string) $name ) ) {
				return false;
			}
		}
		// Core internals + feeds are never cached here.
		if ( preg_match( '#wp-admin|wp-login|wp-cron|wp-json|xmlrpc\.php|/feed/|wp-sitemap|trackback#i', $uri ) ) {
			return false;
		}
		// ?rp= portal tokens and previews carry personalized data.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only inspection.
		if ( false !== stripos( $uri, 'rp=' ) || isset( $_GET['preview'] ) ) {
			return false;
		}
		// Portal + branded-login pages themselves.
		foreach ( self::portal_paths() as $path ) {
			if ( 0 === stripos( $uri, $path ) ) {
				return false;
			}
		}
		// User-configured path prefixes.
		foreach ( self::excluded_paths() as $path ) {
			if ( '' !== $path && 0 === stripos( $uri, $path ) ) {
				return false;
			}
		}
		return true;
	}

	private static function portal_paths() {
		$paths = array();
		foreach ( array( 'arc_rpp_portal_page_id', 'arc_rpp_login_page_id' ) as $opt ) {
			$id = (int) get_option( $opt );
			if ( $id ) {
				$permalink = wp_parse_url( get_permalink( $id ), PHP_URL_PATH );
				if ( $permalink ) {
					$paths[] = $permalink;
				}
			}
		}
		return $paths;
	}

	private static function excluded_paths() {
		$raw = (string) ARC_PERF_Plugin::get( 'cache_exclusions' );
		return array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', $raw ) ) );
	}

	/**
	 * Serve a fresh copy or start the capture buffer.
	 */
	public function handle() {
		if ( ! self::is_cacheable_request() ) {
			return;
		}
		$file = self::cache_path();
		$ttl  = max( 60, (int) ARC_PERF_Plugin::get( 'cache_ttl' ) );

		if ( file_exists( $file ) && ( time() - filemtime( $file ) ) < $ttl ) {
			if ( ARC_PERF_Plugin::get( 'debug_header' ) ) {
				header( 'X-ARC-Cache: HIT' );
			}
			header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cached HTML verbatim.
			echo file_get_contents( $file );
			exit;
		}

		if ( ARC_PERF_Plugin::get( 'debug_header' ) ) {
			header( 'X-ARC-Cache: MISS' );
		}
		ob_start( array( __CLASS__, 'maybe_save' ) );
	}

	/**
	 * Output-buffer callback: persist the page unless headers forbid it.
	 *
	 * @param string $html Final buffer contents.
	 * @return string
	 */
	public static function maybe_save( $html ) {
		foreach ( headers_list() as $header ) {
			if ( 0 === stripos( $header, 'Cache-Control:' )
				&& preg_match( '/no-store|no-cache|private/i', $header ) ) {
				return $html;
			}
		}
		$code = function_exists( 'http_response_code' ) ? http_response_code() : 200;
		if ( 200 !== $code || strlen( $html ) < 512 || false === stripos( $html, '<html' ) ) {
			return $html;
		}
		if ( ! is_dir( ARC_PERF_CACHE_DIR ) ) {
			wp_mkdir_p( ARC_PERF_CACHE_DIR );
		}
		@file_put_contents( self::cache_path(), $html, LOCK_EX );
		return $html;
	}

	public static function purge_all() {
		if ( ! is_dir( ARC_PERF_CACHE_DIR ) ) {
			return 0;
		}
		$count = 0;
		foreach ( glob( ARC_PERF_CACHE_DIR . '*.html' ) as $file ) {
			if ( @unlink( $file ) ) {
				$count++;
			}
		}
		return $count;
	}

	public static function stats() {
		$files = is_dir( ARC_PERF_CACHE_DIR ) ? glob( ARC_PERF_CACHE_DIR . '*.html' ) : array();
		$size  = 0;
		foreach ( (array) $files as $file ) {
			$size += filesize( $file );
		}
		return array( 'files' => count( (array) $files ), 'bytes' => $size );
	}
}
