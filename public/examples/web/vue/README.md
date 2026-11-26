# Vue — time-entries

El molde yohacase en Vue 3 (Composition API): el dominio vive en un
composable, el contrato es el `api` compartido, la UI solo consume.

## Temas de la documentación oficial (vuejs.org) → archivos

| Tema oficial | Archivo | Qué aporta el molde |
|---|---|---|
| SFC / Components | `EntryForm.vue`, `EntryList.vue`, `EntriesPage.vue` | vista pura, sin requests |
| Reactivity (ref/computed) | `useEntries.ts`, `EntryList.vue` | estado del dominio en `ref` |
| Template syntax | `EntryList.vue` | `v-for`, `v-if`, props |
| Forms (`v-model`) | `EntryForm.vue` | inputs controlados → `NewEntry` |
| Composables | `useEntries.ts` | el composable ES el módulo |
| provide/inject (DI) | `entries-key.ts`, `EntriesPage.vue` | dominio inyectado por `InjectionKey` |
| Routing | `src/router/index.ts` | rutas lazy por dominio + guard de permiso |
| Data fetching | `src/shared/api.ts` | contrato tipado + `X-Request-Id` |
| Testing | `useEntries.test.ts` | el dominio se prueba sin montar componentes |

## Contrato

```
GET  /api/time-entries?day=YYYY-MM-DD
POST /api/time-entries        headers: X-Request-Id  → idempotente
```

## Restricciones aplicadas (máxima validación)

- `shared/api.ts`: JSON only, `AbortSignal.timeout(10s)`, `credentials: 'same-origin'` — la UI nunca arma fetch suelto
- Error del contrato tipado (`ApiError`) — la UI nunca parsea respuestas a mano
- Router: guard de permiso antes de montar la ruta del dominio
- Form con bounds reales: `required`, `maxlength="255"`, `min="1"`/`max="1440"`, `trim()` antes de enviar
