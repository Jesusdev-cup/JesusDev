---
title: Scalability
description: How a yohacase project grows — by addition, not by editing. Scaling product, data, team and ecosystem.
sidebar: Scalability
---

The method exists so project number ten costs less than the first one — and
so module number twenty does not break module one.

## The four scales

- **Product** — every new feature is a new module plugged in through hooks,
  not one more `if` inside an old one.
- **Data** — every domain versions and migrates its own schema: the database
  grows without rewrites or unknown states.
- **Team** — the mold is the documentation: any file is found without
  asking; the checklist is the shared "done".
- **Ecosystem** — the second project starts 80% built: stack, helpers,
  karibase and the contract already exist.

## Grow by addition, not by editing

The central scaling rule: a new module **does not modify** old modules — it
plugs into their hooks. Editing stable code to add features is the kind of
scale you pay for with bugs.

```php
// the new module subscribes, it does not edit
add_action('arc/eod/before_generate', [$reports, 'snapshot']);
```

## The contract scales the clients

Declaring the REST contract first lets new clients be born without touching
the backend: the MV3 extension, n8n flows and a future mobile app all
consume the same contract. Scaling clients = zero domain changes.

## Performance without magic

- **Reads** — stale-while-revalidate cache: fresh data without blocking.
- **Writes** — queues with dedup: idempotency was already there, the queue
  just uses it.
- **Queries** — every module indexes the columns it queries, inside its own
  migration.

## When NOT to scale

- **Premature microservices** — a well-bounded modular monolith scales
  cheaper than five badly-split services.
- **"Just in case" abstractions** — the rule of two decides when to extract,
  never earlier.
- **Rewrites** — if a domain hurts, wrap it; do not throw the system away.

> Scale is not designed at the start — it is guaranteed by boundaries.
> Boundaries are pillar 2.
