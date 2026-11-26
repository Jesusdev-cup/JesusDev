<?php
/**
 * Admin UI: "ARC SEO" top-level menu.
 * Styled with Tailwind (CDN, preflight off so wp-admin chrome is untouched).
 *
 * @package arc-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEO_Admin {

	private static $instance = null;
	const SLUG   = 'arc-seo';
	const ACCENT = 'violet';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_arc_seo_save', array( $this, 'save' ) );
		add_action( 'admin_post_arc_seo_recommended', array( $this, 'recommended' ) );
	}

	public function menu() {
		add_menu_page(
			__( 'ARC SEO', 'arc-seo' ),
			__( 'ARC SEO', 'arc-seo' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-chart-line',
			82
		);
	}

	public function assets( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_script( 'arc-tailwind', 'https://cdn.tailwindcss.com', array(), null, false );
		wp_add_inline_script( 'arc-tailwind', 'tailwind.config = { corePlugins: { preflight: false } };' );
		// Live character counter on the description textarea.
		wp_add_inline_script(
			'arc-tailwind',
			'document.addEventListener("input",function(e){var c=document.querySelector("[data-arc-count=\'"+e.target.id+"\']");if(c)c.textContent=e.target.value.length+"/160";});'
		);
	}

	private function field( $key, $label, $desc, $value, $type = 'text' ) {
		echo '<div class="px-3 py-3">';
		printf( '<label for="%s" class="block text-sm font-medium text-slate-800">%s</label>', esc_attr( $key ), esc_html( $label ) );
		if ( 'textarea' === $type ) {
			printf(
				'<textarea id="%1$s" name="%1$s" rows="3" maxlength="160" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-%2$s-500 focus:ring-%2$s-500">%3$s</textarea>
				<p class="m-0 mt-1 flex justify-between text-xs text-slate-500"><span>%4$s</span><span data-arc-count="%1$s">%5$d/160</span></p>',
				esc_attr( $key ),
				self::ACCENT,
				esc_textarea( $value ),
				esc_html( $desc ),
				strlen( $value )
			);
		} else {
			printf(
				'<input type="text" id="%1$s" name="%1$s" value="%3$s" class="mt-1.5 w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-%2$s-500 focus:ring-%2$s-500">
				<p class="m-0 mt-1 text-xs leading-relaxed text-slate-500">%4$s</p>',
				esc_attr( $key ),
				self::ACCENT,
				esc_attr( $value ),
				esc_html( $desc )
			);
		}
		echo '</div>';
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Denied.' );
		}
		$s = ARC_SEO_Plugin::instance()->settings();
		echo '<div class="arc-admin mx-auto max-w-4xl px-4 py-8">';

		// Header.
		printf(
			'<header class="mb-6 flex flex-wrap items-center justify-between gap-4">
				<div class="flex items-center gap-3">
					<span class="flex h-11 w-11 items-center justify-center rounded-xl bg-%1$s-600 text-white"><span class="dashicons dashicons-chart-line" style="font-size:22px;width:22px;height:22px"></span></span>
					<div>
						<h1 class="m-0 text-2xl font-bold leading-tight text-slate-900">ARC SEO <span class="ml-1 align-middle rounded-full bg-%1$s-50 px-2 py-0.5 text-xs font-semibold text-%1$s-700">v%2$s</span></h1>
						<p class="m-0 mt-1 text-sm text-slate-500">Meta tags, Open Graph and structured data — emitted automatically on every public page.</p>
					</div>
				</div>
				<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Active on all public pages</span>
			</header>',
			self::ACCENT,
			esc_html( ARC_SEO_VERSION )
		);

		// Notice.
		if ( isset( $_GET['arc_seo'] ) ) {
			$msg = 'recommended' === $_GET['arc_seo'] ? 'Auto-configured — every field reset to automatic defaults.' : 'Settings saved.';
			printf( '<div class="mb-6 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"><span class="dashicons dashicons-yes-alt"></span>%s</div>', esc_html( $msg ) );
		}

		// What runs automatically.
		echo '<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
			<h2 class="m-0 text-base font-semibold text-slate-900">Handled for you</h2>
			<div class="mt-3 grid gap-2 sm:grid-cols-2 text-sm text-slate-600">
				<p class="m-0 flex items-start gap-2"><span class="dashicons dashicons-yes-alt text-emerald-500" style="font-size:16px;width:16px;height:16px"></span>Meta description on every page (auto-generated if empty)</p>
				<p class="m-0 flex items-start gap-2"><span class="dashicons dashicons-yes-alt text-emerald-500" style="font-size:16px;width:16px;height:16px"></span>Open Graph + Twitter cards for social shares</p>
				<p class="m-0 flex items-start gap-2"><span class="dashicons dashicons-yes-alt text-emerald-500" style="font-size:16px;width:16px;height:16px"></span>JSON-LD: Organization, WebSite, Article, Breadcrumbs</p>
				<p class="m-0 flex items-start gap-2"><span class="dashicons dashicons-yes-alt text-emerald-500" style="font-size:16px;width:16px;height:16px"></span>Portal &amp; login forced <code>noindex,nofollow</code></p>
			</div>
			<p class="m-0 mt-3 text-xs text-slate-500">Per-page SEO: each post and page editor has an <strong>ARC SEO</strong> box for a custom description. Sitemap: core serves <code>/wp-sitemap.xml</code>; ARC Security keeps portal/login out of it.</p>
		</section>';

		// Auto-configure card.
		echo '<section class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-' . self::ACCENT . '-200 bg-' . self::ACCENT . '-50/60 p-6 shadow-sm">
			<div class="max-w-xl">
				<h2 class="m-0 text-base font-semibold text-slate-900">Recommended setup</h2>
				<p class="m-0 mt-1 text-sm leading-relaxed text-slate-600">One click resets every field to automatic defaults — descriptions, organization name and share images are generated from your site content. Best starting point.</p>
			</div>
			<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Reset every setting to the recommended configuration?\');">'
			. wp_nonce_field( 'arc_seo_recommended', '_wpnonce', true, false )
			. '<input type="hidden" name="action" value="arc_seo_recommended">
				<button type="submit" class="rounded-lg bg-' . self::ACCENT . '-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-' . self::ACCENT . '-700">Apply recommended setup</button>
			</form>
		</section>';

		// Settings.
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'arc_seo_save' );
		echo '<input type="hidden" name="action" value="arc_seo_save">';

		echo '<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
			<div class="border-b border-slate-100 px-3 pb-3">
				<h3 class="m-0 text-sm font-semibold text-slate-900">Site-wide settings</h3>
				<p class="m-0 mt-1 text-xs text-slate-500">Everything has a safe fallback — leave a field empty and a sensible default is used.</p>
			</div>';
		$this->field( 'front_description', 'Front page description', 'Shown in Google under your homepage. Empty = site tagline.', $s['front_description'], 'textarea' );
		$this->field( 'org_name', 'Organization name', 'Used in structured data. Empty = site name.', $s['org_name'] );
		$this->field( 'org_logo', 'Organization logo URL', 'Full URL to a logo image for Google knowledge panels.', $s['org_logo'] );
		$this->field( 'default_og_image', 'Default share image URL', 'Used when a shared page has no featured image.', $s['default_og_image'] );
		$this->field( 'twitter_handle', 'Twitter/X @handle', 'Your account, e.g. @arc. Appears on Twitter cards.', $s['twitter_handle'] );
		$this->field( 'title_separator', 'Title separator', 'Character between page title and site name. Default |', $s['title_separator'] );
		echo '</section>';

		$btn = 'rounded-lg bg-' . self::ACCENT . '-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-' . self::ACCENT . '-700 focus:outline-none focus:ring-2 focus:ring-' . self::ACCENT . '-500 focus:ring-offset-2';
		printf(
			'<div class="flex items-center gap-3">
				<button type="submit" class="%s">Save settings</button>
				<span class="text-xs text-slate-400">Changes apply immediately.</span>
			</div>',
			esc_attr( $btn )
		);
		echo '</form>';

		// Health snapshot + recent activity — same payload the REST contract
		// serves at /wp-json/arc-seo/v1/status.
		ARC_SEO_Diagnostics::card();
		echo '</div>';
	}

	public function recommended() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_seo_recommended' ) ) {
			wp_die( 'Denied.' );
		}
		ARC_SEO_Plugin::save_settings( ARC_SEO_Plugin::defaults() );
		ARC_SEO_Audit::log( 'recommended_applied' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&arc_seo=recommended' ) );
		exit;
	}

	public function save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_seo_save' ) ) {
			wp_die( 'Denied.' );
		}
		$in = array(
			'front_description' => isset( $_POST['front_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['front_description'] ) ) : '',
			'default_og_image'  => isset( $_POST['default_og_image'] ) ? esc_url_raw( wp_unslash( $_POST['default_og_image'] ) ) : '',
			'twitter_handle'    => isset( $_POST['twitter_handle'] ) ? sanitize_text_field( wp_unslash( $_POST['twitter_handle'] ) ) : '',
			'org_name'          => isset( $_POST['org_name'] ) ? sanitize_text_field( wp_unslash( $_POST['org_name'] ) ) : '',
			'org_logo'          => isset( $_POST['org_logo'] ) ? esc_url_raw( wp_unslash( $_POST['org_logo'] ) ) : '',
			'title_separator'   => isset( $_POST['title_separator'] ) ? substr( sanitize_text_field( wp_unslash( $_POST['title_separator'] ) ), 0, 3 ) : '|',
		);
		ARC_SEO_Plugin::save_settings( $in );
		ARC_SEO_Audit::log( 'settings_saved' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&arc_seo=1' ) );
		exit;
	}
}
