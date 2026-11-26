# Qwik — time-entries

El molde en Qwik City: el contrato vive en la ruta (`routeLoader$` +
`routeAction$` + `zod$`), el dominio corre solo en servidor.

## Temas de la documentación oficial (qwik.dev) → archivos

| Tema oficial | Archivo | Qué aporta el molde |
|---|---|---|
| Components (`component$`) | `components/entry-list.tsx` | vista pura por props |
| State (`useSignal`, `useStore`) | `routes/time-entries/index.tsx` | estado local mínimo — el dominio es del servidor |
| Resumability | todo el ejemplo | cero hydration — el molde no lo rompe |
| Routing (Qwik City) | `routes/time-entries/index.tsx` | el dominio posee su ruta |
| `routeLoader$` | `useEntries` loader | GET contract: datos en servidor |
| `routeAction$` + `zod$` | `useCreateEntry` action | POST contract validado; `action.value.error` = forma de error |
| `<Form>` | form en `index.tsx` | progressive enhancement, `spaReset` |
| `server$()` | `server/entries.ts` | RPC explícito — frontera cliente/servidor |
| Request event | `requestEvent.request.headers` | `X-Request-Id` deduplicado en el action |

## Contrato

```
GET  /time-entries?day=YYYY-MM-DD   → routeLoader$
POST /time-entries (form)          → routeAction$ + zod$, X-Request-Id dedup
```

## Restricciones aplicadas (máxima validación)

- `zod$().strict()` — campos desconocidos → `400`; `task` 1-255, `minutes` 1-1440
- `fail(403)` en el action — el permiso se chequea en servidor antes del dominio
- `day` validado con regex `YYYY-MM-DD` → `fail(400)` en el loader
- `server$()` es la única frontera cliente/servidor — el dominio nunca se importa en UI
