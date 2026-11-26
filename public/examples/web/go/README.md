# Go — time-entries

El molde en Go: `internal/` impone la frontera (el toolchain, no la
disciplina), el contrato es el handler + middleware en el ServeMux.

## Temas de la documentación oficial (go.dev) → archivos

| Tema oficial | Archivo | Qué aporta el molde |
|---|---|---|
| Package layout / `internal/` | `internal/entries/` | la frontera del módulo la enforcea el compilador |
| `net/http` server + ServeMux | `cmd/server/main.go` | routing `METHOD /pattern` (Go 1.22) |
| Handlers | `internal/entries/handler.go` | validate → dedup → domain |
| Interfaces | `Store` en `entries.go` | consumidores dependen de la interfaz, no del impl |
| Middleware | `internal/entries/middleware.go` | `RequirePerm` = permission_callback |
| JSON contract | `handler.go` (`apiError`) | forma de error explícita, internals no salen |
| Concurrency safety | `sync.Mutex` en `MemStore`/`Dedup` | estado del dominio protegido |
| Testing | `internal/entries/handler_test.go` | `httptest` — contrato probado sin red |

## Contrato

```
GET  /api/time-entries?day=YYYY-MM-DD   perm: time.read
POST /api/time-entries                  perm: time.write + X-Request-Id dedup
GET  /api/status                        health
```

## Restricciones aplicadas (máxima validación)

- `http.MaxBytesReader` — body acotado a 8 KiB → `413` antes de parsear
- `dec.DisallowUnknownFields()` — campos desconocidos → `400`
- `Content-Type: application/json` obligatorio → `415`
- `X-Request-Id` obligatorio en POST → `400`
- Bounds completos: `task` 1-255 (tras trim), `minutes` 1-1440; `day` regex → `400`
- `SecurityHeaders` + `RateLimit(60/min)` middleware — `429` + `Retry-After`
- `http.Server` con timeouts explícitos — `ReadHeaderTimeout` contra Slowloris
- `401` sin principal vs `403` sin permiso — nunca se confunden
