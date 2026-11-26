---
title: Ejemplos en frameworks web
description: El mismo molde aplicado con código — React, Vue, Angular, Svelte, Qwik, Express, Laravel, Symfony, Go y PHP.
sidebar: Ejemplos web
---

El molde no cambia — cambia el equivalente de "módulo", "contrato" y "hook"
en cada runtime. El mismo dominio `time-entries`, doce veces.

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

Un interceptor funcional global añade auth y normaliza la forma de error —
igual que `permission_callback` + el error del contrato en WordPress:

```ts
// app.config.ts — se registra una vez, cubre todos los dominios
export const reqIdInterceptor: HttpInterceptorFn = (req, next) =>
  next(req.clone({ setHeaders: { 'X-Request-Id': crypto.randomUUID() } }));

provideHttpClient(withInterceptors([reqIdInterceptor]));
```

### Svelte — estado por dominio con runes

```ts
// features/time-entries/entries.svelte.ts — la clase ES el dominio
class EntriesStore {
  list = $state<TimeEntry[]>([]);

  async create(e: NewEntry) {
    const entry = await api.post<TimeEntry>('/time-entries', e, {
      headers: { 'X-Request-Id': crypto.randomUUID() },
    });
    this.list.push(entry);
    return entry;
  }
}
export const entries = new EntriesStore();
```

El componente lee `entries.list` (reactivo por la runa `$state`) y llama
`entries.create()` — la mutación siempre pasa por el dominio, nunca por un
fetch improvisado.

### Qwik — el contrato vive en la ruta

```tsx
// routes/time-entries/index.tsx — la action es el contrato validado
export const useCreateEntry = routeAction$(
  async (data, requestEvent) => {
    // corre en servidor: dominio puro, mismo request id deduplicado
    const id = requestEvent.request.headers.get('X-Request-Id') ?? crypto.randomUUID();
    return entries.create(data, id);
  },
  zod$({ task: z.string(), minutes: z.number().int() }) // schema = contrato
);
```

`routeLoader$` y `routeAction$` son las rutas REST del runtime: `zod$` es el
schema del contrato, `action.value.error` la forma de error — el cliente
consume el contrato, nunca la tabla.

## Backend

### Node / Express — router + middleware idempotente

```ts
const newEntrySchema = z.object({ task: z.string(), minutes: z.number().int() });

router.post('/time-entries',
  requirePerm('time.write'),
  validate(newEntrySchema),   // el contrato: 400 con forma de error si falla
  idempotent(),               // dedup por header Idempotency-Key
  async (req, res) => {
    const entry = await entries.create(req.body);
    res.status(201).json(entry);
  });
```

Cadena de middleware = la declaración del contrato: permiso → schema →
idempotencia → dominio. `idempotent()` hace dedup por `Idempotency-Key` —
la misma regla del `request_id` con otro nombre.

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
// src/content.config.ts — la colección declara loader + schema
const docs = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/docs' }),
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

> Mismo molde, doce runtimes: dominio aislado, contrato explícito, mutación
> idempotente y una UI que solo consume.
