<?php
/**
 * Per-post SEO fields: custom title + meta description.
 *
 * @package arc-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEO_Metabox {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	public function register() {
		foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
			add_meta_box(
				'arc-seo',
				__( 'ARC SEO', 'arc-seo' ),
				array( $this, 'render' ),
				$type,
				'normal',
				'default'
			);
		}
	}

	public function render( $post ) {
		wp_nonce_field( 'arc_seo_metabox', 'arc_seo_nonce' );
		printf(
			'<p><label for="arc_seo_title"><strong>%s</strong></label><br><input type="text" class="widefat" id="arc_seo_title" name="arc_seo_title" value="%s" maxlength="70"><span class="description">%s</span></p>',
			esc_html__( 'SEO title', 'arc-seo' ),
			esc_attr( get_post_meta( $post->ID, '_arc_seo_title', true ) ),
			esc_html__( 'Empty = page title. Aim for <= 60 chars.', 'arc-seo' )
		);
		printf(
			'<p><label for="arc_seo_desc"><strong>%s</strong></label><br><textarea class="widefat" rows="3" id="arc_seo_desc" name="arc_seo_desc" maxlength="160">%s</textarea><span class="description">%s</span></p>',
			esc_html__( 'Meta description', 'arc-seo' ),
			esc_textarea( get_post_meta( $post->ID, '_arc_seo_desc', true ) ),
			esc_html__( 'Empty = excerpt/content. Aim for <= 155 chars.', 'arc-seo' )
		);
	}

	public function save( $post_id ) {
		if ( ! isset( $_POST['arc_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['arc_seo_nonce'] ) ), 'arc_seo_metabox' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( in_array( $post_id, ARC_SEO_Plugin::private_page_ids(), true ) ) {
			return; // The portal pages never carry SEO meta.
		}
		$title = isset( $_POST['arc_seo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['arc_seo_title'] ) ) : '';
		$desc  = isset( $_POST['arc_seo_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['arc_seo_desc'] ) ) : '';
		update_post_meta( $post_id, '_arc_seo_title', $title );
		update_post_meta( $post_id, '_arc_seo_desc', $desc );
	}
}
