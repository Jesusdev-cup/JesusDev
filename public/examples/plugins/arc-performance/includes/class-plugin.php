<?php
/**
 * Settings store.
 *
 * @package arc-performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_PERF_Plugin {

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
			'cache_enabled'    => 1,
			'cache_ttl'        => 3600,
			'cache_exclusions' => "/cart/\n/my-account/\n/checkout/",
			'lazy_images'      => 1,
			'minify_html'      => 1,
			'disable_emojis'   => 1,
			'disable_embeds'   => 1,
			'heartbeat_interval' => 0, // 0 = WP default.
			'debug_header'     => 1,
			'db_optimize'      => 1,
		);
	}

	public function settings() {
		if ( null === $this->settings ) {
			$this->settings = wp_parse_args( (array) get_option( 'arc_perf_settings', array() ), self::defaults() );
		}
		return $this->settings;
	}

	public static function get( $key ) {
		$settings = self::instance()->settings();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : null;
	}

	public static function save_settings( $settings ) {
		$clean                   = wp_parse_args( (array) $settings, self::defaults() );
		self::instance()->settings = $clean;
		// autoload = true: settings are read on EVERY request — bundling
		// them into the single autoload SELECT costs less than a query
		// of their own.
		update_option( 'arc_perf_settings', $clean, true );
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( 'arc_perf_settings', 'on' );
		}
	}
}
