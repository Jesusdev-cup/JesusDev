---
title: Anatomía de un módulo
description: Qué posee cada módulo de dominio y cómo se comunican entre sí.
sidebar: Anatomía de un módulo
---

Cada módulo de dominio es un mini-producto completo. Posee cuatro cosas:

## Lo que posee

- **Sus datos** — su tabla, o las tablas que le tocan. Ningún otro módulo las escribe.
- **Su versión de schema** — la estampa (ej. `2.9.38`) y migra sola.
- **Sus rutas** — endpoints REST propios con contrato explícito y permisos.
- **Sus límites** — nunca entra en el dominio de otro módulo.

## Estructura canónica

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

## Comunicación entre módulos

Los módulos no se importan entre sí. Se hablan por:

- **Funciones públicas** del módulo dueño del dato.
- **Hooks/filters** — el punto de extensión que WordPress enseñó al método.

Ejemplo real: en `arc-employee-time-clock`, `class-time-entries`,
`class-eod`, `class-security` y `class-cron` son dominios separados; el reporte
EOD lee entradas vía la API pública del módulo de entradas, nunca de su tabla.
