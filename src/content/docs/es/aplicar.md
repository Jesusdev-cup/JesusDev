---
title: Cómo aplicar el método
description: Cómo usar yohacase según el tipo de proyecto — plugin, plataforma web, extensión o automatización.
sidebar: Cómo aplicarlo
---

yohacase no cambia según el proyecto — cambia **dónde aterriza**. El molde es
el mismo; lo que varía es el runtime.

## Plugin de WordPress

El caso más completo: dominios como clases, hooks como contrato interno, REST
como contrato externo.

- Un dominio = un `class-<dominio>.php` con sus datos y su schema.
- La matriz de permisos vive en una función única — UI y API leen la misma.
- Crons con health tracking: cada ejecución registra duración y resultado.

## Plataforma web / intranet

La web es un caso del plugin: cada módulo del portal es un dominio (tickets,
documentos, onboarding, inbox). El front-end consume solo el contrato REST —
nunca toca la base de datos del otro dominio.

## Extensión de navegador (MV3)

- El service worker es el "backend": cola offline, dedup por `request_id`,
  caché stale-while-revalidate.
- Permisos mínimos declarados en el manifest — la extensión pide solo lo que usa.
- Reutiliza la sesión del portal: cero login extra.

## Sitio estático (Astro)

- HTML primero; JavaScript solo donde aporta.
- Contenido en colecciones tipadas — los datos también tienen contrato.
- Cada página compone componentes; nada de páginas monolíticas.

## Automatización (n8n / Make / Apps Script)

- Todo flujo registra health, duración y errores — ninguna automatización es magia.
- Los flujos llaman a los endpoints del contrato; no escriben datos por detrás.
