---
title: Ejemplos en frameworks web
description: El mismo molde aplicado con código — React, Vue, Angular, Svelte, Qwik, Express, Laravel, Symfony, Go y PHP.
sidebar: Ejemplos web
---

El molde no cambia — cambia el equivalente de "módulo", "contrato" y "hook"
en cada runtime. El mismo dominio `time-entries`, doce veces.

## Front-end

### React — feature + contrato tipado

> Ejemplo funcional: [`public/examples/web/react/`](/JesusDev/examples/web/react/README.md) — componentes, contexto, hooks, test.

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

> Ejemplo funcional: [`public/examples/web/vue/`](/JesusDev/examples/web/vue/README.md) — composable, provide/inject, router, test.

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

> Ejemplo funcional: [`public/examples/web/angular/`](/JesusDev/examples/web/angular/README.md) — los 12 temas oficiales: signal forms, interceptor, routing, SSR, Aria, i18n, animaciones, test.

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

> Ejemplo funcional: [`public/examples/web/svelte/`](/JesusDev/examples/web/svelte/README.md) — runes, SvelteKit `load` + form actions, test.

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

> Ejemplo funcional: [`public/examples/web/qwik/`](/JesusDev/examples/web/qwik/README.md) — `routeLoader$`, `routeAction$` + `zod$`, `server$`, componentes.

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

> Ejemplo funcional: [`public/examples/web/express/`](/JesusDev/examples/web/express/README.md) — dominio puro, middleware chain, error boundary.

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

> Ejemplo funcional: [`public/examples/web/laravel/`](/JesusDev/examples/web/laravel/README.md) — Form Request, Policy, middleware idempotencia, migration, feature test.

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

> Ejemplo funcional: [`public/examples/web/symfony/`](/JesusDev/examples/web/symfony/README.md) — `#[IsGranted]` + voter, `#[MapRequestPayload]` DTO, feature test.

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

> Ejemplo funcional: [`public/examples/web/go/`](/JesusDev/examples/web/go/README.md) — `internal/` + ServeMux + `httptest`, compilable con `go build`.

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

> Ejemplo funcional: [`public/examples/web/php-composer/`](/JesusDev/examples/web/php-composer/README.md) — PSR-4 + contratos + eventos PSR-14 + PHPUnit.

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

> Ejemplo funcional: [`public/examples/web/astro/`](/JesusDev/examples/web/astro/README.md) — colección + actions + endpoint + middleware + i18n.

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

> Ejemplo funcional: [`public/examples/web/mv3/`](/JesusDev/examples/web/mv3/README.md) — manifest, service worker, popup, mensajes tipados.

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
