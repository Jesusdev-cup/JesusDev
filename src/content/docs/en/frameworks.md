---
title: Other frameworks & languages
description: How yohacase translates outside WordPress — React, Astro, Node, Laravel and more.
sidebar: Other frameworks
---

The pillars are agnostic: what changes is the equivalent of "module", "hook"
and "contract" in each ecosystem.

## React

- Domain = feature folder (`features/time-entries/`) with its own hooks,
  components and services.
- Contract = TypeScript types + a single `api/` layer; the UI never issues a
  loose fetch.
- Shared components = the design system — imported, never copied.

## Astro

- Domain = typed content collection + page per route.
- Contract = the collection's Zod schema — invalid data fails at build.
- Zero JS by default: islands exist only where they earn it.

## Node / Express

- Domain = router per resource with its permission middleware and Zod schema.
- Idempotency = `Idempotency-Key` header with middleware dedup.
- Observability = `/health` + structured logs per request_id.

## Laravel

- The convention is already yohacase's: `includes/` ↔ `app/Domain/<Name>/`,
  Form Requests = the contract, Policies = the single permission matrix.
- Per-domain migrations — each module migrates itself.

## MV3 extension / others

- Domain = service worker module + its own storage.
- Contract = typed `chrome.runtime` messages — the popup never reads another
  module's storage.
- Minimal manifest permissions, always.

## What never changes

Whatever the language: single-responsibility domains, contract before UI, one
source of truth per rule, idempotent mutations and observability inside the
product.
