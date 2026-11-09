---
title: Los tres pilares
description: Stack fijo, dominios modulares y componentes reutilizables — la base del método.
sidebar: Los tres pilares
---

Todo proyecto yohacase descansa sobre tres pilares. Si falta uno, no es yohacase.

## 1. Stack fijo

El mismo conjunto de herramientas en cada proyecto. Conocer el stack de memoria
vale más que perseguir el framework de moda: menos sorpresas, mejor código.

La regla de crecimiento: el stack solo crece cuando una herramienta demuestra
que resuelve algo que las actuales no resuelven bien. Nunca por hype.

## 2. Dominios modulares

Cada sistema se divide en **módulos de dominio de responsabilidad única**.
Un módulo posee sus datos, su versión de schema y sus rutas — y nunca entra en
el dominio de otro. Se comunican por funciones públicas o por hooks.

Bajo acoplamiento → fácil de probar, fácil de extender, fácil de borrar.

## 3. Componentes reutilizables

Las piezas compartidas viven **una vez** y se componen en todos lados:

- Un design system propio (karibase).
- Helpers compartidos que corren igual en AJAX y en REST.
- Una sola fuente de verdad por regla de negocio.

> Si una pieza se usa dos veces, se extrae. Nada se duplica.
