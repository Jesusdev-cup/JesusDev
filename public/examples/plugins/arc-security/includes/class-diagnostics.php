<?php
/**
 * Health snapshot: one function that answers "is this plugin healthy?"
 * — version, schema, scan state, audit trail, cron and the protection
 * flags. Rendered on the admin screen and served over the REST contract.
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Diagnostics {

	const RANK = array( 'pass' => 0, 'info' => 1, 'warn' => 2, 'fail' => 3 );

	/**
	 * Every check maps name => status + detail so a renderer (admin card or
	 * REST consumer) can display it without knowing the internals.
	 *
	 * @return array<string,mixed>
	 */
	public static function snapshot() {
		global $wpdb;
		$schema   = (string) get_option( ARC_SEC_Upgrade::OPTION, '0' );
		$scan     = get_option( 'arc_sec_scan', array() );
		$counts   = ! empty( $scan['items'] ) ? array_count_values( wp_list_pluck( $scan['items'], 'status' ) ) : array();
		$fails    = (int) ( $counts['fail'] ?? 0 );
		$next_cron = wp_next_scheduled( 'arc_sec_daily' );
		$last_purge = (int) get_option( 'arc_sec_last_purge' );
		$table    = ARC_SEC_Audit::table();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- own table name.
		$table_ok = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		$checks = array(
			'audit_table' => array(
				'status' => $table_ok ? 'pass' : 'fail',
				'detail' => $table_ok ? ARC_SEC_Audit::count() . ' events stored.' : 'Missing — deactivating and reactivating recreates it.',
			),
			'scan'        => array(
				'status' => empty( $scan['items'] ) ? 'info' : ( $fails > 0 ? 'fail' : 'pass' ),
				'detail' => empty( $scan['items'] )
					? 'Never run — press Run security scan on the Audit tab.'
					: $fails . ' failing, ' . (int) ( $counts['warn'] ?? 0 ) . ' warnings — last run ' . ( $scan['ran_at'] ?? 'unknown' ) . ' UTC.',
			),
			'purge_cron'  => array(
				'status' => $next_cron ? 'pass' : 'warn',
				'detail' => $next_cron
					? 'Next ' . gmdate( 'Y-m-d H:i', $next_cron ) . ' UTC — last ' . ( $last_purge ? gmdate( 'Y-m-d H:i', $last_purge ) . ' UTC' : 'never' )
					: 'Daily event lost — deactivation/reactivation re-arms it.',
			),
			'schema'      => array(
				'status' => version_compare( $schema, ARC_SEC_Upgrade::VERSION, '>=' ) ? 'pass' : 'warn',
				'detail' => 'Stored ' . $schema . ' / code ' . ARC_SEC_Upgrade::VERSION,
			),
		);

		$worst = 'pass';
		foreach ( $checks as $check ) {
			if ( self::RANK[ $check['status'] ] > self::RANK[ $worst ] ) {
				$worst = $check['status'];
			}
		}

		return array(
			'plugin'  => 'arc-security',
			'version' => ARC_SEC_VERSION,
			'schema'  => $schema,
			'status'  => $worst,
			'flags'   => array(
				'login_lockout'    => (bool) ARC_SEC_Plugin::get( 'login_lockout' ),
				'firewall'         => (bool) ARC_SEC_Plugin::get( 'firewall' ),
				'security_headers' => (bool) ARC_SEC_Plugin::get( 'security_headers' ),
				'audit'            => (bool) ARC_SEC_Plugin::get( 'audit_enabled' ),
			),
			'checks'  => $checks,
		);
	}

	/**
	 * Admin card — the same snapshot the REST contract serves at
	 * /wp-json/arc-sec/v1/status.
	 */
	public static function card() {
		$snap   = self::snapshot();
		$colors = array( 'pass' => 'emerald', 'info' => 'sky', 'warn' => 'amber', 'fail' => 'rose' );
		$color  = isset( $colors[ $snap['status'] ] ) ? $colors[ $snap['status'] ] : 'slate';
		echo '<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
			<div class="flex flex-wrap items-center justify-between gap-3">
				<div>
					<h2 class="m-0 text-base font-semibold text-slate-900">Health</h2>
					<p class="m-0 mt-1 text-sm text-slate-500">Live diagnostics — the same payload served at <code>/wp-json/arc-sec/v1/status</code>.</p>
				</div>';
		printf(
			'<span class="rounded-full bg-%1$s-100 px-3 py-1 text-xs font-semibold text-%1$s-700">%2$s</span></div>',
			esc_attr( $color ),
			esc_html( strtoupper( $snap['status'] ) )
		);
		echo '<dl class="mt-4 grid gap-2 sm:grid-cols-2">';
		foreach ( $snap['checks'] as $name => $check ) {
			printf(
				'<div class="flex items-start gap-2 rounded-lg bg-slate-50 px-3 py-2"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-%s-500"></span><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-600">%s</dt><dd class="m-0 mt-0.5 text-xs leading-relaxed text-slate-500">%s</dd></div></div>',
				esc_attr( isset( $colors[ $check['status'] ] ) ? $colors[ $check['status'] ] : 'slate' ),
				esc_html( str_replace( '_', ' ', $name ) ),
				esc_html( $check['detail'] )
			);
		}
		echo '</dl></section>';
	}
}
