---
title: Principios
description: Los seis principios que rigen cada decisión del método.
sidebar: Principios
---

## 01 — Canonical first

Existe un solo árbol canónico del código. Las copias de desarrollo se
sincronizan desde él — nunca divergen, nunca se parchan por separado.

## 02 — Contrato antes que código

Las rutas REST, el schema y los errores se definen primero. La UI consume el
contrato — nunca lo inventa.

## 03 — Una sola fuente de verdad

Cada regla de negocio vive en una función. La UI, la API y los diagnósticos
leen la misma — si cambia la regla, cambia en un solo sitio.

## 04 — Idempotente por defecto

Toda mutación lleva un `request_id`. Reintentar es gratis: la red puede fallar,
los datos no se duplican.

## 05 — Verificar de punta a punta

Nada llega a producción sin pasar por el entorno de test y una suite QA real —
permisos, OAuth, formularios, todo comprobado con asserts.

## 06 — Ship seguro

Feature flags, safe no-ops y shims de compatibilidad. Cada release puede
apagarse sin desplegar — romper producción no es una opción.
