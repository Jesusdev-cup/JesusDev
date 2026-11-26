---
title: Ejemplo aplicado — plugin WordPress
description: Un dominio completo de punta a punta — contrato, clase de dominio, permisos, idempotencia y cron en código real.
sidebar: Ejemplo · Plugin
---

Así se construye el dominio `time-entries` — el mismo que corre en producción
en `arc-time-clock`. Cinco piezas, en el orden del pipeline.

## 1. El contrato, primero

Antes de cualquier UI, la ruta se declara con su verbo, su permiso y su forma
de error:

| Verbo | Ruta | Permiso | Error típico |
|---|---|---|---|
| GET | `/arc/v1/time-entries` | `time.read` | `403 forbidden` |
| POST | `/arc/v1/time-entries` | `time.write` | `409 duplicate` |
| POST | `/arc/v1/time-entries/close` | `time.write` | `422 invalid_state` |

```php
register_rest_route('arc/v1', '/time-entries', [
  'methods'             => 'POST',
  'callback'            => [self::class, 'create'],
  'permission_callback' => fn() => arc_can('time.write'),
  'args'                => $schema,
]);
```

## 2. La matriz de permisos, una sola

```php
function arc_can(string $cap): bool {
  $map = [
    'time.read'  => 'arc_time_read',
    'time.write' => 'arc_time_write',
  ];
  return current_user_can($map[$cap] ?? 'manage_options');
}
```

La UI, la API REST y los diagnósticos leen la misma función — si el permiso
cambia, cambia en un solo sitio.

## 3. La mutación idempotente

```php
$request_id = sanitize_text_field($req->get_header('X-Request-Id'));
if ($seen = get_transient("arc_seen_{$request_id}")) {
  return new WP_REST_Response($seen, 200); // reintento → misma respuesta
}
$entry = self::insert($args);
set_transient("arc_seen_{$request_id}", $entry, DAY_IN_SECONDS);
```

La red puede fallar y el usuario puede hacer doble clic — el dato no se
duplica.

## 4. El dominio expone, nunca comparte

`class-eod` necesita las entradas del día — las pide por la API pública del
módulo dueño:

```php
$entries = Arc_Time_Entries::for_day($user_id, $date); // función pública
// jamás: SELECT * FROM wp_arc_time_entries
```

La tabla pertenece a `time-entries`. Quien la toca por fuera rompe el
contrato implícito.

## 5. El cron que se explica

```php
$start = microtime(true);
$count = self::sync_pending();
arc_health_log('time.sync', microtime(true) - $start, $count);
```

Cada corrida deja duración, resultado y errores — la automatización se audita
como una ruta más. Sin health log, un cron es un fallo en silencio.

> Cinco piezas, un dominio: contrato declarado, permiso único, mutación
> idempotente, datos tras función pública y cron con health.
