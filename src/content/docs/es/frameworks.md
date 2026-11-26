---
title: En otros frameworks y lenguajes
description: Cómo se traduce yohacase fuera de WordPress — front-end, backend y otros runtimes.
sidebar: Otros frameworks
---

Los pilares son agnósticos: lo que cambia es el equivalente de "módulo",
"hook" y "contrato" en cada ecosistema.

## Front-end (SPA)

### React

- Dominio = carpeta de feature (`features/time-entries/`) con sus hooks,
  componentes y servicios propios.
- Contrato = tipos TypeScript + capa `api/` única; la UI nunca arma fetch suelto.
- Componentes compartidos = el design system — se importa, no se copia.

### Vue

- Dominio = composable por dominio (`useEntries`) dentro de su feature folder.
- Contrato = capa `api/` tipada; las Pinia stores guardan solo estado del dominio.
- La vista consume el composable — nunca construye requests.

### Angular

- Dominio = feature standalone con su `Service` inyectable y su estado en signals.
- Contrato = `HttpClient` tipado por dominio + interceptors funcionales
  (`HttpInterceptorFn` en `withInterceptors`: auth, request id, error shape).
- La DI es el "hook" del ecosistema: los módulos se enchufan por providers.

### Svelte

- Dominio = feature folder con su estado por dominio (`entries.svelte.ts`
  con runas `$state`).
- Contrato = `load` y `+server.ts` tipados en SvelteKit — el contrato vive en
  la ruta.
- Componentes compartidos en `lib/` — se importan, no se duplican.

### Qwik

- Dominio = feature con `routeLoader$` / `routeAction$` — el contrato está en
  la ruta misma.
- Schema = `zod$` adjunto a la action; errores de validación llegan por
  `action.value.error`.
- Resumibilidad = la misma regla de Astro: JS solo donde aporta.
- `server$` es la función pública del dominio — el cliente nunca escribe directo.

## Backend

### Node / Express

- Dominio = router por recurso con su middleware de permisos y su schema Zod.
- Idempotencia = header `Idempotency-Key` con dedup en middleware.
- Observabilidad = `/health` + logs estructurados por request id.

### Laravel

- La convención ya es del propio yohacase: `app/Domain/<Name>/`,
  Form Requests = el contrato, Policies = la matriz de permisos única.
- Migrations por dominio — cada módulo migra solo.

### Symfony

- Dominio = bounded context en `src/Domain/<Name>/` (o bundle por dominio).
- Contrato = DTO + validación; los voters son la matriz de permisos única.
- Messenger para crons y colas: retry, dedup y logs por mensaje — health incluido.

### Go

- Dominio = package en `internal/<domain>/` — el compilador hace cumplir el
  límite; otro package no puede importarlo.
- Contrato = interfaces del dominio + handlers con middleware de permiso e
  idempotencia.
- Un binario, un propósito — Unix aplicado al deploy.

### Plugin PHP (Composer)

- Dominio = `src/<Domain>/` por dominio bajo PSR-4, cada uno con su namespace.
- Contrato = interfaces en `src/Contracts/` — los consumers dependen de
  interfaces, no de clases concretas.
- Hooks = eventos (PSR-14) — el punto de extensión que WordPress enseñó al método.

## Otros runtimes

### Astro

- Dominio = colección de contenido tipada + página por ruta.
- Contrato = schema Zod de la colección — los datos inválidos fallan en build.
- Cero JS por defecto: los islands existen solo donde aportan.

### Extensión MV3

- Dominio = módulo del service worker + su storage propio.
- Contrato = `chrome.runtime` messages tipadas — el popup nunca lee storage ajeno.
- Permisos mínimos en el manifest, siempre.

## Lo que nunca cambia

Sea cual sea el lenguaje: dominios de responsabilidad única, contrato antes
que UI, una fuente de verdad por regla, mutaciones idempotentes y
observabilidad dentro del producto.
