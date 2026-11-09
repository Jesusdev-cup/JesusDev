---
title: The fixed stack
description: The method's tools, layer by layer, and the rule for adding new ones.
sidebar: The fixed stack
---

The stack does not change per project. This is the full list:

| Layer | Tools |
|---|---|
| Backend / CMS | WordPress · PHP 8 · REST API |
| Front-end | Vanilla JS · React · Tailwind |
| Data | MySQL · SQLite · Supabase · PocketBase |
| Extension | Chrome MV3 · service workers |
| Sites | Astro (static output) |
| Design | Karibase (in-house design system) |
| Automation | n8n · Make · Google Apps Script |

## Why fixed

- **Real speed** — there is no "evaluate technologies" phase per project.
- **Depth > breadth** — the bugs of a known stack already happened.
- **Portable code** — what I learn in one project applies to the next.

## Growth rule

A tool enters the stack only if it:

1. Solves something the current ones do not solve well.
2. Survives a pilot project without regrets.
3. Does not break the observability rule nor the least-privilege one.
