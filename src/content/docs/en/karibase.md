---
title: Karibase
description: The method's design system — how it is used and how it syncs into the plugins.
sidebar: Karibase
---

**Karibase** is yohacase's shared component par excellence: an in-house CSS
framework specialized in **admin dashboards and floating bots**, with its JS
plugins and an Astro docs site.

Full docs: [jesusdev-cup.github.io/karibase](https://jesusdev-cup.github.io/karibase/)

## What it provides

- **Utilities + components** — grids, cards, forms, tables and modals with one
  visual language across all products.
- **JS plugins** — ESM and min bundles for interactive pieces without a framework.
- **ARC themes** — `build-arc-themes.js` generates the themed bundles used by
  the arc plugin family.

## How it applies in a project

1. The plugin loads `karibase-wp.php`, the wrapper registering the assets.
2. Plugin panels compose with the utilities — no loose per-screen CSS.
3. When a screen needs a new piece, it is born in the plugin; used twice, it
   migrates to karibase via `npm run sync:plugins`
   (`extract-plugin-styles.js` → `build-arc-themes.js`).

That is pillar 3 made literal: the piece exists once in karibase and every
project composes it — never duplicated across plugins.
