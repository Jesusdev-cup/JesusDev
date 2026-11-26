---
title: Examples — three real plugins
description: Three production plugins built from the same mold — arc-performance, arc-seo and arc-security — with full trees and key files.
sidebar: Examples · Plugins
---

Three real plugins in production, one mold. Each solves a different domain
— caching, SEO and security — with the same structure: autoloader
bootstrap, settings store, domain modules, REST contract, diagnostics,
schema versioning and clean uninstall.

> The full source, ready to read file by file, lives in
> `public/examples/plugins/`: **arc-performance**, **arc-seo** and
> **arc-security**.

## The shared machinery

Four files implement the methodology and are structurally identical across
the three plugins — only the `ARC_*_` prefix and the domain checks change.

### `includes/class-upgrade.php` — schema versioning

```php
class ARC_PERF_Upgrade {

	const VERSION = '1';
	const OPTION  = 'arc_perf_schema';

	// Ordered steps: stored version < step => run. The map is append-only —
	// a new change adds a key, it never edits a shipped one.
	public static function migrations() {
		return array(
			'1' => function () {
				// Baseline: settings already live in arc_perf_settings.
			},
		);
	}

	// Runs on every request — the option is autoloaded so the check costs
	// nothing; a migration only fires when a release ships a newer VERSION.
	public static function maybe_upgrade() {
		$stored = (string) get_option( self::OPTION, '0' );
		if ( version_compare( $stored, self::VERSION, '>=' ) ) {
			return;
		}
		foreach ( self::migrations() as $to => $step ) {
			if ( version_compare( $stored, $to, '<' ) ) {
				$step();
			}
		}
		update_option( self::OPTION, self::VERSION, true );
	}
}
```

### `includes/class-diagnostics.php` — health snapshot

One function answers "is this plugin healthy?": version, schema, flags,
cron, storage and recent activity. Each check maps `name => status +
detail` so any renderer (admin card or REST consumer) can display it
without knowing the internals:

```php
$checks = array(
	'cache_dir' => array(
		'status' => is_dir( ARC_PERF_CACHE_DIR ) ? ( is_writable( ARC_PERF_CACHE_DIR ) ? 'pass' : 'fail' ) : 'info',
		'detail' => /* ... */,
	),
	'db_cron'   => array( /* next scheduled run + last real run */ ),
	'schema'    => array( /* stored version vs. code version */ ),
);
// plugin status = the worst status across its checks.
```

### `includes/class-rest.php` — idempotent contract

Admin screens use `admin-post.php` + nonces; the outside world uses the
REST contract — one permission, same domain functions. Mutations honor
`X-Request-Id`: retrying with the same id replays the stored response
instead of running twice:

```php
register_rest_route( 'arc-perf/v1', '/status', array(
	'methods'             => 'GET',
	'callback'            => array( __CLASS__, 'status' ),
	'permission_callback' => array( __CLASS__, 'can' ), // manage_options
) );

$rid = trim( (string) $req->get_header( 'X-Request-Id' ) );
$key = 'arc_perf_req_' . md5( $rid );
if ( '' !== $rid && false !== ( $hit = get_transient( $key ) ) ) {
	return new WP_REST_Response( array( 'deduped' => true, 'result' => $hit ), 200 );
}
```

### `includes/class-audit.php` — bounded trail

arc-security keeps its forensic trail in a dedicated table; the other two
only need "who saved/purged/optimized what and when" — a 50-event ring
buffer in one option is the right-sized store:

```php
public static function log( $event, $context = array() ) {
	$events   = (array) get_option( self::OPTION, array() );
	$events[] = array(
		'ts'      => time(),
		'event'   => substr( sanitize_key( $event ), 0, 60 ),
		'user'    => get_current_user_id(),
		'context' => substr( (string) wp_json_encode( $context ), 0, 500 ),
	);
	// autoload=false: read on admin screens only — never inflates the bundle.
	update_option( self::OPTION, array_slice( $events, -self::LIMIT ), false );
}
```

## arc-performance — cache & maintenance

Anonymous page cache on disk, lazy-load, conservative minify and bounded
weekly database cleanup (500-row batches — it can never lock the site).

```
arc-performance/
├── arc-performance.php      # bootstrap: autoloader, hooks, boot
├── uninstall.php            # deletes options, transients and cache dir
└── includes/
    ├── class-plugin.php        # settings store (defaults + get/save)
    ├── class-cache.php         # anonymous cache with hard exclusions
    ├── class-database.php      # bounded weekly cleanup + health stamp
    ├── class-optimize.php      # lazy img, minify, emojis/embeds off
    ├── class-admin.php         # admin screen + admin-post handlers
    ├── class-upgrade.php       # schema version
    ├── class-diagnostics.php   # health snapshot + card
    ├── class-rest.php          # GET /status, POST /purge|optimize|recommended
    ├── class-audit.php         # activity log (50 events)
    ├── class-activator.php     # cache dir + settings + schema stamp
    └── class-deactivator.php   # purge + unschedule
```

The hard cache exclusions — the domain decides what is never stored:

```php
// Never caches: non-GET, auth/session cookies, wp-admin, wp-login,
// REST, feeds, previews, portal pages and ?rp= token URLs — and if the
// response emitted Cache-Control: no-store, it is never written either.
if ( is_user_logged_in() || 'GET' !== $method ) {
	return false;
}
```

The cron leaves a trail — `optimize()` stamps `arc_perf_db_last_run` and
writes to the activity log; Diagnostics shows the next run and the last
real one:

```php
$report = array(
	'transients'  => self::purge_transients(),  // 500-row batches
	'revisions'   => self::purge_revisions(),   // older than 90 days
	'orphan_meta' => self::purge_orphan_meta(),
	'optimized'   => self::optimize_tables(),   // data_free >= 1 MB only
);
update_option( 'arc_perf_db_last_run', time(), false );
ARC_PERF_Audit::log( 'db_optimized', $report );
```

## arc-seo — meta & schema emission

Meta descriptions, Open Graph/Twitter cards, JSON-LD `@graph` and per-post
SEO fields. Private pages are `noindex,nofollow` with no exceptions.

```
arc-seo/
├── arc-seo.php                # bootstrap
├── uninstall.php              # options + per-post meta + transients
└── includes/
    ├── class-plugin.php        # settings + helpers (description, image, title)
    ├── class-meta.php          # <head>: description, OG, twitter, robots
    ├── class-schema.php        # JSON-LD @graph (Org, WebSite, Article, Breadcrumbs)
    ├── class-metabox.php       # per-post title/desc with nonce + edit_post
    ├── class-admin.php         # settings page
    ├── class-upgrade.php       # schema version
    ├── class-diagnostics.php   # field coverage + private noindex
    ├── class-rest.php          # GET /status, POST /recommended
    ├── class-audit.php         # settings_saved, recommended_applied
    └── class-activator.php     # seed defaults + schema stamp
```

Domain helpers resolve ONCE — meta and schema consume the same functions
(`description()`, `share_image()`, `title()`, `private_page_ids()`), they
never duplicate the resolution logic.

## arc-security — hardening & audit

The largest one: login lockout, core hardening, headers, request firewall,
a scanner mapped to a SEC catalog and an append-only audit log on its own
table with hashed IPs (HMAC-SHA256, never in the clear).

```
arc-security/
├── arc-security.php           # bootstrap + external event bridge
├── uninstall.php              # options, transients, cron, DROP TABLE audit
└── includes/
    ├── class-plugin.php        # settings + client_ip() with trusted proxies
    ├── class-login.php         # IP+user lockout, 429, generic errors
    ├── class-hardening.php     # xmlrpc off, author-scan, REST users, sitemap
    ├── class-headers.php       # nosniff, frame, referrer, HSTS, CSP opt-in
    ├── class-firewall.php      # rejects malformed payloads before auth
    ├── class-scanner.php       # ~30 checks mapped to SEC-IDs
    ├── class-fixer.php         # uploads + root .htaccess (idempotent)
    ├── class-audit.php         # append-only table + retention purge
    ├── class-admin.php         # 3 tabs: Audit / Hardening / Log
    ├── class-upgrade.php       # schema version (dbDelta reruns create_tables)
    ├── class-diagnostics.php   # table ok, scan, purge cron, flags
    ├── class-rest.php          # GET /status, POST /scan (idempotent)
    ├── class-activator.php     # table + settings + schema + daily cron
    └── class-deactivator.php   # unschedule (data only wiped on uninstall)
```

The lockout illustrates "the domain validates": an IP+user counter in a
transient, a configurable threshold, and every event lands in the audit
table:

```php
public function on_fail( $username ) {
	ARC_SEC_Audit::log( 'login_fail', 'warning', array( 'user_login' => $username ) );
	$state = $this->attempts( $username );
	$state['fails']++;
	if ( $state['fails'] >= $threshold ) {
		$state['until'] = time() + $minutes * MINUTE_IN_SECONDS;
		ARC_SEC_Audit::log( 'login_lockout_start', 'critical', /* ... */ );
	}
	set_transient( $this->attempts_key( $username ), $state, $minutes * MINUTE_IN_SECONDS );
}
```

## Methodology → file

| Piece of the method | File | All three plugins |
|---|---|---|
| Autoloader bootstrap | `arc-*.php` | ✓ |
| Settings: defaults + typed + autoloaded | `class-plugin.php` | ✓ |
| One module per domain | `class-cache.php`, `class-meta.php`, `class-login.php`… | ✓ |
| Versioned schema, append-only migrations | `class-upgrade.php` | ✓ |
| Health snapshot + admin card | `class-diagnostics.php` | ✓ |
| REST contract + `X-Request-Id` | `class-rest.php` | ✓ |
| Bounded trail / forensic table | `class-audit.php` | option / table |
| Clean lifecycle | `class-activator.php`, `class-deactivator.php`, `uninstall.php` | ✓ |

> Same mold, three domains: a new module plugs in without touching the
> existing ones — that is the scalability checklist working.
