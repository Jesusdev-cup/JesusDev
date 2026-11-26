---
title: Examples across web frameworks
description: The same mold applied in code — React, Vue, Angular, Svelte, Qwik, Express, Laravel, Symfony, Go and PHP.
sidebar: Web examples
---

The mold does not change — what changes is the equivalent of "module",
"contract" and "hook" in each runtime. The same `time-entries` domain,
eleven times.

## Front-end

### React — feature + typed contract

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

A global interceptor adds auth and normalizes the error shape — same as
`permission_callback` + the contract error in WordPress.

### Svelte — a store per domain

```ts
// features/time-entries/store.ts — the store is the domain
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

The component reads `$entries` and calls `entries.create()` — every mutation
goes through the domain, never through an ad-hoc fetch.

### Qwik — the contract lives in the route

```tsx
// routes/time-entries/index.tsx — loaders and actions are the contract
export const useCreateEntry = routeAction$(async (data, { request }) => {
  return entries.create(data); // runs on the server: pure domain
});
```

`routeLoader$` and `routeAction$` are the runtime's REST routes: the client
consumes the contract, never the table.

## Backend

### Node / Express — router + idempotent middleware

```ts
router.post('/time-entries',
  requirePerm('time.write'),
  idempotent(),
  async (req, res) => {
    const entry = await entries.create(req.body);
    res.status(201).json(entry);
  });
```

`idempotent()` dedupes by `Idempotency-Key` header in middleware — the same
`request_id` rule under a different name.

### Laravel — the mold is the convention

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

```ts
const docs = defineCollection({
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

```ts
// the popup never reads another domain's storage: it asks via typed message
const entries = await chrome.runtime.sendMessage<Msg, Entry[]>({
  type: 'entries.list',
  payload: { day },
});
```

The service worker is the backend; the popup is just another UI consuming
the contract.

> Same mold, eleven runtimes: isolated domain, explicit contract, idempotent
> mutation and a UI that only consumes.
