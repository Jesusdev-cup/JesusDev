# Svelte / SvelteKit — time-entries

El molde en Svelte 5 + SvelteKit: el dominio es una clase con runas
`.svelte.ts`; el contrato vive en `load` + `actions` del servidor.

## Temas de la documentación oficial (svelte.dev) → archivos

| Tema oficial | Archivo | Qué aporta el molde |
|---|---|---|
| Runes (`$state`, `$derived`) | `features/time-entries/entries.svelte.ts` | la clase ES el módulo de dominio |
| `.svelte` components | `EntryForm.svelte`, `EntryList.svelte` | vista que consume, no construye requests |
| Template syntax (`#each`, `#if`) | `EntryForm.svelte`, `+page.svelte` | `{#each}` con `key`, `{:else}` |
| SvelteKit routing | `routes/time-entries/` | el dominio posee su subárbol de rutas |
| `load` functions / SSR | `+page.server.ts` | datos listos en servidor (SSR nativo) |
| Form actions | `+page.server.ts` → `actions.create` | el contrato validado, `fail(400)` = forma de error |
| `use:enhance` | `+page.svelte` | progressive enhancement — funciona sin JS |
| Server-only modules | `lib/server/entries.ts` | `$lib/server` impide que el dominio llegue al cliente |
| Testing | `entries.svelte.test.ts` | la clase se prueba como módulo plano |

## Contrato

```
GET  /time-entries?day=YYYY-MM-DD   → load()
POST /time-entries?/create         → action, X-Request-Id dedup
```

## Restricciones aplicadas (máxima validación)

- La validación corre en el servidor (`+page.server.ts`) — `fail(400/403)` es la única forma de error del contrato
- `task`/`minutes` acotados en la action antes de tocar el dominio (`trim`, 1-255, 1-1440)
- `day` validado con regex `YYYY-MM-DD` → `400`
- `shared/api.ts` (cliente): JSON only, `AbortSignal.timeout(10s)`, `credentials: 'same-origin'`
- Form con bounds reales + `use:enhance` — funciona sin JS sin aflojar la validación
