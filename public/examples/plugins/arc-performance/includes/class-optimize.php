<?php
/**
 * Front-end optimizations: lazy images, conservative HTML minify, and
 * removal of bloat the site does not use (emojis, oEmbed script).
 *
 * @package arc-performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_PERF_Optimize {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( ARC_PERF_Plugin::get( 'lazy_images' ) ) {
			add_filter( 'wp_content_img_tag', array( $this, 'lazy_img' ), 20, 3 );
		}
		if ( ARC_PERF_Plugin::get( 'minify_html' ) ) {
			// Priority 99: starts AFTER the cache buffer (priority 9), so it
			// sits as the innermost buffer and the cached copy is stored
			// already minified. On a cache HIT the request exits at priority 9
			// and this buffer never opens.
			add_action( 'template_redirect', array( $this, 'maybe_minify' ), 99 );
		}
		if ( ARC_PERF_Plugin::get( 'disable_emojis' ) ) {
			$this->disable_emojis();
		}
		if ( ARC_PERF_Plugin::get( 'disable_embeds' ) ) {
			add_action( 'init', array( $this, 'disable_embeds' ), 9999 );
		}
		$heartbeat = (int) ARC_PERF_Plugin::get( 'heartbeat_interval' );
		if ( $heartbeat >= 15 ) {
			add_filter(
				'heartbeat_settings',
				function ( $settings ) use ( $heartbeat ) {
					$settings['interval'] = $heartbeat;
					return $settings;
				}
			);
		}
	}

	/**
	 * Ensure every content image is lazy + async-decoded (WP adds loading=lazy
	 * since 5.5 — this backstops markup that skipped it).
	 *
	 * @param string $filtered_image Full img tag.
	 * @param string $context        Additional context.
	 * @param int    $attachment_id  Attachment ID.
	 * @return string
	 */
	public function lazy_img( $filtered_image, $context, $attachment_id ) {
		if ( false === stripos( $filtered_image, 'loading=' ) ) {
			$filtered_image = str_replace( '<img ', '<img loading="lazy" ', $filtered_image );
		}
		if ( false === stripos( $filtered_image, 'decoding=' ) ) {
			$filtered_image = str_replace( '<img ', '<img decoding="async" ', $filtered_image );
		}
		return $filtered_image;
	}

	public function maybe_minify() {
		if ( is_admin() || is_feed() || is_user_logged_in() || 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'minify' ) );
	}

	/**
	 * Conservative minifier: strips HTML comments (keeps conditional and
	 * boilerplate comments) and collapses whitespace BETWEEN tags only —
	 * never inside <pre>/<textarea>/<script>/<style> content.
	 *
	 * @param string $html Buffer contents.
	 * @return string
	 */
	public static function minify( $html ) {
		if ( false === stripos( $html, '<html' ) ) {
			return $html;
		}
		// Protect raw-text elements with placeholders.
		$protected = array();
		$html      = preg_replace_callback(
			'#<(pre|textarea|script|style)[\s>].*?</\1>#is',
			function ( $m ) use ( &$protected ) {
				$protected[] = $m[0];
				return "\x00ARCPERF" . ( count( $protected ) - 1 ) . "\x00";
			},
			$html
		);
		$html = preg_replace( '/<!--(?!\s*\[if|<!|>)[\s\S]*?-->/s', '', $html );
		$html = preg_replace( '/>\s+</', '><', $html );
		$html = preg_replace( '/^\s+|\s+$/m', '', $html );

		foreach ( $protected as $i => $chunk ) {
			$html = str_replace( "\x00ARCPERF{$i}\x00", $chunk, $html );
		}
		return $html;
	}

	private function disable_emojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}

	/**
	 * The site does not embed WP posts elsewhere — drop the wp-embed script.
	 */
	public function disable_embeds() {
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	}
}
