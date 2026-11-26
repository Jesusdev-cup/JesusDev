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
