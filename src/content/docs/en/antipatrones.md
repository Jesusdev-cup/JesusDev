---
title: Anti-patterns
description: What yohacase avoids by design — and why.
sidebar: Anti-patterns
---

- **Framework hopping** — the stack does not change per project; switching
  tools for fashion is debt with no upside.
- **Copy-paste across projects** — if a piece is used twice, it gets extracted
  into a shared component. Nothing is duplicated.
- **Magic automation** — a cron or flow without logs and health tracking is a
  failure waiting to happen silently.
- **Secrets in the repo** — no token, key or password ever touches version
  control. No exceptions.
- **Production first** — nothing touches production without passing through
  test. The order is always canonical → dev → test → prod.
- **Divergent copies** — a patch that only exists in one dev copy is a future
  bug. Everything returns to canonical.
