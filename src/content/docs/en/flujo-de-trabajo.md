---
title: The working flow
description: How the method applies day to day — the order of a feature, the rule of two and the definition of done.
sidebar: Working flow
---

The method does not live in the diagram — it lives in the order every feature
gets worked. This is that order.

## Order of a feature

1. **Contract** — route, schema and error shapes written first. No contract, no UI.
2. **Domain** — the class or module with its data, its logic and its public function.
3. **UI** — composes existing pieces and consumes the contract. Zero loose fetch.
4. **Verification** — E2E asserts on test before touching canonical.

The temptation is always to start with the UI "to see something". Resist it:
the UI is the last thing written and the first thing that changes.

## The rule of two

- A piece is born in the project that needs it — not designed "just in case".
- If a second project uses it, it migrates to the shared layer (karibase, helpers, lib).
- The copy left in the first project gets replaced by the import.

## New module or extension?

- **Same domain, same table** → extend the existing module.
- **Own data or own business rule** → new module with its schema.
- **In doubt** → new module: merging later is easy, splitting hurts.

## Definition of done

A feature is finished when:

- the contract is declared on the route itself — args, permission and error shape;
- the mutation accepts a `request_id` and is rate-limited;
- at least one E2E assert covers it on the test environment;
- diagnostics expose it — version, flag and last cron run;
- it can be turned off with a flag without deploying.

## Adopting it in someone else's code

Do not rewrite — wrap:

1. Pick the domain that hurts the most.
2. Enclose its data behind a public function.
3. Extract the first duplicated rule into a single source.
4. Repeat with the next domain once the first one breathes.

> Every step leaves the system better than before. None requires a big bang.
