<?php
/**
 * JSON-LD structured data: Organization + WebSite on the front page,
 * WebPage/Article + BreadcrumbList on singular content.
 *
 * @package arc-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEO_Schema {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_head', array( $this, 'output' ), 5 );
	}

	public function output() {
		if ( ARC_SEO_Plugin::is_private_page() ) {
			return;
		}
		$graph = array();

		if ( is_front_page() ) {
			$org = array(
				'@type' => 'Organization',
				'@id'   => home_url( '/#org' ),
				'name'  => ARC_SEO_Plugin::get( 'org_name' ) ?: get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
			);
			if ( ARC_SEO_Plugin::get( 'org_logo' ) ) {
				$org['logo'] = ARC_SEO_Plugin::get( 'org_logo' );
			}
			$graph[] = $org;
			$graph[] = array(
				'@type'       => 'WebSite',
				'@id'         => home_url( '/#website' ),
				'url'         => home_url( '/' ),
				'name'        => get_bloginfo( 'name' ),
				'description' => ARC_SEO_Plugin::description(),
				'publisher'   => array( '@id' => $org['@id'] ),
			);
		}

		if ( is_singular() ) {
			$post = get_queried_object();
			$page = array(
				'@type'       => 'post' === $post->post_type ? 'Article' : 'WebPage',
				'@id'         => get_permalink( $post ) . '#webpage',
				'url'         => get_permalink( $post ),
				'name'        => ARC_SEO_Plugin::title(),
				'description' => ARC_SEO_Plugin::description(),
				'inLanguage'  => get_locale(),
				'isPartOf'    => array( '@id' => home_url( '/#website' ) ),
			);
			$image = ARC_SEO_Plugin::share_image();
			if ( $image ) {
				$page['image'] = $image;
			}
			if ( 'post' === $post->post_type ) {
				$page['datePublished'] = get_the_date( 'c', $post );
				$page['dateModified']  = get_the_modified_date( 'c', $post );
				$page['author']        = array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', $post->post_author ) );
			}
			$graph[] = $page;

			if ( ! is_front_page() ) {
				$graph[] = array(
					'@type'           => 'BreadcrumbList',
					'itemListElement' => array(
						array( '@type' => 'ListItem', 'position' => 1, 'name' => get_bloginfo( 'name' ), 'item' => home_url( '/' ) ),
						array( '@type' => 'ListItem', 'position' => 2, 'name' => get_the_title( $post ), 'item' => get_permalink( $post ) ),
					),
				);
			}
		}

		if ( ! $graph ) {
			return;
		}
		echo '<script type="application/ld+json">' . wp_json_encode(
			array( '@context' => 'https://schema.org', '@graph' => $graph ),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		) . '</script>' . "\n";
	}
}
