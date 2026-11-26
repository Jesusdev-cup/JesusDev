# React — time-entries

El mismo molde yohacase, en React: dominio aislado en `features/`,
contrato REST tipado, mutación idempotente, UI que solo consume.

## Temas de la documentación oficial (react.dev) → archivos

| Tema oficial | Archivo | Qué aporta el molde |
|---|---|---|
| Components | `src/features/time-entries/components/*.tsx` | UI pura — sin fetch suelto |
| State & events | `src/features/time-entries/hooks.ts` | el estado vive en el dominio |
| Custom hooks | `src/features/time-entries/hooks.ts` | `useEntries` es la API pública del módulo |
| Context (DI) | `src/features/time-entries/EntriesContext.tsx` | inyección del dominio sin prop drilling |
| Forms | `src/features/time-entries/components/EntryForm.tsx` | controlled inputs → `NewEntry` |
| Lists & conditional | `src/features/time-entries/components/EntryList.tsx` | render puro por props |
| Data fetching | `src/features/time-entries/api.ts` + `src/shared/api.ts` | contrato tipado + `X-Request-Id` |
| Testing | `src/features/time-entries/hooks.test.ts` | el dominio se prueba sin UI |

## Contrato

```
GET  /api/time-entries?day=YYYY-MM-DD
POST /api/time-entries        headers: X-Request-Id  → idempotente
```

## Restricciones aplicadas (máxima validación)

- `shared/api.ts`: JSON only, `AbortSignal.timeout(10s)`, `credentials: 'same-origin'` — la UI nunca arma fetch suelto
- Error del contrato tipado (`ApiError`) — la UI nunca parsea respuestas a mano
- Form con bounds reales: `required`, `maxLength={255}`, `min={1}`/`max={1440}`, `trim()` antes de enviar
