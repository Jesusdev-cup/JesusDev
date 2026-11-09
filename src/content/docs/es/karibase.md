---
title: Karibase
description: El design system del método — cómo se usa y cómo se sincroniza con los plugins.
sidebar: Karibase
---

**Karibase** es el componente compartido por excelencia de yohacase: un
framework CSS propio especializado en **admin dashboards y bots flotantes**,
con sus plugins JS y un sitio de documentación en Astro.

Docs completas: [jesusdev-cup.github.io/karibase](https://jesusdev-cup.github.io/karibase/)

## Qué aporta

- **Utilidades + componentes** — grids, cards, forms, tablas y modales con un
  solo lenguaje visual en todos los productos.
- **Plugins JS** — bundles ESM y min para piezas interactivas sin framework.
- **Temas ARC** — `build-arc-themes.js` genera los bundles temáticos que usan
  los plugins de la familia arc.

## Cómo se aplica en un proyecto

1. El plugin carga `karibase-wp.php`, el wrapper que registra los assets.
2. Los paneles del plugin componen con las utilidades — nada de CSS suelto por
   pantalla.
3. Cuando una pantalla necesita una pieza nueva, nace en el plugin; si se usa
   dos veces, migra a karibase con `npm run sync:plugins`
   (`extract-plugin-styles.js` → `build-arc-themes.js`).

Así el pilar 3 se cumple literalmente: la pieza existe una vez en karibase y
cada proyecto la compone — nunca se duplica entre plugins.
