---
title: The pipeline
description: The six phases every project goes through — from mapping to observing.
sidebar: The pipeline
---

```
Map → Contract → Build → Verify → Ship → Observe
```

## Map

Understand the domain before writing code: actors, data, flows and business
rules. **Deliverable:** module map and contracts.

## Contract

Define routes, schema and errors — the contract that the UI and clients will
consume. **Deliverable:** the contract, before the UI.

## Build

Domain modules + shared components on the fixed stack.
**Deliverable:** code that does not repeat.

## Verify

End-to-end QA suites on a test environment: permissions, flows, errors — with
asserts, not eyeballs. **Deliverable:** green asserts, not "seems to work".

## Ship

From the canonical tree to dev, to test, and only then to production.
**Deliverable:** every step reversible.

## Observe

Diagnostics, cron health and audit logs inside the product.
**Deliverable:** the system explains itself.
