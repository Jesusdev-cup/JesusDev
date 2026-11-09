---
title: Projects & ecosystem
description: Where the method is applied in production and what each piece contributes.
sidebar: Projects
---

The method is not theory — these products run it in production:

- **arc-intranet** — domain modules (google, permissions, onboarding),
  permission matrix as the single source and built-in diagnostics.
- **arc-time-clock** — idempotent API with `request_id`, rate limiting,
  per-day drafts and an E2E suite.
- **arc-time-clock-extension** — MV3 with minimal permissions, offline queue
  with dedup and no extra login (it reuses the portal session).
- **arc-starter-templates** — remote on-demand payload, wizard with dry-run
  and reset — reversible by design.
- **karibase** — the shared component par excellence: design system + JS
  plugins reusable in any project.
- **lienzo-astra** — convention over configuration as a theme: everything is
  tuned from the Customizer, nothing hardcoded.

## Karibase, the central piece

Everything else composes on top of **karibase**: utilities, components and JS
plugins that live once. When a project needs a new piece, it is born in the
project and returns to karibase if used twice — that is how the ecosystem
grows without duplication.
