<?php
/**
 * Admin UI: "ARC Security" top-level menu with three tabs —
 * Audit (scanner results), Hardening (settings), Log (audit trail).
 * Every mutation goes through admin-post.php with capability + nonce.
 * Styled with Tailwind (CDN, preflight off so wp-admin chrome is untouched).
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Admin {

	private static $instance = null;
	const SLUG  = 'arc-security';
	const ACCENT = 'rose';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_arc_sec_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_arc_sec_run_scan', array( $this, 'run_scan' ) );
		add_action( 'admin_post_arc_sec_fix', array( $this, 'fix' ) );
		add_action( 'admin_post_arc_sec_recommended', array( $this, 'recommended' ) );
	}

	public function menu() {
		add_menu_page(
			__( 'ARC Security', 'arc-security' ),
			__( 'ARC Security', 'arc-security' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-shield',
			80
		);
	}

	public function assets( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_script( 'arc-tailwind', 'https://cdn.tailwindcss.com', array(), null, false );
		wp_add_inline_script( 'arc-tailwind', 'tailwind.config = { corePlugins: { preflight: false } };' );
	}

	private function tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'audit';
		return in_array( $tab, array( 'audit', 'hardening', 'log' ), true ) ? $tab : 'audit';
	}

	// ------------------------------------------------------------------ shell.

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Denied.' );
		}
		$tab = $this->tab();
		echo '<div class="arc-admin mx-auto max-w-6xl px-4 py-8">';
		$this->header();
		$this->notices();
		$this->tabs( $tab );

		if ( 'audit' === $tab ) {
			$this->render_audit();
		} elseif ( 'hardening' === $tab ) {
			$this->render_hardening();
		} else {
			$this->render_log();
		}
		echo '</div>';
	}

	private function header() {
		$scan   = get_option( 'arc_sec_scan', array() );
		$counts = ! empty( $scan['items'] ) ? array_count_values( wp_list_pluck( $scan['items'], 'status' ) ) : array();
		$fails  = (int) ( $counts['fail'] ?? 0 );
		$warns  = (int) ( $counts['warn'] ?? 0 );
		if ( empty( $scan['items'] ) ) {
			$badge = '<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Not scanned yet</span>';
		} elseif ( $fails > 0 ) {
			$badge = sprintf( '<span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">%d failing check%s</span>', $fails, $fails > 1 ? 's' : '' );
		} elseif ( $warns > 0 ) {
			$badge = sprintf( '<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">%d warning%s</span>', $warns, $warns > 1 ? 's' : '' );
		} else {
			$badge = '<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">All checks passing</span>';
		}
		printf(
			'<header class="mb-6 flex flex-wrap items-center justify-between gap-4">
				<div class="flex items-center gap-3">
					<span class="flex h-11 w-11 items-center justify-center rounded-xl bg-%1$s-600 text-white"><span class="dashicons dashicons-shield" style="font-size:22px;width:22px;height:22px"></span></span>
					<div>
						<h1 class="m-0 text-2xl font-bold leading-tight text-slate-900">ARC Security <span class="ml-1 align-middle rounded-full bg-%1$s-50 px-2 py-0.5 text-xs font-semibold text-%1$s-700">v%2$s</span></h1>
						<p class="m-0 mt-1 text-sm text-slate-500">Login protection, site hardening and audit log — everything runs automatically.</p>
					</div>
				</div>
				%3$s
			</header>',
			self::ACCENT,
			esc_html( ARC_SEC_VERSION ),
			$badge
		);
	}

	private function tabs( $tab ) {
		echo '<nav class="mb-6 flex w-fit gap-1 rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm">';
		foreach ( array( 'audit' => 'Audit', 'hardening' => 'Hardening', 'log' => 'Log' ) as $slug => $label ) {
			printf(
				'<a href="%s" class="rounded-lg px-4 py-2 text-sm font-semibold no-underline transition %s">%s</a>',
				esc_url( admin_url( 'admin.php?page=' . self::SLUG . '&tab=' . $slug ) ),
				$tab === $slug ? 'bg-' . self::ACCENT . '-600 text-white shadow' : 'text-slate-600 hover:bg-slate-100',
				esc_html( $label )
			);
		}
		echo '</nav>';
	}

	private function notices() {
		if ( ! isset( $_GET['arc_sec'] ) ) {
			return;
		}
		$map = array(
			'saved'   => array( 'Settings saved.', 'emerald' ),
			'scanned' => array( 'Scan finished — results below.', 'emerald' ),
			'fixed'   => array( 'Fix applied to the file system.', 'emerald' ),
			'fixfail' => array( 'Fix failed — check file permissions on that path.', 'rose' ),
			'recommended' => array( 'Recommended setup applied — every switch reset to the tuned defaults.', 'emerald' ),
		);
		$key = sanitize_key( $_GET['arc_sec'] );
		if ( isset( $map[ $key ] ) ) {
			printf(
				'<div class="mb-6 flex items-center gap-2 rounded-xl border border-%2$s-200 bg-%2$s-50 px-4 py-3 text-sm font-medium text-%2$s-800"><span class="dashicons dashicons-%3$s"></span>%1$s</div>',
				esc_html( $map[ $key ][0] ),
				esc_attr( $map[ $key ][1] ),
				'rose' === $map[ $key ][1] ? 'warning' : 'yes-alt'
			);
		}
	}

	private function badge( $text, $color ) {
		printf( '<span class="rounded-full bg-%s-100 px-2.5 py-0.5 text-xs font-semibold text-%s-700">%s</span>', esc_attr( $color ), esc_attr( $color ), esc_html( $text ) );
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

	private function text_field( $name, $label, $desc, $value, $mono = true ) {
		printf(
			'<div class="px-3 py-3">
				<label for="%1$s" class="block text-sm font-medium text-slate-800">%2$s</label>
				<input type="text" id="%1$s" name="%1$s" value="%5$s" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm %6$s focus:border-%4$s-500 focus:ring-%4$s-500">
				<p class="m-0 mt-1 text-xs leading-relaxed text-slate-500">%3$s</p>
			</div>',
			esc_attr( $name ),
			esc_html( $label ),
			esc_html( $desc ),
			self::ACCENT,
			esc_attr( $value ),
			$mono ? 'font-mono' : ''
		);
	}

	private function section( $title, $desc = '' ) {
		printf(
			'<div class="border-b border-slate-100 px-3 pb-3 pt-4 first:pt-0">
				<h3 class="m-0 text-sm font-semibold text-slate-900">%s</h3>%s
			</div>',
			esc_html( $title ),
			$desc ? '<p class="m-0 mt-1 text-xs text-slate-500">' . esc_html( $desc ) . '</p>' : ''
		);
	}

	private function submit( $label = 'Save changes' ) {
		$btn = 'rounded-lg bg-' . self::ACCENT . '-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-' . self::ACCENT . '-700 focus:outline-none focus:ring-2 focus:ring-' . self::ACCENT . '-500 focus:ring-offset-2';
		printf(
			'<div class="mt-6 flex items-center gap-3">
				<button type="submit" class="%s">%s</button>
				<span class="text-xs text-slate-400">Changes apply immediately.</span>
			</div>',
			esc_attr( $btn ),
			esc_html( $label )
		);
	}

	// ------------------------------------------------------------------ audit.

	private function render_audit() {
		$scan   = get_option( 'arc_sec_scan', array() );
		$items  = $scan['items'] ?? array();
		$counts = $items ? array_count_values( wp_list_pluck( $items, 'status' ) ) : array();

		// Scan action card.
		echo '<section class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
			<div class="max-w-xl">
				<h2 class="m-0 text-base font-semibold text-slate-900">Security scan</h2>
				<p class="m-0 mt-1 text-sm leading-relaxed text-slate-500">Checks the site against the SEC catalog (server-level items). Anything marked <strong>FAIL</strong> or <strong>WARN</strong> needs attention — the rest is already handled.</p>'
				. ( ! empty( $scan['ran_at'] ) ? '<p class="m-0 mt-2 text-xs text-slate-400">Last run: ' . esc_html( $scan['ran_at'] ) . ' UTC</p>' : '' ) .
			'</div>
			<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">'
			. wp_nonce_field( 'arc_sec_run_scan', '_wpnonce', true, false )
			. '<input type="hidden" name="action" value="arc_sec_run_scan">
				<button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-' . self::ACCENT . '-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-' . self::ACCENT . '-700"><span class="dashicons dashicons-search" style="font-size:16px;width:16px;height:16px"></span>' . esc_html( $items ? 'Re-run scan' : 'Run security scan' ) . '</button>
			</form>
		</section>';

		$this->render_fixers();

		// Health snapshot — same payload the REST contract serves at
		// /wp-json/arc-sec/v1/status.
		ARC_SEC_Diagnostics::card();

		if ( ! $items ) {
			echo '<div class="rounded-xl border-2 border-dashed border-slate-300 bg-white/50 px-6 py-12 text-center text-sm text-slate-500">No scan results yet — press <strong>Run security scan</strong> to check this site.</div>';
			return;
		}

		// Stat cards.
		$stats = array(
			'pass' => array( 'PASS', 'emerald', 'Working correctly' ),
			'fail' => array( 'FAIL', 'rose', 'Needs fixing now' ),
			'warn' => array( 'WARN', 'amber', 'Worth a look' ),
			'info' => array( 'INFO', 'sky', 'For your records' ),
		);
		echo '<div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">';
		foreach ( $stats as $status => $meta ) {
			printf(
				'<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="m-0 text-3xl font-bold text-%s-600">%d</p><p class="m-0 mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">%s · %s</p></div>',
				esc_attr( $meta[1] ),
				(int) ( $counts[ $status ] ?? 0 ),
				esc_html( $meta[0] ),
				esc_html( $meta[2] )
			);
		}
		echo '</div>';

		// Results table — attention first.
		$order = array( 'fail' => 0, 'warn' => 1, 'info' => 2, 'pass' => 3 );
		usort(
			$items,
			function ( $a, $b ) use ( $order ) {
				return ( $order[ $a['status'] ] ?? 4 ) <=> ( $order[ $b['status'] ] ?? 4 );
			}
		);
		$bcolors = array( 'pass' => 'emerald', 'fail' => 'rose', 'warn' => 'amber', 'info' => 'sky' );
		echo '<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
			<table class="w-full text-sm"><thead><tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
				<th class="px-4 py-3 w-20">Status</th><th class="px-4 py-3">Check</th><th class="px-4 py-3 w-44">SEC refs</th><th class="px-4 py-3 w-2/5">What it means</th>
			</tr></thead><tbody class="divide-y divide-slate-100">';
		foreach ( $items as $item ) {
			echo '<tr class="align-top"><td class="px-4 py-3">';
			$this->badge( strtoupper( $item['status'] ), $bcolors[ $item['status'] ] ?? 'slate' );
			printf(
				'</td><td class="px-4 py-3 font-medium text-slate-800">%s</td><td class="px-4 py-3"><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600">%s</code></td><td class="px-4 py-3 text-slate-600">%s</td></tr>',
				esc_html( $item['name'] ),
				esc_html( $item['id'] ),
				esc_html( $item['detail'] )
			);
		}
		echo '</tbody></table></section>';
	}

	private function render_fixers() {
		$uploads_path = ARC_SEC_Scanner::uploads_htaccess_path();
		$root_path    = ABSPATH . '.htaccess';
		$fixes        = array(
			'uploads_htaccess' => array(
				'title' => 'Block PHP inside uploads/',
				'desc'  => 'Stops a common malware trick: uploaded .php files cannot execute.',
				'path'  => $uploads_path,
				'done'  => file_exists( $uploads_path ) && false !== strpos( (string) file_get_contents( $uploads_path ), 'Require all denied' ),
			),
			'root_htaccess'    => array(
				'title' => 'Harden the root .htaccess',
				'desc'  => 'Denies dotfiles, readme/license, disables directory indexing and closes xmlrpc.php at server level.',
				'path'  => $root_path,
				'done'  => file_exists( $root_path ) && false !== strpos( (string) file_get_contents( $root_path ), 'BEGIN ARC Security' ),
			),
		);
		echo '<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
			<h2 class="m-0 text-base font-semibold text-slate-900">File-level fixes</h2>
			<p class="m-0 mt-1 text-sm text-slate-500">One-click protections written to .htaccess. Safe to re-apply — existing rules are detected and kept.</p>
			<div class="mt-4 grid gap-3 md:grid-cols-2">';
		foreach ( $fixes as $key => $fix ) {
			echo '<div class="flex flex-col justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
				<div>
					<div class="flex items-center justify-between gap-2"><p class="m-0 text-sm font-semibold text-slate-800">' . esc_html( $fix['title'] ) . '</p>';
			if ( $fix['done'] ) {
				$this->badge( 'Active', 'emerald' );
			} else {
				$this->badge( 'Not applied', 'slate' );
			}
			echo '</div><p class="m-0 mt-1 text-xs leading-relaxed text-slate-500">' . esc_html( $fix['desc'] ) . '</p>
					<code class="mt-2 block break-all rounded bg-slate-100 px-2 py-1 text-xs text-slate-500">' . esc_html( $fix['path'] ) . '</code>
				</div>';
			if ( ! $fix['done'] ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Write protection rules to this .htaccess file?\');">'
				. wp_nonce_field( 'arc_sec_fix_' . $key, '_wpnonce', true, false )
				. '<input type="hidden" name="action" value="arc_sec_fix"><input type="hidden" name="fix" value="' . esc_attr( $key ) . '">
					<button type="submit" class="w-full rounded-lg border border-' . self::ACCENT . '-200 bg-' . self::ACCENT . '-50 px-4 py-2 text-sm font-semibold text-' . self::ACCENT . '-700 transition hover:bg-' . self::ACCENT . '-100">Apply fix</button>
				</form>';
			} else {
				echo '<p class="m-0 flex items-center gap-1.5 text-xs font-medium text-emerald-600"><span class="dashicons dashicons-yes-alt" style="font-size:15px;width:15px;height:15px"></span>Nothing to do — already protected.</p>';
			}
			echo '</div>';
		}
		echo '</div></section>';
	}

	// -------------------------------------------------------------- hardening.

	private function render_hardening() {
		$s = ARC_SEC_Plugin::instance()->settings();
		echo '<section class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-' . self::ACCENT . '-200 bg-' . self::ACCENT . '-50/60 p-6 shadow-sm">
			<div class="max-w-xl">
				<h2 class="m-0 text-base font-semibold text-slate-900">Recommended setup</h2>
				<p class="m-0 mt-1 text-sm leading-relaxed text-slate-600">One click applies the tuned configuration: every protection on, lockout at 5 tries / 15 min, CSP off (safe default), audit kept 90 days. It resets all the switches below.</p>
			</div>
			<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Reset every setting to the recommended configuration?\');">'
			. wp_nonce_field( 'arc_sec_recommended', '_wpnonce', true, false )
			. '<input type="hidden" name="action" value="arc_sec_recommended">
				<button type="submit" class="rounded-lg bg-' . self::ACCENT . '-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-' . self::ACCENT . '-700">Apply recommended setup</button>
			</form>
		</section>';
		echo '<div class="mb-6 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">These switches are tuned for this site — the recommended setup is already on. Turning one off weakens protection; the Audit tab shows what each covers.</div>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'arc_sec_save_settings' );
		echo '<input type="hidden" name="action" value="arc_sec_save_settings">';

		// Card 1: login & access.
		echo '<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">';
		$this->section( 'Login &amp; access', 'Who can log in, and what happens when someone guesses wrong.' );
		$this->toggle( 'login_lockout', 'Login lockout', 'After N failed attempts the IP+username pair is locked out for a while.', ! empty( $s['login_lockout'] ) );
		$this->toggle( 'generic_login_errors', 'Generic login errors', 'Never reveal whether the username or the password was wrong.', ! empty( $s['generic_login_errors'] ) );
		$this->toggle( 'strong_passwords', 'Strong passwords', 'Administrators must use 12+ chars with mixed case and digits.', ! empty( $s['strong_passwords'] ) );
		$this->toggle( 'branded_login_only', 'Branded login only', 'wp-login.php redirects visitors to /partner-login/ — the default form is never shown.', ! empty( $s['branded_login_only'] ) );
		$this->toggle( 'wpadmin_lockdown', 'wp-admin lockdown', 'Only the whitelisted usernames below can open wp-admin; everyone else gets a 403.', ! empty( $s['wpadmin_lockdown'] ) );
		echo '<div class="mt-2 grid gap-1 sm:grid-cols-2">';
		$this->number_field( 'lockout_threshold', 'Attempts before lockout', 'Failed logins allowed before the lock kicks in.', $s['lockout_threshold'] );
		$this->number_field( 'lockout_minutes', 'Lockout minutes', 'How long a locked IP+user stays blocked.', $s['lockout_minutes'] );
		$this->number_field( 'max_input_len', 'Max input length (bytes)', 'Longer usernames/passwords are rejected as junk input.', $s['max_input_len'] );
		$this->number_field( 'login_post_limit', 'POSTs per 5 min per IP', 'Raw flood cap on wp-login.php POSTs.', $s['login_post_limit'] );
		echo '</div>';
		$this->text_field( 'wpadmin_users', 'wp-admin whitelist', 'Comma-separated usernames allowed into wp-admin. admin-post.php and admin-ajax.php stay open so portal actions keep working. Keep your own username in the list!', $s['wpadmin_users'] );
		echo '</section>';

		// Card 2: core hardening.
		echo '<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">';
		$this->section( 'Core hardening', 'Quiet information leaks and unused doors into the site.' );
		$this->toggle( 'disable_xmlrpc', 'Disable XML-RPC', 'xmlrpc.php returns 403 — kills a classic brute-force amplifier.', ! empty( $s['disable_xmlrpc'] ) );
		$this->toggle( 'block_author_scan', 'Block ?author=N scans', 'Stops bots from enumerating usernames via author archives.', ! empty( $s['block_author_scan'] ) );
		$this->toggle( 'rest_users_protect', 'Hide REST user list', '/wp-json/wp/v2/users answers 404 to anonymous visitors.', ! empty( $s['rest_users_protect'] ) );
		$this->toggle( 'hide_version', 'Hide WP version', 'Removes the generator meta tag that advertises the version.', ! empty( $s['hide_version'] ) );
		$this->toggle( 'disable_file_editor', 'Disable file editor', 'DISALLOW_FILE_EDIT — no editing plugin/theme PHP from wp-admin.', ! empty( $s['disable_file_editor'] ) );
		$this->toggle( 'disable_app_passwords', 'Disable app passwords', 'Blocks a backdoor auth method this site does not use.', ! empty( $s['disable_app_passwords'] ) );
		$this->toggle( 'exclude_portal_sitemap', 'Portal out of sitemap', 'Portal and login pages stay out of wp-sitemap.xml.', ! empty( $s['exclude_portal_sitemap'] ) );
		echo '</section>';

		// Card 3: headers & firewall.
		echo '<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">';
		$this->section( 'Headers &amp; firewall', 'What browsers and bots are allowed to do.' );
		$this->toggle( 'security_headers', 'Security headers', 'nosniff, frame, referrer and permissions-policy headers on every page.', ! empty( $s['security_headers'] ) );
		$this->toggle( 'csp_enabled', 'Content-Security-Policy', 'Strong protection — test first, a strict CSP can break page builders.', ! empty( $s['csp_enabled'] ) );
		$this->text_field( 'csp_policy', 'CSP policy', 'Only used when CSP is on. Edit the rules only if you know which origins your assets come from.', $s['csp_policy'] );
		$this->toggle( 'firewall', 'Request firewall', 'Rejects malformed login posts: array payloads, null bytes, oversized input.', ! empty( $s['firewall'] ) );
		$this->text_field( 'trusted_proxies', 'Trusted proxies', 'IPs allowed to send X-Forwarded-For (empty = nobody, safest). Add your CDN/proxy IPs here if you use one.', $s['trusted_proxies'] );
		echo '</section>';

		// Card 4: audit.
		echo '<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">';
		$this->section( 'Audit log', 'The append-only trail shown in the Log tab.' );
		$this->toggle( 'audit_enabled', 'Audit log enabled', 'Records logins, lockouts, scans and fixes. IPs stored as HMAC hashes.', ! empty( $s['audit_enabled'] ) );
		$this->number_field( 'audit_retention_days', 'Retention (days)', 'Old entries are pruned daily. Minimum 7.', $s['audit_retention_days'], 7 );
		echo '</section>';

		$this->submit( 'Save hardening settings' );
		echo '</form>';
	}

	// --------------------------------------------------------------------- log.

	private function render_log() {
		$rows     = ARC_SEC_Audit::recent( 100 );
		$sevcolor = array( 'info' => 'sky', 'warning' => 'amber', 'critical' => 'rose' );
		echo '<section class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
			<div>
				<h2 class="m-0 text-base font-semibold text-slate-900">Audit trail</h2>
				<p class="m-0 mt-1 text-sm text-slate-500">Append-only log of security events. IPs are stored as HMAC hashes — never plain text.</p>
			</div>
			<div class="text-right"><p class="m-0 text-2xl font-bold text-slate-900">' . (int) ARC_SEC_Audit::count() . '</p><p class="m-0 text-xs text-slate-500">events · kept ' . (int) ARC_SEC_Plugin::get( 'audit_retention_days' ) . ' days</p></div>
		</section>';

		echo '<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto">
			<table class="w-full text-sm"><thead><tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
				<th class="px-4 py-3">ID</th><th class="px-4 py-3">Time (UTC)</th><th class="px-4 py-3">Event</th><th class="px-4 py-3">Severity</th><th class="px-4 py-3">IP hash</th><th class="px-4 py-3">User</th><th class="px-4 py-3">Context</th>
			</tr></thead><tbody class="divide-y divide-slate-100">';
		foreach ( $rows as $row ) {
			echo '<tr class="align-top"><td class="px-4 py-3 text-slate-400">' . (int) $row->id . '</td><td class="px-4 py-3 whitespace-nowrap text-slate-600">' . esc_html( $row->created_at ) . '</td><td class="px-4 py-3"><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-700">' . esc_html( $row->event ) . '</code></td><td class="px-4 py-3">';
			$this->badge( $row->severity, $sevcolor[ $row->severity ] ?? 'slate' );
			printf(
				'</td><td class="px-4 py-3"><code class="text-xs text-slate-400">%s</code></td><td class="px-4 py-3 text-slate-600">%s</td><td class="px-4 py-3 text-xs text-slate-500">%s</td></tr>',
				esc_html( substr( $row->ip_hash, 0, 12 ) ),
				esc_html( $row->user_login ),
				esc_html( (string) $row->context )
			);
		}
		if ( ! $rows ) {
			echo '<tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">No events yet — logins, scans and fixes will appear here.</td></tr>';
		}
		echo '</tbody></table></div></section>';
	}

	// ---------------------------------------------------------------- handlers.

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_sec_save_settings' ) ) {
			wp_die( 'Denied.' );
		}
		$defaults = ARC_SEC_Plugin::defaults();
		$in       = array();
		foreach ( array_keys( $defaults ) as $key ) {
			if ( 'csp_policy' === $key ) {
				$in[ $key ] = isset( $_POST[ $key ] ) ? substr( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ), 0, 500 ) : '';
			} elseif ( 'wpadmin_users' === $key ) {
				$users      = array_filter( array_map( 'trim', explode( ',', isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '' ) ) );
				$users      = array_filter( array_map( 'sanitize_user', $users ) );
				$in[ $key ] = implode( ',', $users );
			} elseif ( 'trusted_proxies' === $key ) {
				$ips        = array_filter( array_map( 'trim', explode( ',', isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '' ) ), function ( $ip ) {
					return (bool) filter_var( $ip, FILTER_VALIDATE_IP );
				} );
				$in[ $key ] = implode( ',', $ips );
			} elseif ( in_array( $key, array( 'lockout_threshold', 'lockout_minutes', 'max_input_len', 'login_post_limit', 'audit_retention_days' ), true ) ) {
				$in[ $key ] = isset( $_POST[ $key ] ) ? absint( $_POST[ $key ] ) : $defaults[ $key ];
			} else {
				$in[ $key ] = isset( $_POST[ $key ] ) ? 1 : 0;
			}
		}
		ARC_SEC_Plugin::save_settings( $in );
		ARC_SEC_Audit::log( 'settings_saved', 'info' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&tab=hardening&arc_sec=saved' ) );
		exit;
	}

	public function recommended() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_sec_recommended' ) ) {
			wp_die( 'Denied.' );
		}
		ARC_SEC_Plugin::save_settings( ARC_SEC_Plugin::defaults() );
		ARC_SEC_Audit::log( 'recommended_applied', 'info' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&tab=hardening&arc_sec=recommended' ) );
		exit;
	}

	public function run_scan() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'arc_sec_run_scan' ) ) {
			wp_die( 'Denied.' );
		}
		update_option(
			'arc_sec_scan',
			array( 'ran_at' => gmdate( 'Y-m-d H:i:s' ), 'items' => ARC_SEC_Scanner::run() ),
			false
		);
		ARC_SEC_Audit::log( 'scan_run', 'info' );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&arc_sec=scanned' ) );
		exit;
	}

	public function fix() {
		$fix = isset( $_POST['fix'] ) ? sanitize_key( $_POST['fix'] ) : '';
		if ( ! current_user_can( 'manage_options' ) || ! in_array( $fix, array( 'uploads_htaccess', 'root_htaccess' ), true ) || ! check_admin_referer( 'arc_sec_fix_' . $fix ) ) {
			wp_die( 'Denied.' );
		}
		$result = 'uploads_htaccess' === $fix ? ARC_SEC_Fixer::protect_uploads() : ARC_SEC_Fixer::harden_root_htaccess();
		ARC_SEC_Audit::log( 'fix_applied', is_wp_error( $result ) ? 'warning' : 'info', array( 'fix' => $fix ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&arc_sec=' . ( is_wp_error( $result ) ? 'fixfail' : 'fixed' ) ) );
		exit;
	}
}
