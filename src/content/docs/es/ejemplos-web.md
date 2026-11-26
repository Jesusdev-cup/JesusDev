---
title: Ejemplos en frameworks web
description: El mismo molde aplicado con código — React, Vue, Angular, Svelte, Qwik, Express, Laravel, Symfony, Go y PHP.
sidebar: Ejemplos web
---

El molde no cambia — cambia el equivalente de "módulo", "contrato" y "hook"
en cada runtime. El mismo dominio `time-entries`, once veces.

## Front-end

### React — feature + contrato tipado

```
src/
├── features/time-entries/
│   ├── api.ts            # único punto que habla con el backend
│   ├── hooks.ts
│   └── components/
└── shared/ui/            # se importa, no se copia
```

```ts
// features/time-entries/api.ts — la UI nunca arma fetch suelto
export const createEntry = (body: NewEntry, requestId: string) =>
  api.post<TimeEntry>('/time-entries', body, {
    headers: { 'X-Request-Id': requestId },
  });
```

### Vue — composable por dominio

```ts
// features/time-entries/useEntries.ts — el dominio vive en un composable
export function useEntries() {
  const list = ref<TimeEntry[]>([]);
  const create = (e: NewEntry) =>
    api.post('/time-entries', e, {
      headers: { 'X-Request-Id': crypto.randomUUID() },
    });
  return { list, create }; // la vista consume, no construye requests
}
```

El composable es el módulo: posee el estado del dominio y expone funciones
públicas. Otro dominio no toca `list` — pide por la función.

### Angular — servicio por dominio + interceptor

```ts
// features/time-entries/entries.service.ts — el dominio es un servicio
@Injectable({ providedIn: 'root' })
export class EntriesService {
  private http = inject(HttpClient);

  create(e: NewEntry) {
    return this.http.post<TimeEntry>('/api/time-entries', e, {
      headers: { 'X-Request-Id': crypto.randomUUID() },
    });
  }
}
```

Un interceptor global añade auth y normaliza la forma de error — igual que
`permission_callback` + el error del contrato en WordPress.

### Svelte — store por dominio

```ts
// features/time-entries/store.ts — el store es el dominio
function createEntries() {
  const { subscribe } = writable<TimeEntry[]>([]);
  return {
    subscribe,
    create: (e: NewEntry) =>
      api.post('/time-entries', e, {
        headers: { 'X-Request-Id': crypto.randomUUID() },
      }),
  };
}
export const entries = createEntries();
```

El componente lee `$entries` y llama `entries.create()` — la mutación siempre
pasa por el dominio, nunca por un fetch improvisado.

### Qwik — el contrato vive en la ruta

```tsx
// routes/time-entries/index.tsx — loaders y actions son el contrato
export const useCreateEntry = routeAction$(async (data, { request }) => {
  return entries.create(data); // corre en servidor: dominio puro
});
```

`routeLoader$` y `routeAction$` son las rutas REST del runtime: el cliente
consume el contrato, nunca la tabla.

## Backend

### Node / Express — router + middleware idempotente

```ts
router.post('/time-entries',
  requirePerm('time.write'),
  idempotent(),
  async (req, res) => {
    const entry = await entries.create(req.body);
    res.status(201).json(entry);
  });
```

`idempotent()` hace dedup por header `Idempotency-Key` en middleware — la
misma regla del `request_id` con otro nombre.

### Laravel — el molde ya es convención

```php
// app/Domain/TimeEntries/CreateEntryRequest.php — el contrato
class CreateEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('time.write');
    }

    public function rules(): array
    {
        return ['task' => 'required|string', 'minutes' => 'required|integer'];
    }
}
```

Form Request = contrato, Policy = matriz única, `app/Domain/` = módulos.

### Symfony — voter como matriz única

```php
// src/Domain/TimeEntries/EntryController.php
#[Route('/api/time-entries', methods: ['POST'])]
#[IsGranted('TIME_WRITE')]
public function create(#[MapRequestPayload] NewEntry $dto): JsonResponse
{
    $entry = $this->entries->create($dto); // dominio propio
    return $this->json($entry, 201);
}
```

`#[IsGranted]` lee el voter — la misma función `arc_can()` con otra sintaxis.
El DTO validado es el schema; el `400` automático es la forma de error.

### Go — el compilador hace cumplir el límite

```go
// internal/entries/handler.go — internal/ impone la frontera
func (h *Handler) Create(w http.ResponseWriter, r *http.Request) {
    if dup := h.dedup.Seen(r.Header.Get("X-Request-Id")); dup != nil {
        writeJSON(w, http.StatusOK, dup) // reintento → misma respuesta
        return
    }
    // ...
}
```

Un package en `internal/` no se puede importar desde fuera — los límites del
módulo los enforcea el toolchain, no la disciplina.

### Plugin PHP (Composer) — contratos + eventos

```php
// src/Contracts/EntryStore.php — el contrato del paquete
interface EntryStore
{
    public function create(NewEntry $e, string $requestId): Entry;
}
```

```php
// el paquete se extiende por eventos PSR-14 — hooks con otro nombre
$dispatcher->dispatch(new EntryCreated($entry));
```

`src/Contracts/` es la API pública; los eventos son los hooks. El consumer
depende de interfaces, no de implementaciones — igual que en WordPress, pero
con tipos.

## Otros runtimes

### Astro — el schema es el contrato

```ts
const docs = defineCollection({
  schema: z.object({
    title: z.string(),
    description: z.string(),
    sidebar: z.string().optional(),
  }),
});
```

Los datos inválidos fallan en build — el contrato se verifica antes de
desplegar, no en runtime. Esta misma documentación corre sobre esa idea.

### MV3 — mensajes como contrato interno

```ts
// el popup nunca lee storage ajeno: pide por mensaje tipado
const entries = await chrome.runtime.sendMessage<Msg, Entry[]>({
  type: 'entries.list',
  payload: { day },
});
```

El service worker es el backend; el popup, una UI más que consume el
contrato.

> Mismo molde, once runtimes: dominio aislado, contrato explícito, mutación
> idempotente y una UI que solo consume.
