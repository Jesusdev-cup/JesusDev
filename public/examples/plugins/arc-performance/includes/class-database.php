<?php
/**
 * Database hygiene: scheduled + on-demand cleanup of the four sources of
 * MySQL bloat that accumulate silently on a WP site without object cache.
 *
 *  1. Expired transients — core only deletes a transient when it is next
 *     read; dead rows sit in wp_options forever and grow every request.
 *  2. Old post revisions — each revision duplicates the full post row.
 *  3. Orphaned meta — postmeta/usermeta/commentmeta rows whose parent
 *     post/user/comment is gone.
 *  4. Table fragmentation — OPTIMIZE TABLE only where data_free shows
 *     real reclaimable space (never a blind rebuild).
 *
 * Everything is bounded (LIMIT batches) so the job can never lock the
 * site; it runs weekly via wp-cron and on demand from the admin screen.
 *
 * @package arc-performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_PERF_Database {

	const CRON_HOOK = 'arc_perf_db_weekly';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'optimize' ) );
		if ( is_admin() ) {
			// Self-healing schedule: if the event was lost (clone/restore,
			// cron flush) it is re-armed on the next admin request.
			if ( ARC_PERF_Plugin::get( 'db_optimize' ) && ! wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::CRON_HOOK );
			}
			// Settings are read on every request — older installs stored
			// them autoload='off'; update_option() won't flip the flag on
			// an identical value, so the dedicated API is required.
			if ( function_exists( 'wp_set_option_autoload' ) ) {
				wp_set_option_autoload( 'arc_perf_settings', 'on' );
			}
		}
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::CRON_HOOK );
		}
	}

	public static function unschedule() {
		$ts = wp_next_scheduled( self::CRON_HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::CRON_HOOK );
		}
	}

	/**
	 * Current bloat counters for the admin screen.
	 *
	 * @return array<string,int>
	 */
	public static function stats() {
		global $wpdb;
		$now = time();
		return array(
			'expired_transients' => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
					$wpdb->esc_like( '_transient_timeout_' ) . '%',
					$now
				)
			),
			'orphan_transients'  => (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->options} o
				LEFT JOIN {$wpdb->options} t
					ON t.option_name = CONCAT('_transient_timeout_', SUBSTRING(o.option_name, 12))
				WHERE o.option_name LIKE '_transient_%'
					AND o.option_name NOT LIKE '_transient_timeout_%'
					AND t.option_name IS NULL"
			),
			'old_revisions'      => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_date < %s",
					gmdate( 'Y-m-d H:i:s', $now - 90 * DAY_IN_SECONDS )
				)
			),
			'orphan_postmeta'    => (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL"
			),
			'reclaimable_kb'     => (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT SUM(data_free) FROM information_schema.tables WHERE table_schema = %s AND engine IN ("InnoDB","MyISAM")',
					DB_NAME
				)
			) / 1024,
		);
	}

	/**
	 * Run the full cleanup. Every step is capped so it finishes inside
	 * a normal request even on a neglected database.
	 *
	 * @return array<string,int> Rows removed per task + tables optimized.
	 */
	public static function optimize() {
		global $wpdb;
		$report = array(
			'transients'   => self::purge_transients(),
			'revisions'    => self::purge_revisions(),
			'orphan_meta'  => self::purge_orphan_meta(),
			'optimized'    => self::optimize_tables(),
		);
		// Health stamp + trail: diagnostics shows when the job last ran and
		// the activity log keeps what it removed — a cron without a trace
		// is a silent failure waiting to happen.
		update_option( 'arc_perf_db_last_run', time(), false );
		ARC_PERF_Audit::log( 'db_optimized', $report );
		return $report;
	}

	/**
	 * Expired + orphaned transients, in two bounded DELETEs.
	 *
	 * @return int Rows removed.
	 */
	private static function purge_transients() {
		global $wpdb;
		$removed = 0;

		// Orphaned values first (their timeout row is already gone).
		// Two-step: MySQL forbids LIMIT inside a multi-table DELETE.
		$orphans = $wpdb->get_col(
			"SELECT o.option_name FROM {$wpdb->options} o
			LEFT JOIN {$wpdb->options} t
				ON t.option_name = CONCAT('_transient_timeout_', SUBSTRING(o.option_name, 12))
			WHERE o.option_name LIKE '_transient_%'
				AND o.option_name NOT LIKE '_transient_timeout_%'
				AND t.option_name IS NULL
			LIMIT 500"
		);
		foreach ( (array) $orphans as $name ) {
			$removed += (int) $wpdb->query(
				$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", $name )
			);
		}

		// Expired pairs: collect the expired timeout names, then drop both rows.
		$expired = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d LIMIT 500",
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				time()
			)
		);
		foreach ( (array) $expired as $timeout_name ) {
			$value_name = '_transient_' . substr( $timeout_name, strlen( '_transient_timeout_' ) );
			$removed    += (int) $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name IN (%s, %s)",
					$timeout_name,
					$value_name
				)
			);
		}
		return $removed;
	}

	/**
	 * Revisions older than 90 days — enough history for any restore,
	 * none of the unbounded growth.
	 *
	 * @return int Rows removed.
	 */
	private static function purge_revisions() {
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS );
		$ids    = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_date < %s LIMIT 500",
				$cutoff
			)
		);
		if ( ! $ids ) {
			return 0;
		}
		$in      = implode( ',', array_map( 'intval', $ids ) );
		$removed = (int) $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$removed += (int) $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $removed;
	}

	/**
	 * Meta rows pointing at parents that no longer exist.
	 *
	 * @return int Rows removed.
	 */
	private static function purge_orphan_meta() {
		global $wpdb;
		$removed = 0;
		// Two-step again: orphaned child ids first, then a bounded IN() delete.
		$jobs = array(
			array( $wpdb->postmeta, 'meta_id', "SELECT pm.meta_id FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL LIMIT 500" ),
			array( $wpdb->commentmeta, 'meta_id', "SELECT cm.meta_id FROM {$wpdb->commentmeta} cm LEFT JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id WHERE c.comment_ID IS NULL LIMIT 500" ),
			array( $wpdb->usermeta, 'umeta_id', "SELECT um.umeta_id FROM {$wpdb->usermeta} um LEFT JOIN {$wpdb->users} u ON u.ID = um.user_id WHERE u.ID IS NULL LIMIT 500" ),
		);
		foreach ( $jobs as $job ) {
			$ids = $wpdb->get_col( $job[2] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fixed SQL.
			if ( $ids ) {
				$in       = implode( ',', array_map( 'intval', $ids ) );
				$removed += (int) $wpdb->query( "DELETE FROM {$job[0]} WHERE {$job[1]} IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}
		return $removed;
	}

	/**
	 * OPTIMIZE only tables with real reclaimable space (>= 1 MB data_free).
	 * Table names come from information_schema and are validated against
	 * the WP prefix before use — never interpolated from user input.
	 *
	 * @return int Tables optimized.
	 */
	private static function optimize_tables() {
		global $wpdb;
		$tables = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT table_name FROM information_schema.tables
				WHERE table_schema = %s AND engine IN ("InnoDB","MyISAM") AND data_free > %d',
				DB_NAME,
				1048576
			)
		);
		$done = 0;
		foreach ( (array) $tables as $table ) {
			if ( ! is_string( $table ) || 0 !== strpos( $table, $wpdb->prefix ) || ! preg_match( '/^[a-zA-Z0-9_]+$/', $table ) ) {
				continue;
			}
			$wpdb->query( 'OPTIMIZE TABLE `' . $table . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$done++;
		}
		return $done;
	}
}
