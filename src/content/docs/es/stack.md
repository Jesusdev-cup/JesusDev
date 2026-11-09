---
title: El stack fijo
description: Las herramientas del método, capa por capa, y la regla para añadir nuevas.
sidebar: El stack fijo
---

El stack no cambia por proyecto. Esta es la lista completa:

| Capa | Herramienta |
|---|---|
| Backend / CMS | WordPress · PHP 8 · REST API |
| Front-end | Vanilla JS · React · Tailwind |
| Datos | MySQL · SQLite · Supabase · PocketBase |
| Extensión | Chrome MV3 · service workers |
| Sitios | Astro (static output) |
| Diseño | Karibase (design system propio) |
| Automatización | n8n · Make · Google Apps Script |

## Por qué fijo

- **Velocidad real** — no hay fase de "evaluar tecnologías" por proyecto.
- **Profundidad > amplitud** — los bugs de un stack conocido ya ocurrieron.
- **Código portable** — lo que aprendo en un proyecto aplica al siguiente.

## Regla de crecimiento

Una herramienta entra al stack solo si:

1. Resuelve algo que las actuales no resuelven bien.
2. Sobrevive a un proyecto piloto sin arrepentimientos.
3. No rompe la regla de observabilidad ni la de permisos mínimos.
