<?php
/**
 * Health snapshot: one function that answers "is this plugin healthy?"
 * — version, schema, field coverage and the last events. Rendered on the
 * admin screen and served over the REST contract.
 *
 * @package arc-seo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEO_Diagnostics {

	const RANK = array( 'pass' => 0, 'info' => 1, 'warn' => 2, 'fail' => 3 );

	/**
	 * Every check maps name => status + detail so a renderer (admin card or
	 * REST consumer) can display it without knowing the internals.
	 *
	 * @return array<string,mixed>
	 */
	public static function snapshot() {
		$schema      = (string) get_option( ARC_SEO_Upgrade::OPTION, '0' );
		$front_desc  = (string) ARC_SEO_Plugin::get( 'front_description' );
		$private_ids = ARC_SEO_Plugin::private_page_ids();

		$checks = array(
			'front_description' => array(
				'status' => '' !== trim( $front_desc ) ? 'pass' : 'info',
				'detail' => '' !== trim( $front_desc ) ? 'Custom description set.' : 'Empty — the site tagline is used as fallback.',
			),
			'org_schema'        => array(
				'status' => ARC_SEO_Plugin::get( 'org_name' ) ? 'pass' : 'info',
				'detail' => ARC_SEO_Plugin::get( 'org_name' ) ? 'Organization name set — JSON-LD uses it.' : 'Empty — site name is used in structured data.',
			),
			'private_noindex'   => array(
				'status' => $private_ids ? 'pass' : 'warn',
				'detail' => $private_ids
					? count( $private_ids ) . ' private page(s) forced noindex,nofollow.'
					: 'No portal/login pages registered — the noindex list is empty.',
			),
			'schema'            => array(
				'status' => version_compare( $schema, ARC_SEO_Upgrade::VERSION, '>=' ) ? 'pass' : 'warn',
				'detail' => 'Stored ' . $schema . ' / code ' . ARC_SEO_Upgrade::VERSION,
			),
		);

		$worst = 'pass';
		foreach ( $checks as $check ) {
			if ( self::RANK[ $check['status'] ] > self::RANK[ $worst ] ) {
				$worst = $check['status'];
			}
		}

		return array(
			'plugin'   => 'arc-seo',
			'version'  => ARC_SEO_VERSION,
			'schema'   => $schema,
			'status'   => $worst,
			'checks'   => $checks,
			'activity' => ARC_SEO_Audit::recent( 10 ),
		);
	}

	/**
	 * Admin card — the same snapshot the REST contract serves at
	 * /wp-json/arc-seo/v1/status.
	 */
	public static function card() {
		$snap   = self::snapshot();
		$colors = array( 'pass' => 'emerald', 'info' => 'sky', 'warn' => 'amber', 'fail' => 'rose' );
		$color  = isset( $colors[ $snap['status'] ] ) ? $colors[ $snap['status'] ] : 'slate';
		echo '<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
			<div class="flex flex-wrap items-center justify-between gap-3">
				<div>
					<h2 class="m-0 text-base font-semibold text-slate-900">Health</h2>
					<p class="m-0 mt-1 text-sm text-slate-500">Live diagnostics — the same payload served at <code>/wp-json/arc-seo/v1/status</code>.</p>
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
		echo '</dl>';
		if ( $snap['activity'] ) {
			echo '<h3 class="m-0 mt-5 text-xs font-semibold uppercase tracking-wide text-slate-500">Recent activity</h3><ul class="m-0 mt-2 space-y-1">';
			foreach ( array_slice( $snap['activity'], 0, 5 ) as $row ) {
				printf(
					'<li class="flex flex-wrap items-center gap-2 text-xs text-slate-500"><code class="rounded bg-slate-100 px-1.5 py-0.5 text-slate-600">%s</code><span>%s UTC</span>%s</li>',
					esc_html( $row['event'] ),
					esc_html( gmdate( 'Y-m-d H:i', (int) $row['ts'] ) ),
					'' !== (string) $row['context'] ? '<span class="text-slate-400">— ' . esc_html( $row['context'] ) . '</span>' : ''
				);
			}
			echo '</ul>';
		}
		echo '</section>';
	}
}
