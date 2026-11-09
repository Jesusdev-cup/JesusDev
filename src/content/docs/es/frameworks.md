---
title: En otros frameworks y lenguajes
description: Cómo se traduce yohacase fuera de WordPress — React, Astro, Node, Laravel y más.
sidebar: Otros frameworks
---

Los pilares son agnósticos: lo que cambia es el equivalente de "módulo",
"hook" y "contrato" en cada ecosistema.

## React

- Dominio = carpeta de feature (`features/time-entries/`) con sus hooks,
  componentes y servicios propios.
- Contrato = tipos TypeScript + capa `api/` única; la UI nunca arma fetch suelto.
- Componentes compartidos = el design system — se importa, no se copia.

## Astro

- Dominio = colección de contenido tipada + página por ruta.
- Contrato = schema Zod de la colección — los datos inválidos fallan en build.
- Cero JS por defecto: los islands existen solo donde aportan.

## Node / Express

- Dominio = router por recurso con su middleware de permisos y su schema Zod.
- Idempotencia = header `Idempotency-Key` con dedup en middleware.
- Observabilidad = `/health` + logs estructurados por request_id.

## Laravel

- La convención ya es del propio yohacase: `includes/` ↔ `app/Domain/<Name>/`,
  Form Requests = el contrato, Policies = la matriz de permisos única.
- Migrations por dominio — cada módulo migra solo.

## Extensión MV3 / otros

- Dominio = módulo del service worker + su storage propio.
- Contrato = `chrome.runtime` messages tipadas — el popup nunca lee storage ajeno.
- Permisos mínimos en el manifest, siempre.

## Lo que nunca cambia

Sea cual sea el lenguaje: dominios de responsabilidad única, contrato antes
que UI, una fuente de verdad por regla, mutaciones idempotentes y
observabilidad dentro del producto.
