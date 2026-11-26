---
title: Ejemplos — tres plugins reales
description: Tres plugins de producción construidos con el mismo molde — arc-performance, arc-seo y arc-security — con árbol completo y archivos clave.
sidebar: Ejemplos · Plugins
---

Tres plugins reales en producción, un solo molde. Cada uno resuelve un
dominio distinto — caché, SEO y seguridad — con la misma estructura:
bootstrap con autoloader, store de settings, módulos por dominio, contrato
REST, diagnósticos, schema versionado y limpieza total al desinstalar.

> El código fuente completo, listo para leer archivo por archivo, vive en
> `public/examples/plugins/`: **arc-performance**, **arc-seo** y
> **arc-security**.

## La maquinaria compartida

Cuatro archivos implementan la metodología y son estructuralmente idénticos
en los tres plugins — solo cambia el prefijo `ARC_*_` y los checks del
dominio.

### `includes/class-upgrade.php` — schema versionado

```php
class ARC_PERF_Upgrade {

	const VERSION = '1';
	const OPTION  = 'arc_perf_schema';

	// Pasos ordenados: la versión guardada < paso => correr. El mapa es
	// append-only — un cambio nuevo agrega una clave, nunca edita una vieja.
	public static function migrations() {
		return array(
			'1' => function () {
				// Baseline: settings ya viven en arc_perf_settings.
			},
		);
	}

	// Corre en cada request — la opción es autoloaded, la verificación no
	// cuesta nada; una migración solo dispara cuando sale una VERSION mayor.
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

Una función responde "¿está sano el plugin?": versión, schema, flags, cron,
almacenamiento y actividad reciente. Cada check es `name => status + detail`
para que cualquier render (tarjeta admin o consumidor REST) lo muestre sin
conocer internos:

```php
$checks = array(
	'cache_dir' => array(
		'status' => is_dir( ARC_PERF_CACHE_DIR ) ? ( is_writable( ARC_PERF_CACHE_DIR ) ? 'pass' : 'fail' ) : 'info',
		'detail' => /* ... */,
	),
	'db_cron'   => array( /* próximo run programado + último run real */ ),
	'schema'    => array( /* versión guardada vs. versión del código */ ),
);
// status del plugin = el peor status de sus checks.
```

### `includes/class-rest.php` — contrato con idempotencia

Las pantallas admin usan `admin-post.php` + nonces; el mundo exterior usa el
contrato REST — un mismo permiso, mismas funciones de dominio. Las mutaciones
honran `X-Request-Id`: reintentar con el mismo id repite la respuesta
guardada, no vuelve a ejecutar:

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

### `includes/class-audit.php` — rastro acotado

arc-security guarda su trail forense en una tabla dedicada; los otros dos
solo necesitan "quién guardó/purgó/optimizó qué y cuándo" — un ring buffer
de 50 eventos en una opción es el tamaño correcto:

```php
public static function log( $event, $context = array() ) {
	$events   = (array) get_option( self::OPTION, array() );
	$events[] = array(
		'ts'      => time(),
		'event'   => substr( sanitize_key( $event ), 0, 60 ),
		'user'    => get_current_user_id(),
		'context' => substr( (string) wp_json_encode( $context ), 0, 500 ),
	);
	// autoload=false: se lee solo en admin — nunca infla el bundle.
	update_option( self::OPTION, array_slice( $events, -self::LIMIT ), false );
}
```

## arc-performance — caché y mantenimiento

Caché de página anónimo en disco, lazy-load, minify conservador y limpieza
semanal de base de datos acotada (lotes de 500 — nunca bloquea).

```
arc-performance/
├── arc-performance.php      # bootstrap: autoloader, hooks, boot
├── uninstall.php            # borra opciones, transients y cache dir
└── includes/
    ├── class-plugin.php        # settings store (defaults + get/save)
    ├── class-cache.php         # caché anónimo con exclusiones duras
    ├── class-database.php      # limpieza semanal acotada + health stamp
    ├── class-optimize.php      # lazy img, minify, emojis/embeds off
    ├── class-admin.php         # pantalla admin + handlers admin-post
    ├── class-upgrade.php       # schema version
    ├── class-diagnostics.php   # health snapshot + tarjeta
    ├── class-rest.php          # GET /status, POST /purge|optimize|recommended
    ├── class-audit.php         # log de actividad (50 eventos)
    ├── class-activator.php     # cache dir + settings + schema stamp
    └── class-deactivator.php   # purge + unschedule
```

Las exclusiones duras del caché — el dominio decide qué nunca se guarda:

```php
// Nunca cachea: no-GET, cookies de auth/sesión, wp-admin, wp-login,
// REST, feeds, previews, páginas del portal y tokens ?rp= — y si la
// respuesta emitió Cache-Control: no-store, tampoco se escribe.
if ( is_user_logged_in() || 'GET' !== $method ) {
	return false;
}
```

El cron deja rastro — `optimize()` estampa `arc_perf_db_last_run` y escribe
en el activity log; Diagnostics muestra próximo run y último real:

```php
$report = array(
	'transients'  => self::purge_transients(),  // lotes de 500
	'revisions'   => self::purge_revisions(),   // > 90 días
	'orphan_meta' => self::purge_orphan_meta(),
	'optimized'   => self::optimize_tables(),   // solo data_free >= 1 MB
);
update_option( 'arc_perf_db_last_run', time(), false );
ARC_PERF_Audit::log( 'db_optimized', $report );
```

## arc-seo — emisión de meta y schema

Meta descriptions, Open Graph/Twitter cards, JSON-LD `@graph` y campos SEO
por post. Las páginas privadas quedan `noindex,nofollow` sin excepción.

```
arc-seo/
├── arc-seo.php                # bootstrap
├── uninstall.php              # opciones + meta por post + transients
└── includes/
    ├── class-plugin.php        # settings + helpers (description, image, title)
    ├── class-meta.php          # <head>: description, OG, twitter, robots
    ├── class-schema.php        # JSON-LD @graph (Org, WebSite, Article, Breadcrumbs)
    ├── class-metabox.php       # title/desc por post con nonce + edit_post
    ├── class-admin.php         # settings page
    ├── class-upgrade.php       # schema version
    ├── class-diagnostics.php   # cobertura de campos + noindex privadas
    ├── class-rest.php          # GET /status, POST /recommended
    ├── class-audit.php         # settings_saved, recommended_applied
    └── class-activator.php     # seed defaults + schema stamp
```

Los helpers del dominio resuelven UNA vez — meta y schema consumen las
mismas funciones (`description()`, `share_image()`, `title()`,
`private_page_ids()`), nunca duplican la lógica de resolución.

## arc-security — hardening y auditoría

El más grande: lockout de login, hardening de core, headers, firewall de
requests, escáner mapeado a un catálogo SEC y log de auditoría append-only
en tabla propia con IPs hasheadas (HMAC-SHA256, nunca en claro).

```
arc-security/
├── arc-security.php           # bootstrap + bridge de eventos externos
├── uninstall.php              # opciones, transients, cron, DROP TABLE audit
└── includes/
    ├── class-plugin.php        # settings + client_ip() con trusted proxies
    ├── class-login.php         # lockout IP+usuario, 429, errores genéricos
    ├── class-hardening.php     # xmlrpc off, author-scan, REST users, sitemap
    ├── class-headers.php       # nosniff, frame, referrer, HSTS, CSP opt-in
    ├── class-firewall.php      # rechaza payloads malformados antes de auth
    ├── class-scanner.php       # ~30 checks mapeados a SEC-IDs
    ├── class-fixer.php         # .htaccess de uploads + raíz (idempotente)
    ├── class-audit.php         # tabla append-only + purga por retención
    ├── class-admin.php         # 3 tabs: Audit / Hardening / Log
    ├── class-upgrade.php       # schema version (dbDelta re-corre create_tables)
    ├── class-diagnostics.php   # tabla ok, scan, cron purga, flags
    ├── class-rest.php          # GET /status, POST /scan (idempotente)
    ├── class-activator.php     # tabla + settings + schema + cron diario
    └── class-deactivator.php   # unschedule (datos solo se borran en uninstall)
```

El lockout ilustra la regla "el dominio valida": contador por IP+usuario en
transient, umbral configurable, y todo evento queda en el audit table:

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

## Metodología → archivo

| Pieza del método | Archivo | Los tres plugins |
|---|---|---|
| Bootstrap con autoloader | `arc-*.php` | ✓ |
| Settings: defaults + tipado + autoload | `class-plugin.php` | ✓ |
| Un módulo por dominio | `class-cache.php`, `class-meta.php`, `class-login.php`… | ✓ |
| Schema versionado, migraciones append-only | `class-upgrade.php` | ✓ |
| Health snapshot + tarjeta admin | `class-diagnostics.php` | ✓ |
| Contrato REST + `X-Request-Id` | `class-rest.php` | ✓ |
| Rastro acotado / tabla forense | `class-audit.php` | opción / tabla |
| Ciclo de vida limpio | `class-activator.php`, `class-deactivator.php`, `uninstall.php` | ✓ |

> Mismo molde, tres dominios: el módulo nuevo se enchufa sin tocar los
> existentes — eso es el checklist de escalabilidad funcionando.
