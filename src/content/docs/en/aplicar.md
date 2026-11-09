---
title: Applying the method
description: How to use yohacase per project type — plugin, web platform, extension or automation.
sidebar: How to apply it
---

yohacase does not change per project — what changes is **where it lands**. The
mold is the same; the runtime varies.

## WordPress plugin

The fullest case: domains as classes, hooks as the internal contract, REST as
the external one.

- One domain = one `class-<domain>.php` with its data and its schema.
- The permission matrix lives in a single function — UI and API read the same.
- Crons with health tracking: every run records duration and result.

## Web platform / intranet

The web is a plugin case: every portal module is a domain (tickets, documents,
onboarding, inbox). The front-end only consumes the REST contract — it never
touches another domain's tables.

## Browser extension (MV3)

- The service worker is the "backend": offline queue, `request_id` dedup,
  stale-while-revalidate cache.
- Minimal permissions declared in the manifest — the extension asks only for
  what it uses.
- It reuses the portal session: zero extra login.

## Static site (Astro)

- HTML first; JavaScript only where it earns its place.
- Content in typed collections — data has a contract too.
- Pages compose components; no monolithic pages.

## Automation (n8n / Make / Apps Script)

- Every flow records health, duration and errors — no magic automation.
- Flows call the contract endpoints; they never write data from behind.
