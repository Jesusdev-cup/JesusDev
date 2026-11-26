---
title: Applied example — WordPress plugin
description: A complete domain end to end — contract, domain class, permissions, idempotency and cron in real code.
sidebar: Example · Plugin
---

This is how the `time-entries` domain gets built — the same one running in
production inside `arc-time-clock`. Five pieces, in pipeline order.

## 1. The contract, first

Before any UI, the route is declared with its verb, its permission and its
error shape:

| Verb | Route | Permission | Typical error |
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

## 2. The permission matrix, once

```php
function arc_can(string $cap): bool {
  $map = [
    'time.read'  => 'arc_time_read',
    'time.write' => 'arc_time_write',
  ];
  return current_user_can($map[$cap] ?? 'manage_options');
}
```

UI, REST API and diagnostics all read the same function — if the permission
changes, it changes in a single place.

## 3. The idempotent mutation

```php
$request_id = sanitize_text_field($req->get_header('X-Request-Id'));
if ($seen = get_transient("arc_seen_{$request_id}")) {
  return new WP_REST_Response($seen, 200); // retry → same response
}
$entry = self::insert($args);
set_transient("arc_seen_{$request_id}", $entry, DAY_IN_SECONDS);
```

The network can fail and the user can double-click — the data does not
duplicate.

## 4. The domain exposes, never shares

`class-eod` needs the day's entries — it asks the owning module's public API:

```php
$entries = Arc_Time_Entries::for_day($user_id, $date); // public function
// never: SELECT * FROM wp_arc_time_entries
```

The table belongs to `time-entries`. Whoever touches it from outside breaks
the implicit contract.

## 5. The cron that explains itself

```php
$start = microtime(true);
$count = self::sync_pending();
arc_health_log('time.sync', microtime(true) - $start, $count);
```

Every run records duration, result and errors — automation gets audited like
any other route. Without a health log, a cron is a silent failure.

> Five pieces, one domain: declared contract, single permission, idempotent
> mutation, data behind a public function and a cron with health.
