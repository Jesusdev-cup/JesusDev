<?php
/**
 * Central append-only audit log (SEC-0866 a 0895).
 *
 * IPs are stored as HMAC-SHA256 hashes (never the raw IP) so the log keeps
 * its investigative value without leaking personal data (SEC-0892 / section 10).
 * Nothing in this class updates or deletes single rows — the only mutation
 * is the retention purge (SEC-0883, SEC-0884).
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Audit {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . ARC_SEC_AUDIT_TABLE;
	}

	public static function log( $event, $severity = 'info', $context = array() ) {
		global $wpdb;

		if ( ! ARC_SEC_Plugin::get( 'audit_enabled' ) ) {
			return;
		}
		if ( ! in_array( $severity, array( 'info', 'warning', 'critical' ), true ) ) {
			$severity = 'info';
		}

		// Meta is bounded so a fat payload can never bloat the row (SEC-0885).
		$meta = wp_json_encode( array_map( 'strval', (array) $context ) );
		if ( strlen( $meta ) > 2000 ) {
			$meta = substr( $meta, 0, 2000 );
		}

		// Silent-fail is by design (SEC-0886): logging must never break a request.
		$wpdb->insert(
			self::table(),
			array(
				'event'      => substr( sanitize_key( $event ), 0, 60 ),
				'severity'   => $severity,
				'ip_hash'    => self::ip_hash(),
				'user_login' => isset( $context['user_login'] ) ? substr( sanitize_text_field( $context['user_login'] ), 0, 60 ) : '',
				'context'    => $meta,
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	public static function ip_hash() {
		$ip = ARC_SEC_Plugin::client_ip();
		return 'unknown' === $ip ? '' : hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
	}

	public static function recent( $limit = 100 ) {
		global $wpdb;
		$limit = min( 500, max( 1, (int) $limit ) );
		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT %d', $limit )
		);
	}

	public static function count() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() );
	}

	/**
	 * Retention policy (SEC-0884): rows older than audit_retention_days go.
	 */
	public static function purge_old() {
		global $wpdb;
		$days = max( 7, (int) ARC_SEC_Plugin::get( 'audit_retention_days' ) );
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . self::table() . ' WHERE created_at < %s',
				gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS )
			)
		);
		// Health stamp: diagnostics can prove the retention cron ran.
		update_option( 'arc_sec_last_purge', time(), false );
	}
}
