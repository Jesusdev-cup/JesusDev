# Astro — time-entries

El molde en Astro: el schema de la colección es el contrato de datos,
los endpoints/actions son el contrato REST.

## Temas de la documentación oficial (docs.astro.build) → archivos

| Tema oficial | Archivo | Qué aporta el molde |
|---|---|---|
| Content collections | `src/content.config.ts` | `loader` + `schema` zod — datos inválidos fallan en build |
| Actions | `src/actions/index.ts` | `defineAction` + zod = mutación validada, `X-Request-Id` dedup |
| API endpoints | `src/pages/api/time-entries.ts` | `GET`/`POST` tipados con forma de error del contrato |
| Middleware | `src/middleware.ts` | edge guard: `locals.requestId` + header propagado |
| i18n routing | `astro.config.mjs` | locales declarados una vez, rutas generadas |
| Server-side domain | `src/lib/entries.ts` | el dominio vive solo en servidor |

## Contrato

```
GET  /api/time-entries?day=YYYY-MM-DD
POST /api/time-entries        X-Request-Id dedup
POST /_actions/createEntry    astro action (form) — schema zod
```

## Restricciones aplicadas (máxima validación)

- Schema `.strict()` — campos desconocidos → `400`; `task` 1-255, `minutes` 1-1440 (endpoint y action)
- `Content-Type: application/json` obligatorio → `415`
- `Content-Length` + body real ≤ 8 KiB → `413` antes de parsear
- JSON malformado → `400` con la forma de error del contrato
- `day` validado con regex `YYYY-MM-DD` → `400`
