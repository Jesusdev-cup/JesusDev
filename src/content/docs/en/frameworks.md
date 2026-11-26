---
title: Across frameworks and languages
description: How yohacase translates outside WordPress — front-end, backend and other runtimes.
sidebar: Other frameworks
---

The pillars are agnostic: what changes is the equivalent of "module", "hook"
and "contract" in each ecosystem.

## Front-end (SPA)

### React

- Domain = feature folder (`features/time-entries/`) with its own hooks,
  components and services.
- Contract = TypeScript types + a single `api/` layer; the UI never builds a
  loose fetch.
- Shared components = the design system — imported, never copied.

### Vue

- Domain = a composable per domain (`useEntries`) inside its feature folder.
- Contract = a typed `api/` layer; Pinia stores hold domain state only.
- The view consumes the composable — it never builds requests.

### Angular

- Domain = standalone feature with its injectable `Service` and signal state.
- Contract = domain-typed `HttpClient` + functional interceptors
  (`HttpInterceptorFn` via `withInterceptors`: auth, request id, error shape).
- DI is the ecosystem's "hook": modules plug in through providers.

### Svelte

- Domain = feature folder with its domain state (`entries.svelte.ts` using
  `$state` runes).
- Contract = typed `load` and `+server.ts` in SvelteKit — the contract lives
  in the route.
- Shared components in `lib/` — imported, never duplicated.

### Qwik

- Domain = feature with `routeLoader$` / `routeAction$` — the contract lives
  in the route itself.
- Schema = `zod$` attached to the action; validation errors arrive via
  `action.value.error`.
- Resumability = the same rule as Astro: JS only where it earns its place.
- `server$` is the domain's public function — the client never writes directly.

## Backend

### Node / Express

- Domain = a router per resource with its permission middleware and Zod schema.
- Idempotency = `Idempotency-Key` header with dedup in middleware.
- Observability = `/health` + structured logs per request id.

### Laravel

- The convention is already yohacase: `app/Domain/<Name>/`,
  Form Requests = the contract, Policies = the single permission matrix.
- Migrations per domain — every module migrates on its own.

### Symfony

- Domain = bounded context in `src/Domain/<Name>/` (or a bundle per domain).
- Contract = DTO + validation; voters are the single permission matrix.
- Messenger for crons and queues: retry, dedup and per-message logs — health
  included.

### Go

- Domain = a package in `internal/<domain>/` — the compiler enforces the
  boundary; another package cannot import it.
- Contract = domain interfaces + handlers with permission and idempotency
  middleware.
- One binary, one purpose — Unix applied to deployment.

### PHP plugin (Composer)

- Domain = `src/<Domain>/` per domain under PSR-4, each with its namespace.
- Contract = interfaces in `src/Contracts/` — consumers depend on interfaces,
  not concrete classes.
- Hooks = events (PSR-14) — the extension point WordPress taught the method.

## Other runtimes

### Astro

- Domain = typed content collection + a page per route.
- Contract = the collection's Zod schema — invalid data fails at build.
- Zero JS by default: islands exist only where they earn their place.

### MV3 extension

- Domain = service worker module + its own storage.
- Contract = typed `chrome.runtime` messages — the popup never reads another
  domain's storage.
- Minimal permissions in the manifest, always.

## What never changes

Whatever the language: single-responsibility domains, contract before UI,
one source of truth per rule, idempotent mutations and observability inside
the product.
