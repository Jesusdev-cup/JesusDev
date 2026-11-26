# Laravel — time-entries

El molde ya es convención en Laravel: Form Request = contrato,
Policy = matriz única, `app/Domain/` = módulos.

## Temas de la documentación oficial (laravel.com/docs) → archivos

| Tema oficial | Archivo | Qué aporta el molde |
|---|---|---|
| Routing | `routes/api.php` | el dominio registra sus rutas con su stack de middleware |
| Middleware | `app/Http/Middleware/IdempotencyKey.php` | dedup por header en capa HTTP |
| Form Requests / Validation | `app/Domain/TimeEntries/CreateEntryRequest.php` | `authorize()` + `rules()` = permiso + schema |
| Controllers | `app/Http/Controllers/TimeEntryController.php` | controller delgado → delega al dominio |
| Authorization (Policies) | `app/Domain/TimeEntries/TimeEntryPolicy.php` | matriz única leída por `can()`/`authorize()` |
| Eloquent / Migrations | `TimeEntry.php`, `database/migrations/*` | `request_id` UNIQUE — la DB enforcea idempotencia |
| Domain structure | `app/Domain/TimeEntries/Entries.php` | `firstOrCreate` keyed = retry → misma respuesta |
| Testing | `tests/Feature/TimeEntriesTest.php` | idempotencia y validación probadas end-to-end |
| Auth (Sanctum) | `routes/api.php` | `auth:sanctum` como guard de borde |

## Contrato

```
GET  /api/time-entries?day=YYYY-MM-DD   sanctum + time.read
POST /api/time-entries                  sanctum + time.write + Idempotency-Key
```

## Restricciones aplicadas (máxima validación)

- `bail` + bounds en cada rule: `task` 1-255, `minutes` 1-1440 — un solo fallo por campo
- `prepareForValidation()` — trim antes de validar, el dominio recibe datos normalizados
- `throttle:60,1` reads / `throttle:10,1` writes — presupuestos por tipo de operación
- `Idempotency-Key` **obligatorio** → `400` sin header; formato validado (`[\w\-:.]{1,128}`)
- `day` validado `date_format:Y-m-d` → `422`
- `authorize()` en el Form Request → `403` antes del dominio
