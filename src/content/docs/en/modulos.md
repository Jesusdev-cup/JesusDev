---
title: Anatomy of a module
description: What every domain module owns and how modules communicate.
sidebar: Anatomy of a module
---

Every domain module is a complete mini-product. It owns four things:

## What it owns

- **Its data** — its table, or the tables it touches. No other module writes them.
- **Its schema version** — it stamps it (e.g. `2.9.38`) and migrates itself.
- **Its routes** — its own REST endpoints with an explicit contract and permissions.
- **Its boundaries** — it never enters another module's domain.

## Canonical structure

```
arc-<product>/
├── includes/
│   ├── class-<domain>.php        # one file, one domain
│   ├── class-schema.php          # stamps its version, migrates itself
│   └── helpers.php               # shared pieces live once
├── api/
│   └── class-rest-<domain>.php   # routes with an explicit contract
├── assets/
└── module.php                    # wires everything through hooks
```

## Communication between modules

Modules do not import each other. They talk through:

- **Public functions** of the module that owns the data.
- **Hooks/filters** — the extension point WordPress taught the method.

Real example: in `arc-employee-time-clock`, `class-time-entries`,
`class-eod`, `class-security` and `class-cron` are separate domains; the EOD
report reads entries through the entries module's public API, never its table.
