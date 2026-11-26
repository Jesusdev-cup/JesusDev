<?php
/**
 * <head> output: meta description, Open Graph, Twitter cards, robots.
 * The portal/login pages get nothing but a noindex.
 *
 * @package arc-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEO_Meta {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_head', array( $this, 'head' ), 1 );
		add_filter( 'wp_robots', array( $this, 'robots' ) );
		add_filter( 'document_title_parts', array( $this, 'title_parts' ) );
		add_filter( 'document_title_separator', array( $this, 'title_separator' ) );
	}

	public function head() {
		if ( ARC_SEO_Plugin::is_private_page() ) {
			return;
		}
		$desc  = ARC_SEO_Plugin::description();
		$title = ARC_SEO_Plugin::title();
		$url   = is_singular() ? get_permalink() : home_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
		$image = ARC_SEO_Plugin::share_image();

		if ( $desc ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( is_singular( 'post' ) ? 'article' : 'website' ) );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_attr( $url ) );
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		if ( $desc ) {
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		if ( $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		}

		$handle = ltrim( (string) ARC_SEO_Plugin::get( 'twitter_handle' ), '@' );
		printf( '<meta name="twitter:card" content="%s">' . "\n", esc_attr( $image ? 'summary_large_image' : 'summary' ) );
		printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
		if ( $desc ) {
			printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $desc ) );
		}
		if ( $image ) {
			printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
		}
		if ( $handle ) {
			printf( '<meta name="twitter:site" content="@%s">' . "\n", esc_attr( $handle ) );
		}
	}

	/**
	 * Portal + login pages stay out of every index (SEC-0052/0576);
	 * search results and 404s are never useful landing pages either.
	 *
	 * @param array $robots Robots directives.
	 * @return array
	 */
	public function robots( $robots ) {
		if ( ARC_SEO_Plugin::is_private_page() || is_search() || is_404() ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
			unset( $robots['max-image-preview'], $robots['max-snippet'], $robots['max-video-preview'] );
		}
		return $robots;
	}

	/**
	 * Apply the per-page SEO title when set.
	 *
	 * @param array $parts Title parts.
	 * @return array
	 */
	public function title_parts( $parts ) {
		if ( is_singular() ) {
			$custom = (string) get_post_meta( get_queried_object_id(), '_arc_seo_title', true );
			if ( '' !== $custom ) {
				$parts['title'] = $custom;
			}
		}
		return $parts;
	}

	public function title_separator( $sep ) {
		$custom = (string) ARC_SEO_Plugin::get( 'title_separator' );
		return '' !== $custom ? $custom : $sep;
	}
}
