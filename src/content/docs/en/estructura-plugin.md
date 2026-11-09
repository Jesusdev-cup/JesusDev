---
title: Plugin skeleton
description: A yohacase plugin's base files — what each one does and why it exists.
sidebar: Plugin skeleton
---

This is the minimal mold for a WordPress plugin built with the method:

```
arc-<product>/
├── arc-<product>.php            # bootstrap: version, constants, hooks
├── uninstall.php                # reversible by design
├── includes/
│   ├── class-module.php         # registry: what exists, what loads
│   ├── class-schema.php         # schema version + migrations per domain
│   ├── class-security.php       # nonces, capabilities, rate limiting
│   ├── class-settings.php       # one options entry, typed getters
│   ├── class-diagnostics.php    # health snapshot: crons, versions, flags
│   ├── class-access-log.php     # audit trail
│   └── helpers.php              # shared pieces live once
├── api/
│   └── class-rest-<domain>.php  # one file per domain's routes
└── assets/
    ├── css/
    └── js/
```

## Rules of the mold

- **One file, one domain** — `class-time-entries.php` knows nothing about EOD;
  they talk via public functions or hooks.
- **The bootstrap only wires** — `arc-<product>.php` registers hooks and loads
  modules; it holds no business logic.
- **Schema self-migrates** — each domain stamps its version in an option and
  migrates on load. No "run this SQL by hand".
- **Diagnostics from day one** — `class-diagnostics.php` exposes system health
  before it is ever needed.
- **Reversible** — `uninstall.php` and feature flags: everything can be turned
  off or removed without leftovers.

## Contract first

Before the first UI line, `api/class-rest-<domain>.php` exists with the routes
declared: verb, path, permission and error shape. The UI comes later and only
consumes that contract.
