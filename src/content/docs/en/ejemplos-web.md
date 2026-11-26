---
title: Examples across web frameworks
description: The same mold applied in code — React, Vue, Angular, Svelte, Qwik, Express, Laravel, Symfony, Go and PHP.
sidebar: Web examples
---

The mold does not change — what changes is the equivalent of "module",
"contract" and "hook" in each runtime. The same `time-entries` domain,
twelve times.

> Every example applies **maximum validation and maximum restriction**:
> `.strict()` schemas with full bounds (task 1-255, minutes 1-1440),
> bounded bodies, mandatory content-type, real permissions (401 vs 403),
> rate limiting, security headers and least privilege in each runtime.
> Each `README.md` lists the concrete restrictions applied.

## Front-end

### React — feature + typed contract

> Working example: [`public/examples/web/react/`](/JesusDev/examples/web/react/README.md) — components, context, hooks, test.

```
src/
├── features/time-entries/
│   ├── api.ts            # the only file that talks to the backend
│   ├── hooks.ts
│   └── components/
└── shared/ui/            # imported, never copied
```

```ts
// features/time-entries/api.ts — the UI never builds a loose fetch
export const createEntry = (body: NewEntry, requestId: string) =>
  api.post<TimeEntry>('/time-entries', body, {
    headers: { 'X-Request-Id': requestId },
  });
```

### Vue — a composable per domain

> Working example: [`public/examples/web/vue/`](/JesusDev/examples/web/vue/README.md) — composable, provide/inject, router, test.

```ts
// features/time-entries/useEntries.ts — the domain lives in a composable
export function useEntries() {
  const list = ref<TimeEntry[]>([]);
  const create = (e: NewEntry) =>
    api.post('/time-entries', e, {
      headers: { 'X-Request-Id': crypto.randomUUID() },
    });
  return { list, create }; // the view consumes, it never builds requests
}
```

The composable is the module: it owns the domain state and exposes public
functions. Another domain never touches `list` — it asks through the
function.

### Angular — a service per domain + interceptor

> Working example: [`public/examples/web/angular/`](/JesusDev/examples/web/angular/README.md) — all 12 official topics: signal forms, interceptor, routing, SSR, Aria, i18n, animations, test.

```ts
// features/time-entries/entries.service.ts — the domain is a service
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

A global functional interceptor adds auth and normalizes the error shape —
same as `permission_callback` + the contract error in WordPress:

```ts
// app.config.ts — registered once, covers every domain
export const reqIdInterceptor: HttpInterceptorFn = (req, next) =>
  next(req.clone({ setHeaders: { 'X-Request-Id': crypto.randomUUID() } }));

provideHttpClient(withInterceptors([reqIdInterceptor]));
```

### Svelte — domain state with runes

> Working example: [`public/examples/web/svelte/`](/JesusDev/examples/web/svelte/README.md) — runes, SvelteKit `load` + form actions, test.

```ts
// features/time-entries/entries.svelte.ts — the class IS the domain
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

The component reads `entries.list` (reactive via the `$state` rune) and
calls `entries.create()` — every mutation goes through the domain, never
through an ad-hoc fetch.

### Qwik — the contract lives in the route

> Working example: [`public/examples/web/qwik/`](/JesusDev/examples/web/qwik/README.md) — `routeLoader$`, `routeAction$` + `zod$`, `server$`, components.

```tsx
// routes/time-entries/index.tsx — the action is the validated contract
export const useCreateEntry = routeAction$(
  async (data, requestEvent) => {
    // runs on the server: pure domain, same request id deduplicated
    const id = requestEvent.request.headers.get('X-Request-Id') ?? crypto.randomUUID();
    return entries.create(data, id);
  },
  zod$({ task: z.string(), minutes: z.number().int() }) // schema = contract
);
```

`routeLoader$` and `routeAction$` are the runtime's REST routes: `zod$` is
the contract schema, `action.value.error` the error shape — the client
consumes the contract, never the table.

## Backend

### Node / Express — router + idempotent middleware

> Working example: [`public/examples/web/express/`](/JesusDev/examples/web/express/README.md) — pure domain, middleware chain, error boundary.

```ts
const newEntrySchema = z.object({ task: z.string(), minutes: z.number().int() });

router.post('/time-entries',
  requirePerm('time.write'),
  validate(newEntrySchema),   // the contract: 400 with error shape on failure
  idempotent(),               // dedup by Idempotency-Key header
  async (req, res) => {
    const entry = await entries.create(req.body);
    res.status(201).json(entry);
  });
```

The middleware chain is the contract declaration: permission → schema →
idempotency → domain. `idempotent()` dedupes by `Idempotency-Key` — the
same `request_id` rule under a different name.

### Laravel — the mold is the convention

> Working example: [`public/examples/web/laravel/`](/JesusDev/examples/web/laravel/README.md) — Form Request, Policy, idempotency middleware, migration, feature test.

```php
// app/Domain/TimeEntries/CreateEntryRequest.php — the contract
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

Form Request = contract, Policy = single matrix, `app/Domain/` = modules.

### Symfony — the voter as the single matrix

> Working example: [`public/examples/web/symfony/`](/JesusDev/examples/web/symfony/README.md) — `#[IsGranted]` + voter, `#[MapRequestPayload]` DTO, feature test.

```php
// src/Domain/TimeEntries/EntryController.php
#[Route('/api/time-entries', methods: ['POST'])]
#[IsGranted('TIME_WRITE')]
public function create(#[MapRequestPayload] NewEntry $dto): JsonResponse
{
    $entry = $this->entries->create($dto); // own domain
    return $this->json($entry, 201);
}
```

`#[IsGranted]` reads the voter — the same `arc_can()` function in a different
syntax. The validated DTO is the schema; the automatic `400` is the error
shape.

### Go — the compiler enforces the boundary

> Working example: [`public/examples/web/go/`](/JesusDev/examples/web/go/README.md) — `internal/` + ServeMux + `httptest`, compiles with `go build`.

```go
// internal/entries/handler.go — internal/ enforces the frontier
func (h *Handler) Create(w http.ResponseWriter, r *http.Request) {
    if dup := h.dedup.Seen(r.Header.Get("X-Request-Id")); dup != nil {
        writeJSON(w, http.StatusOK, dup) // retry → same response
        return
    }
    // ...
}
```

A package under `internal/` cannot be imported from outside — the module
boundaries are enforced by the toolchain, not by discipline.

### PHP plugin (Composer) — contracts + events

> Working example: [`public/examples/web/php-composer/`](/JesusDev/examples/web/php-composer/README.md) — PSR-4 + contracts + PSR-14 events + PHPUnit.

```php
// src/Contracts/EntryStore.php — the package contract
interface EntryStore
{
    public function create(NewEntry $e, string $requestId): Entry;
}
```

```php
// the package extends through PSR-14 events — hooks by another name
$dispatcher->dispatch(new EntryCreated($entry));
```

`src/Contracts/` is the public API; the events are the hooks. The consumer
depends on interfaces, not implementations — same as WordPress, but typed.

## Other runtimes

### Astro — the schema is the contract

> Working example: [`public/examples/web/astro/`](/JesusDev/examples/web/astro/README.md) — collection + actions + endpoint + middleware + i18n.

```ts
// src/content.config.ts — the collection declares loader + schema
const docs = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/docs' }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    sidebar: z.string().optional(),
  }),
});
```

Invalid data fails at build time — the contract gets verified before
deploying, not at runtime. This very documentation runs on that idea.

### MV3 — messages as the internal contract

> Working example: [`public/examples/web/mv3/`](/JesusDev/examples/web/mv3/README.md) — manifest, service worker, popup, typed messages.

```ts
// the popup never reads another domain's storage: it asks via typed message
const entries = await chrome.runtime.sendMessage<Msg, Entry[]>({
  type: 'entries.list',
  payload: { day },
});
```

The service worker is the backend; the popup is just another UI consuming
the contract.

> Same mold, twelve runtimes: isolated domain, explicit contract, idempotent
> mutation and a UI that only consumes.
