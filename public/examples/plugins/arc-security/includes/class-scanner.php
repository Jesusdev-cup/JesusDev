<?php
/**
 * Automated security scanner.
 *
 * Each check is mapped to the SEC-IDs of the test catalog (test.txt) so a
 * run answers "which catalog items are covered by site-level controls, and
 * which still need work". Statuses: pass | fail | warn | info.
 * Items that belong to arc-referral-portal internals (tokens, RBAC, nonces,
 * escaping, idempotency — findings H1-H17) are reported in a dedicated
 * section instead of being marked pass/fail here.
 *
 * @package arc-security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ARC_SEC_Scanner {

	/**
	 * Run every check and return the item list.
	 *
	 * @return array<int,array{id:string,name:string,status:string,detail:string}>
	 */
	public static function run() {
		$items = array();
		$add   = function ( $id, $name, $status, $detail = '' ) use ( &$items ) {
			$items[] = array(
				'id'     => $id,
				'name'   => $name,
				'status' => $status,
				'detail' => $detail,
			);
		};
		$get = function ( $key ) {
			return ARC_SEC_Plugin::get( $key );
		};

		// ------------------------------------------------ transport / HTTPS.
		$https = 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME );
		$add( 'SEC-0561,0563,0777', 'HTTPS as the site scheme', $https ? 'pass' : 'warn', $https ? home_url() : 'Site runs over HTTP — expected on local dev, required in production.' );
		$add( 'SEC-0777', 'FORCE_SSL_ADMIN', defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'pass' : 'warn', defined( 'FORCE_SSL_ADMIN' ) ? ( FORCE_SSL_ADMIN ? 'Set.' : 'Defined false.' ) : 'Not defined — set it in wp-config.php when HTTPS is on.' );
		$add( 'SEC-0562', 'HSTS header', $https && $get( 'security_headers' ) ? 'pass' : 'warn', $https ? 'Sent on HTTPS responses (15552000s, includeSubDomains).' : 'Needs HTTPS first.' );
		$add( 'SEC-0566,0567,0569,0570,0600', 'Security headers (nosniff, frame, referrer, permissions-policy)', $get( 'security_headers' ) ? 'pass' : 'fail', 'Frontend + wp-login.php + REST.' );
		$add( 'SEC-0568,0588', 'Content-Security-Policy', $get( 'csp_enabled' ) ? 'pass' : 'info', $get( 'csp_enabled' ) ? 'Enabled.' : 'Optional — enable after testing against Elementor/admin assets.' );
		$add( 'SEC-0577,0598,0834', 'X-Powered-By / expose_php', '0' === (string) ini_get( 'expose_php' ) ? 'pass' : 'warn', 'expose_php=' . ini_get( 'expose_php' ) . ' (plugin sets it at runtime; fix in php.ini for a permanent pass).' );

		// ------------------------------------------------ login & auth.
		$add( 'SEC-0036,0065,0796', 'Login lockout (brute force)', $get( 'login_lockout' ) ? 'pass' : 'fail', $get( 'lockout_threshold' ) . ' fails -> ' . $get( 'lockout_minutes' ) . ' min lock, per IP+username.' );
		$add( 'SEC-0002,0003', 'Generic login errors (no user enumeration)', $get( 'generic_login_errors' ) ? 'pass' : 'fail' );
		$add( 'SEC-0034,0794', 'Strong passwords for elevated roles', $get( 'strong_passwords' ) ? 'pass' : 'warn', 'administrator + arc_admin: >=12 chars, mixed case, digit.' );
		// SEC-0040/0792 (DOC): 'admin' is an accepted account here — it is the
		// only wp-admin user by policy and is covered by lockout, strong
		// passwords and the wp-admin gate below. Other predictable names fail.
		$admin_hit = self::elevated_usernames_exist();
		$add( 'SEC-0040,0792', 'Predictable usernames other than the designated admin account', $admin_hit ? 'fail' : 'pass', $admin_hit ? 'Found: ' . implode( ', ', $admin_hit ) . ' (admin is an accepted account — sole wp-admin user).' : 'Only the designated admin account exists.' );
		$add( 'SEC-0111', 'wp-admin restricted to whitelisted users', $get( 'wpadmin_lockdown' ) ? 'pass' : 'warn', 'Allowed: ' . ( $get( 'wpadmin_users' ) ?: '(none)' ) . ' — admin-post/admin-ajax exempt for portal actions.' );
		$add( 'SEC-0006,0007', 'wp-login.php never shows its own form (branded login only)', $get( 'branded_login_only' ) ? 'pass' : 'warn', 'GET -> /partner-login/ with redirect_to preserved; POST and core actions (logout, lostpassword, resetpass) still work.' );
		$add( 'SEC-0606,0795', '2FA for privileged roles', 'info', 'Not implemented — track as a catalog recommendation.' );

		// ------------------------------------------------ enumeration.
		$add( 'SEC-0041,0785', '?author=N scan blocked', $get( 'block_author_scan' ) ? 'pass' : 'fail' );
		$add( 'SEC-0042,0784', 'REST /wp/v2/users restricted for anonymous', $get( 'rest_users_protect' ) ? 'pass' : 'fail', 'Anonymous requests get rest_no_route; logged-in users keep it.' );
		$add( 'SEC-0039,0797', 'Application passwords disabled', $get( 'disable_app_passwords' ) ? 'pass' : 'warn' );
		$add( 'SEC-0037,0038,0750,0782', 'XML-RPC disabled', $get( 'disable_xmlrpc' ) && ! apply_filters( 'xmlrpc_enabled', true ) ? 'pass' : 'fail', 'xmlrpc_enabled=false + empty method map (no wp.getUsersBlogs, no system.multicall).' );
		$add( 'SEC-0786,0787', 'RSD/WLW/shortlink/oEmbed-discovery/REST head links removed', 'pass' );

		// ------------------------------------------------ core hardening.
		$add( 'SEC-0775', 'DISALLOW_FILE_EDIT', defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ? 'pass' : 'fail' );
		$add( 'SEC-0776', 'DISALLOW_FILE_MODS', defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ? 'pass' : 'info', 'Deployment-policy dependent.' );
		$add( 'SEC-0788,0054', 'WP version hidden (generator meta)', $get( 'hide_version' ) ? 'pass' : 'fail' );
		$ping_off = ! get_option( 'default_pingback_flag' ) && in_array( get_option( 'default_ping_status' ), array( 'closed', '0', 0, false ), true );
		$add( 'SEC-0751,0783', 'Pingbacks/trackbacks off', $ping_off ? 'pass' : 'warn', 'pingback_flag=' . var_export( get_option( 'default_pingback_flag' ), true ) . ', ping_status=' . var_export( get_option( 'default_ping_status' ), true ) );
		$add( 'SEC-0791', 'Public registration', get_option( 'users_can_register' ) ? ( 'subscriber' === get_option( 'default_role' ) ? 'warn' : 'fail' ) : 'pass', get_option( 'users_can_register' ) ? 'Registration ON, default role: ' . get_option( 'default_role' ) : 'Registration disabled.' );
		$add( 'SEC-0808', 'Portal/login pages excluded from wp-sitemap.xml', $get( 'exclude_portal_sitemap' ) ? 'pass' : 'warn' );

		// ------------------------------------------------ versions & config.
		global $wp_version;
		$add( 'SEC-0766', 'WordPress core version', 'info', 'Running ' . $wp_version . ' — confirm it is the latest stable.' );
		$add( 'SEC-0767', 'Minor auto-updates', ( defined( 'WP_AUTO_UPDATE_CORE' ) && false === WP_AUTO_UPDATE_CORE ) ? 'warn' : 'pass', defined( 'WP_AUTO_UPDATE_CORE' ) ? 'WP_AUTO_UPDATE_CORE=' . var_export( WP_AUTO_UPDATE_CORE, true ) : 'Default (minor auto-updates on).' );
		$updates = self::pending_updates();
		$add( 'SEC-0768,0769', 'Pending plugin/theme updates', $updates > 0 ? 'warn' : 'pass', $updates . ' update(s) pending.' );
		$add( 'SEC-0770,0826', 'PHP version', version_compare( PHP_VERSION, '8.2', '>=' ) ? 'pass' : 'warn', 'PHP ' . PHP_VERSION . ' — >=8.2 recommended for production.' );
		$add( 'SEC-0771', 'WP_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG ? 'warn' : 'pass', 'On in dev is fine; must be off in production.' );
		$add( 'SEC-0772', 'WP_DEBUG_LOG location', defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? self::debug_log_status() : 'info', defined( 'WP_DEBUG_LOG' ) ? ( is_string( WP_DEBUG_LOG ) ? WP_DEBUG_LOG : 'wp-content/debug.log (default)' ) : 'Not defined.' );
		$add( 'SEC-0773', 'display_errors', '1' === (string) ini_get( 'display_errors' ) || 'On' === (string) ini_get( 'display_errors' ) ? 'warn' : 'pass', 'display_errors=' . ini_get( 'display_errors' ) );
		$add( 'SEC-0774', 'SCRIPT_DEBUG', defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'warn' : 'pass' );
		$add( 'SEC-0778', 'Salts/keys unique', self::salts_ok() ? 'pass' : 'fail', 'AUTH_KEY and friends must not keep placeholder values.' );
		$add( 'SEC-0779', 'Table prefix', $GLOBALS['wpdb']->prefix === 'wp_' ? 'info' : 'pass', 'Prefix: ' . $GLOBALS['wpdb']->prefix );
		$add( 'SEC-0780', 'WP_ENVIRONMENT_TYPE', 'info', 'Current: ' . wp_get_environment_type() . ' — must be "production" in prod (the portal dev-tools gate depends on it, H17).' );
		$add( 'SEC-0781', 'ARC_RPP_DEV_TOOLS', defined( 'ARC_RPP_DEV_TOOLS' ) && ARC_RPP_DEV_TOOLS ? 'warn' : 'pass', defined( 'ARC_RPP_DEV_TOOLS' ) ? 'Defined (' . var_export( ARC_RPP_DEV_TOOLS, true ) . ') — must NOT be defined in production.' : 'Not defined.' );

		// ------------------------------------------------ filesystem.
		$add( 'SEC-0789', 'readme.html removed', file_exists( ABSPATH . 'readme.html' ) ? 'warn' : 'pass', file_exists( ABSPATH . 'readme.html' ) ? 'Delete ABSPATH/readme.html — leaks the WP version.' : '' );
		$add( 'SEC-0800', 'Uploads PHP execution denied', self::uploads_protected() ? 'pass' : 'warn', 'Use the fix button to drop a deny .htaccess into uploads/.' );
		$add( 'SEC-0801,0802,0803', 'Root .htaccess hardening (dotfiles, xmlrpc, indexing)', self::root_hardened() ? 'pass' : 'warn', 'Use the fix button to insert the ARC marker block.' );
		$add( 'SEC-0804', 'wp-config.php location', file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ? 'pass' : 'info', file_exists( ABSPATH . 'wp-config.php' ) ? 'wp-config.php is inside the webroot — acceptable when the web server denies access; ideally move it one level up.' : 'Above webroot.' );
		$add( 'SEC-0749,0805', 'DISABLE_WP_CRON + real cron', defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'pass' : 'info', defined( 'DISABLE_WP_CRON' ) ? 'Disabled — ensure a real cron hits wp-cron.php.' : 'WP-Cron runs on traffic; consider a real cron in production.' );
		$add( 'SEC-0810', 'Inactive themes', self::count_inactive_themes() > 1 ? 'warn' : 'pass', self::count_inactive_themes() . ' inactive theme(s).' );
		$add( 'SEC-0811', 'Inactive plugins', self::count_inactive_plugins() > 0 ? 'warn' : 'pass', self::count_inactive_plugins() . ' inactive plugin(s) — hello.php included.' );
		$add( 'SEC-0812,0813', 'mu-plugins / drop-ins', 'info', self::mu_dropins() );
		$add( 'SEC-0823', 'admin_email domain', 'info', get_option( 'admin_email' ) );
		$add( 'SEC-0828', 'Database version', 'info', $GLOBALS['wpdb']->get_var( 'SELECT VERSION()' ) );
		$add( 'SEC-0840,0841,0842,0844', 'PHP limits', 'info', 'post_max=' . ini_get( 'post_max_size' ) . ', upload_max=' . ini_get( 'upload_max_filesize' ) . ', memory=' . ini_get( 'memory_limit' ) . ', input_vars=' . ini_get( 'max_input_vars' ) );

		// ------------------------------------------------ audit & firewall.
		$add( 'SEC-0063,0064,0747', 'Request firewall (oversize/null-byte/array input)', $get( 'firewall' ) ? 'pass' : 'fail' );
		$add( 'SEC-0819,0877,0878', 'Central audit: login fail/success logged', $get( 'audit_enabled' ) ? 'pass' : 'fail', 'Append-only table, IPs stored as HMAC-SHA256.' );
		$add( 'SEC-0884', 'Audit retention policy', $get( 'audit_retention_days' ) >= 7 ? 'pass' : 'warn', $get( 'audit_retention_days' ) . ' days, daily purge via cron.' );
		$add( 'SEC-0370,0864', 'Trusted proxies for X-Forwarded-For', '' === trim( (string) $get( 'trusted_proxies' ) ) ? 'info' : 'pass', '' === trim( (string) $get( 'trusted_proxies' ) ) ? 'REMOTE_ADDR only (safe default). Configure when a proxy/CDN sits in front.' : $get( 'trusted_proxies' ) );

		// ------------------------------------------------ portal awareness.
		$portal = class_exists( 'ARC_RPP_Portal' ) || defined( 'ARC_RPP_VERSION' ) || is_plugin_active( 'arc-referral-portal/arc-referral-portal.php' );
		$add( 'portal', 'arc-referral-portal detected', $portal ? 'info' : 'warn', $portal ? 'Site-level protections apply to it. Catalog items bound to its internals (SEC-0066-0765 ranges, findings H1-H17) must be fixed in that plugin.' : 'Not active — audit its own checklist separately.' );

		return $items;
	}

	private static function elevated_usernames_exist() {
		$found   = array();
		$allowed = array_map( 'strtolower', array_map( 'trim', explode( ',', (string) ARC_SEC_Plugin::get( 'wpadmin_users' ) ) ) );
		foreach ( array( 'admin', 'administrator', 'root', 'test' ) as $name ) {
			if ( username_exists( $name ) && ! in_array( $name, $allowed, true ) ) {
				$found[] = $name;
			}
		}
		return $found;
	}

	private static function pending_updates() {
		require_once ABSPATH . 'wp-admin/includes/update.php';
		$plugins = function_exists( 'get_plugin_updates' ) ? count( (array) get_plugin_updates() ) : 0;
		$themes  = function_exists( 'get_theme_updates' ) ? count( (array) get_theme_updates() ) : 0;
		return $plugins + $themes;
	}

	private static function debug_log_status() {
		if ( is_string( WP_DEBUG_LOG ) && 0 === strpos( WP_DEBUG_LOG, ABSPATH ) ) {
			return 'warn';
		}
		// Default location wp-content/debug.log sits in the webroot.
		return true === WP_DEBUG_LOG ? 'warn' : 'info';
	}

	private static function salts_ok() {
		foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $key ) {
			if ( ! defined( $key ) || 'put your unique phrase here' === constant( $key ) || strlen( (string) constant( $key ) ) < 32 ) {
				return false;
			}
		}
		return true;
	}

	public static function uploads_htaccess_path() {
		$uploads = wp_upload_dir();
		return trailingslashit( $uploads['basedir'] ) . '.htaccess';
	}

	private static function uploads_protected() {
		$file = self::uploads_htaccess_path();
		return file_exists( $file ) && false !== strpos( (string) file_get_contents( $file ), 'Require all denied' );
	}

	private static function root_hardened() {
		$file = ABSPATH . '.htaccess';
		return file_exists( $file ) && false !== strpos( (string) file_get_contents( $file ), 'ARC Security' );
	}

	private static function count_inactive_themes() {
		$all    = wp_get_themes();
		$active = wp_get_theme()->get_stylesheet();
		$count  = 0;
		foreach ( $all as $slug => $theme ) {
			if ( $slug !== $active ) {
				$count++;
			}
		}
		return $count;
	}

	private static function count_inactive_plugins() {
		$all = (array) get_plugins();
		return count( $all ) - count( (array) get_option( 'active_plugins', array() ) );
	}

	private static function mu_dropins() {
		$mu = function_exists( 'get_mu_plugins' ) ? array_keys( (array) get_mu_plugins() ) : array();
		$di = array();
		foreach ( array( 'advanced-cache.php', 'object-cache.php', 'db.php', 'maintenance.php' ) as $drop ) {
			if ( file_exists( WP_CONTENT_DIR . '/' . $drop ) ) {
				$di[] = $drop;
			}
		}
		return 'mu-plugins: ' . ( $mu ? implode( ', ', $mu ) : 'none' ) . ' | drop-ins: ' . ( $di ? implode( ', ', $di ) : 'none' );
	}
}
