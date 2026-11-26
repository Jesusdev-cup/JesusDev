<?php
/**
 * Admin UI: "ARC Performance" top-level menu + admin-bar purge button.
 * Styled with Tailwind (CDN, preflight off so wp-admin chrome is untouched).
 *
 * @package arc-performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_PERF_Admin {

	private static $instance = null;
	const SLUG   = 'arc-performance';
	const ACCENT = 'sky';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 90 );
		add_action( 'admin_post_arc_perf_save', array( $this, 'save' ) );
		add_action( 'admin_post_arc_perf_purge', array( $this, 'purge' ) );
		add_action( 'admin_post_arc_perf_recommended', array( $this, 'recommended' ) );
		add_action( 'admin_post_arc_perf_dbopt', array( $this, 'optimize_db' ) );
	}

	public function menu() {
		add_menu_page(
			__( 'ARC Performance', 'arc-performance' ),
			__( 'ARC Performance', 'arc-performance' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-performance',
			81
		);
	}

	public function assets( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_script( 'arc-tailwind', 'https://cdn.tailwindcss.com', array(), null, false );
		wp_add_inline_script( 'arc-tailwind', 'tailwind.config = { corePlugins: { preflight: false } };' );
	}

	public function admin_bar( $bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'arc-perf-purge',
				'title' => 'Purge ARC Cache',
				'href'  => wp_nonce_url(
					admin_url( 'admin-post.php?action=arc_perf_purge&redirect=' . rawurlencode( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' ) ),
					'arc_perf_purge'
				),
			)
		);
	}

	private function toggle( $name, $title, $desc, $on ) {
		printf(
			'<label class="flex cursor-pointer items-center justify-between gap-4 rounded-lg px-3 py-3 transition hover:bg-slate-50">
				<span class="min-w-0">
					<span class="block text-sm font-medium text-slate-800">%s</span>
					<span class="mt-0.5 block text-xs leading-relaxed text-slate-500">%s</span>
				</span>
				<span class="relative inline-flex shrink-0">
					<input type="checkbox" name="%s" value="1" class="peer sr-only" %s>
					<span class="block h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-emerald-500"></span>
					<span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
				</span>
			</label>',
			esc_html( $title ),
			esc_html( $desc ),
			esc_attr( $name ),
			checked( ! empty( $on ), true, false )
		);
	}

	private function number_field( $name, $label, $desc, $value, $min = 1 ) {
		printf(
			'<div class="px-3 py-3">
				<label for="%1$s" class="block text-sm font-medium text-slate-800">%2$s</label>
				<input type="number" min="%6$d" id="%1$s" name="%1$s" value="%5$s" class="mt-1.5 w-28 rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-%4$s-500 focus:ring-%4$s-500">
				<p class="m-0 mt-1 text-xs text-slate-500">%3$s</p>
			</div>',
			esc_attr( $name ),
			esc_html( $label ),
			esc_html( $desc ),
			self::ACCENT,
			esc_attr( $value ),
			(int) $min
		);
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Denied.' );
		}
		$s     = ARC_PERF_Plugin::instance()->settings();
		$stats = ARC_PERF_Cache::stats();

		echo '<div class="arc-admin mx-auto max-w-4xl px-4 py-8">';

		// Header.
		$badge = ! empty( $s['cache_enabled'] )
			? '<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Cache active</span>'
			: '<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Cache off</span>';
		printf(
			'<header class="mb-6 flex flex-wrap items-center justify-between gap-4">
				<div class="flex items-center gap-3">
					<span class="flex h-11 w-11 items-center justify-center rounded-xl bg-%1$s-600 text-white"><span class="dashicons dashicons-performance" style="font-size:22px;width:22px;height:22px"></span></span>
					<div>
						<h1 class="m-0 text-2xl font-bold leading-tight text-slate-900">ARC Performance <span class="ml-1 align-middle rounded-full bg-%1$s-50 px-2 py-0.5 text-xs font-semibold text-%1$s-700">v%2$s</span></h1>
						<p class="m-0 mt-1 text-sm text-slate-500">Page cache and front-end optimizations — safe for the portal, everything is automatic.</p>
					</div>
				</div>
				%3$s
			</header>',
			self::ACCENT,
			esc_html( ARC_PERF_VERSION ),
			$badge
		);

		// Notice.
		if ( isset( $_GET['arc_perf'] ) ) {
			$msgs = array(
				'saved'       => 'Settings saved.',
				'purged'      => 'Cache purged — fresh pages will be generated on next visit.',
				'recommended' => 'Recommended configuration applied and cache purged.',
				'dbopt'       => 'Database optimized — expired transients, old revisions, orphaned meta and fragmented tables cleaned.',
			);
			$key  = sanitize_key( $_GET['arc_perf'] );
			if ( isset( $msgs[ $key ] ) ) {
				printf(
					'<div class="mb-6 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"><span class="dashicons dashicons-yes-alt"></span>%s</div>',
					esc_html( $msgs[ $key ] )
				);
			}
		}

		// Stat cards.
		printf(
			'<div class="mb-6 grid grid-cols-3 gap-3">
				<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="m-0 text-3xl font-bold text-%1$s-600">%2$d</p><p class="m-0 mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Cached pages</p></div>
				<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="m-0 text-3xl font-bold text-%1$s-600">%3$s</p><p class="m-0 mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Cache size</p></div>
				<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="m-0 text-3xl font-bold text-%1$s-600">%4$ds</p><p class="m-0 mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">TTL</p></div>
			</div>',
			self::ACCENT,
			(int) $stats['files'],
			esc_html( size_format( $stats['bytes'] ) ),
			(int) $s['cache_ttl']
		);

		// Auto-configure card.
		echo '<section class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-' . self::ACCENT . '-200 bg-' . self::ACCENT . '-50/60 p-6 shadow-sm">
			<div class="max-w-xl">
				<h2 class="m-0 text-base font-semibold text-slate-900">Recommended setup</h2>
				<p class="m-0 mt-1 text-sm leading-relaxed text-slate-600">One click applies the tuned configuration: page cache on, lazy images, safe minification, emoji/embed bloat off, 1-hour TTL — then purges the cache so it takes effect now. Resets the settings below.</p>
			</div>
			<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Reset every setting to the recommended configuration?\');">'
			. wp_nonce_field( 'arc_perf_recommended', '_wpnonce', true, false )
			. '<input type="hidden" name="action" value="arc_perf_recommended">
				<button type="submit" class="rounded-lg bg-' . self::ACCENT . '-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-' . self::ACCENT . '-700">Apply recommended setup</button>
			</form>
		</section>';

		// Purge card.
		// Database health card.
		$db = ARC_PERF_Database::stats();
		printf(
			'<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
				<div class="flex flex-wrap items-center justify-between gap-4">
					<div class="max-w-xl">
						<h2 class="m-0 text-base font-semibold text-slate-900">Database health</h2>
						<p class="m-0 mt-1 text-sm leading-relaxed text-slate-500">Weekly cleanup removes expired transients, revisions older than 90 days, orphaned meta and fragmented table space — all bounded, never blocking.</p>
					</div>
					<form method="post" action="%1$s" onsubmit="return confirm(\'Run the database optimization now?\');">%2$s
						<input type="hidden" name="action" value="arc_perf_dbopt">
						<button type="submit" class="rounded-lg border border-%3$s-200 bg-%3$s-50 px-5 py-2.5 text-sm font-semibold text-%3$s-700 transition hover:bg-%3$s-100">Optimize now</button>
					</form>
				</div>
				<div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
					<div class="rounded-lg bg-slate-50 px-3 py-2"><p class="m-0 text-xl font-bold text-slate-800">%4$d</p><p class="m-0 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Expired transients</p></div>
					<div class="rounded-lg bg-slate-50 px-3 py-2"><p class="m-0 text-xl font-bold text-slate-800">%5$d</p><p class="m-0 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Orphan transients</p></div>
					<div class="rounded-lg bg-slate-50 px-3 py-2"><p class="m-0 text-xl font-bold text-slate-800">%6$d</p><p class="m-0 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Old revisions</p></div>
					<div class="rounded-lg bg-slate-50 px-3 py-2"><p class="m-0 text-xl font-bold text-slate-800">%7$d</p><p class="m-0 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Orphan meta</p></div>
					<div class="rounded-lg bg-slate-50 px-3 py-2"><p class="m-0 text-xl font-bold text-slate-800">%8$s</p><p class="m-0 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Reclaimable</p></div>
				</div>
			</section>',
			esc_url( admin_url( 'admin-post.php' ) ),
			wp_nonce_field( 'arc_perf_dbopt', '_wpnonce', true, false ),
			self::ACCENT,
			(int) $db['expired_transients'],
			(int) $db['orphan_transients'],
			(int) $db['old_revisions'],
			(int) $db['orphan_postmeta'],
			esc_html( size_format( (float) $db['reclaimable_kb'] * 1024, 1 ) )
		);

		echo '<section class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
			<div class="max-w-xl">
				<h2 class="m-0 text-base font-semibold text-slate-900">Clear the cache</h2>
				<p class="m-0 mt-1 text-sm leading-relaxed text-slate-500">Deletes every stored page. Use it after changing the theme, menus or widgets — content edits purge automatically.</p>
			</div>
			<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Purge all cached pages?\');">'
			. wp_nonce_field( 'arc_perf_purge', '_wpnonce', true, false )
			. '<input type="hidden" name="action" value="arc_perf_purge"><input type="hidden" name="redirect" value="' . esc_attr( admin_url( 'admin.php?page=' . self::SLUG . '&arc_perf=purged' ) ) . '">
				<button type="submit" class="rounded-lg border border-' . self::ACCENT . '-200 bg-' . self::ACCENT . '-50 px-5 py-2.5 text-sm font-semibold text-' . self::ACCENT . '-700 transition hover:bg-' . self::ACCENT . '-100">Purge cache</button>
			</form>
		</section>';

		// Settings.
		echo '<div class="mb-6 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">Logged-in users, POSTs, the portal and URLs with <code>?rp=</code> are <strong>never</strong> cached — security always wins over speed.</div>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'arc_perf_save' );
		echo '<input type="hidden" name="action" value="arc_perf_save">';

		echo '<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
			<div class="border-b border-slate-100 px-3 pb-3">
				<h3 class="m-0 text-sm font-semibold text-slate-900">Optimizations</h3>
				<p class="m-0 mt-1 text-xs text-slate-500">Each one is safe to toggle — nothing here can break the portal or logged-in sessions.</p>
			</div>';
		$this->toggle( 'cache_enabled', 'Anonymous page cache', 'Serves full pages from disk to visitors who are not logged in. The big win.', ! empty( $s['cache_enabled'] ) );
		$this->toggle( 'lazy_images', 'Lazy-load images', 'Adds loading="lazy" to content images so off-screen images do not block rendering.', ! empty( $s['lazy_images'] ) );
		$this->toggle( 'minify_html', 'Safe HTML minification', 'Collapses whitespace in the HTML output. Skips pre/textarea/script so nothing breaks.', ! empty( $s['minify_html'] ) );
		$this->toggle( 'disable_emojis', 'Remove emoji bloat', 'Strips the WP emoji script and styles — modern browsers render emojis natively.', ! empty( $s['disable_emojis'] ) );
		$this->toggle( 'disable_embeds', 'Remove wp-embed.js', 'Only needed if other sites embed YOUR posts. Safe to remove.', ! empty( $s['disable_embeds'] ) );
		$this->toggle( 'debug_header', 'X-ARC-Cache header', 'Sends HIT/MISS with each response — handy for testing, harmless to leave on.', ! empty( $s['debug_header'] ) );
		$this->toggle( 'db_optimize', 'Weekly database optimization', 'Purges expired transients, revisions over 90 days, orphaned meta and table fragmentation every week.', ! empty( $s['db_optimize'] ) );
		echo '<div class="mt-2 grid gap-1 sm:grid-cols-2">';
		$this->number_field( 'cache_ttl', 'Cache TTL (seconds)', 'How long a cached page stays fresh. 3600 = 1 hour.', $s['cache_ttl'], 60 );
		$this->number_field( 'heartbeat_interval', 'Heartbeat interval (s)', 'WP keep-alive in wp-admin. 0 = default 15s; raise it on slow servers.', $s['heartbeat_interval'], 0 );
		echo '</div>';
		printf(
			'<div class="px-3 py-3">
				<label for="cache_exclusions" class="block text-sm font-medium text-slate-800">Extra cache exclusions</label>
				<textarea id="cache_exclusions" name="cache_exclusions" rows="3" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-%1$s-500 focus:ring-%1$s-500">%2$s</textarea>
				<p class="m-0 mt-1 text-xs leading-relaxed text-slate-500">One path prefix per line (e.g. <code>/shop/</code>). wp-admin, wp-login, REST, portal pages and <code>?rp=</code> URLs are always excluded — no need to list them.</p>
			</div>',
			self::ACCENT,
			esc_textarea( $s['cache_exclusions'] )
		);
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
		// serves at /wp-json/arc-perf/v1/status.
		ARC_PERF_Diagnostics::card();
		echo '</div>';
	}

	public function save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_perf_save' ) ) {
			wp_die( 'Denied.' );
		}
		$in = array(
			'cache_enabled'      => isset( $_POST['cache_enabled'] ) ? 1 : 0,
			'lazy_images'        => isset( $_POST['lazy_images'] ) ? 1 : 0,
			'minify_html'        => isset( $_POST['minify_html'] ) ? 1 : 0,
			'disable_emojis'     => isset( $_POST['disable_emojis'] ) ? 1 : 0,
			'disable_embeds'     => isset( $_POST['disable_embeds'] ) ? 1 : 0,
			'debug_header'       => isset( $_POST['debug_header'] ) ? 1 : 0,
			'db_optimize'        => isset( $_POST['db_optimize'] ) ? 1 : 0,
			'cache_ttl'          => max( 60, isset( $_POST['cache_ttl'] ) ? absint( $_POST['cache_ttl'] ) : 3600 ),
			'heartbeat_interval' => isset( $_POST['heartbeat_interval'] ) ? absint( $_POST['heartbeat_interval'] ) : 0,
			'cache_exclusions'   => isset( $_POST['cache_exclusions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cache_exclusions'] ) ) : '',
		);
		ARC_PERF_Plugin::save_settings( $in );
		if ( $in['db_optimize'] ) {
			ARC_PERF_Database::schedule();
		} else {
			ARC_PERF_Database::unschedule();
		}
		ARC_PERF_Audit::log( 'settings_saved' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&arc_perf=saved' ) );
		exit;
	}

	public function optimize_db() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_perf_dbopt' ) ) {
			wp_die( 'Denied.' );
		}
		ARC_PERF_Database::optimize();
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&arc_perf=dbopt' ) );
		exit;
	}

	public function recommended() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_perf_recommended' ) ) {
			wp_die( 'Denied.' );
		}
		ARC_PERF_Plugin::save_settings( ARC_PERF_Plugin::defaults() );
		ARC_PERF_Cache::purge_all();
		ARC_PERF_Audit::log( 'recommended_applied' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&arc_perf=recommended' ) );
		exit;
	}

	public function purge() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_perf_purge' ) ) {
			wp_die( 'Denied.' );
		}
		ARC_PERF_Audit::log( 'cache_purged', array( 'files' => ARC_PERF_Cache::purge_all() ) );
		$redirect = isset( $_REQUEST['redirect'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect'] ) ) : '';
		// Only allow same-site redirects.
		if ( '' === $redirect || ( 0 !== strpos( $redirect, home_url() ) && '/' !== $redirect[0] ) ) {
			$redirect = admin_url( 'admin.php?page=' . self::SLUG . '&arc_perf=purged' );
		}
		wp_safe_redirect( $redirect );
		exit;
	}
}
