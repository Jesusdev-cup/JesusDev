<?php
/**
 * Settings store + shared helpers (page resolution, description building).
 *
 * @package arc-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEO_Plugin {

	private static $instance = null;
	private $settings         = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function defaults() {
		return array(
			'front_description' => '',
			'default_og_image'  => '',
			'twitter_handle'    => '',
			'org_name'          => '',
			'org_logo'          => '',
			'title_separator'   => '|',
		);
	}

	public function settings() {
		if ( null === $this->settings ) {
			$this->settings = wp_parse_args( (array) get_option( 'arc_seo_settings', array() ), self::defaults() );
		}
		return $this->settings;
	}

	public static function get( $key ) {
		$settings = self::instance()->settings();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : null;
	}

	public static function save_settings( $settings ) {
		$clean                  = wp_parse_args( (array) $settings, self::defaults() );
		self::instance()->settings = $clean;
		// autoload = true: meta output reads settings on every front-end
		// request — a dedicated SELECT per page is pure waste.
		update_option( 'arc_seo_settings', $clean, true );
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( 'arc_seo_settings', 'on' );
		}
	}

	/**
	 * The portal and branded-login pages: never indexed, never described.
	 *
	 * @return int[]
	 */
	public static function private_page_ids() {
		return array_filter(
			array(
				(int) get_option( 'arc_rpp_portal_page_id' ),
				(int) get_option( 'arc_rpp_login_page_id' ),
			)
		);
	}

	public static function is_private_page() {
		return is_singular() && in_array( get_queried_object_id(), self::private_page_ids(), true );
	}

	/**
	 * Meta description for the current request: per-post field first,
	 * then excerpt/content, front-page setting, term description.
	 *
	 * @return string
	 */
	public static function description() {
		$desc = '';
		if ( is_singular() ) {
			$post = get_queried_object();
			$desc = (string) get_post_meta( $post->ID, '_arc_seo_desc', true );
			if ( '' === $desc ) {
				$desc = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
			}
		} elseif ( is_front_page() || is_home() ) {
			$desc = (string) self::get( 'front_description' );
			if ( '' === $desc ) {
				$desc = get_bloginfo( 'description' );
			}
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$desc = term_description();
		}
		$desc = trim( wp_strip_all_tags( strip_shortcodes( (string) $desc ) ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $desc, 0, 160 ) : substr( $desc, 0, 160 );
	}

	/**
	 * Share image for the current request: featured image -> default.
	 *
	 * @return string URL or ''.
	 */
	public static function share_image() {
		if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
			$src = wp_get_attachment_image_src( get_post_thumbnail_id( get_queried_object_id() ), 'large' );
			if ( $src ) {
				return $src[0];
			}
		}
		return (string) self::get( 'default_og_image' );
	}

	public static function title() {
		if ( is_singular() ) {
			$custom = (string) get_post_meta( get_queried_object_id(), '_arc_seo_title', true );
			if ( '' !== $custom ) {
				return $custom;
			}
		}
		return wp_get_document_title();
	}
}
