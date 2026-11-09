---
title: Esqueleto de un plugin
description: Los archivos base de un plugin yohacase — qué hace cada uno y por qué existe.
sidebar: Esqueleto de plugin
---

Este es el molde mínimo de un plugin de WordPress construido con el método:

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

## Reglas del molde

- **Un archivo, un dominio** — `class-time-entries.php` no sabe nada de EOD;
  se hablan por funciones públicas o hooks.
- **El bootstrap solo enchufa** — `arc-<product>.php` registra hooks y carga
  módulos; no contiene lógica de negocio.
- **Schema se auto-migra** — cada dominio estampa su versión en una option y
  migra al cargar. Nada de "corre este SQL a mano".
- **Diagnósticos desde el día uno** — `class-diagnostics.php` expone el health
  del sistema antes de que haga falta.
- **Reversible** — `uninstall.php` y feature flags: todo se puede apagar o
  desinstalar sin dejar restos.

## El contrato primero

Antes de la primera línea de UI, existe `api/class-rest-<domain>.php` con las
rutas declaradas: verbo, ruta, permiso y forma de error. La UI llega después y
solo consume ese contrato.
