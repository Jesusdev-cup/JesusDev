# Express — time-entries

El molde en Express: la cadena de middleware ES la declaración del
contrato — permiso → schema → idempotencia → dominio.

## Temas de la documentación oficial (expressjs.com) → archivos

| Tema oficial | Archivo | Qué aporta el molde |
|---|---|---|
| Routing (`express.Router`) | `routes/time-entries.ts` | el dominio posee su router |
| Middleware | `middleware/*.ts` | cada pieza del contrato es un middleware |
| `req`/`res` API | `routes/time-entries.ts` | `req.body` ya validado por schema |
| Error handling | `app.ts` (error boundary) | forma de error del contrato, internals no salen |
| App structure | `src/{domain,routes,middleware}` | dominio puro sin imports de express |
| Validation | `middleware/validate.ts` (zod) | 400 con la forma de error del contrato |
| Idempotency | `middleware/idempotent.ts` | dedup por `Idempotency-Key`/`X-Request-Id` |
| Auth/permissions | `middleware/require-perm.ts` | `permission_callback` por ruta |

## Contrato

```
GET  /api/time-entries?day=YYYY-MM-DD   perm: time.read
POST /api/time-entries                  perm: time.write + Idempotency-Key
GET  /api/status                        health
```

## Restricciones aplicadas (máxima validación)

- `express.json({ limit: '8kb' })` — bodies acotados antes de parsear
- `securityHeaders()` — headers en cada respuesta (helmet-style, sin deps)
- `rateLimit(60)` — ventana fija por IP+ruta, `429` + `Retry-After`
- `requireJson()` — `415` si el body no es `application/json`
- Schema `.strict()` — campos desconocidos → `400`; `task` 1-255, `minutes` 1-1440
- `day` validado con regex `YYYY-MM-DD` → `400`
- `401` sin principal vs `403` sin permiso — nunca se confunden
- `x-powered-by` deshabilitado — el stack no se anuncia
