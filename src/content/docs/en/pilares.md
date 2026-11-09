---
title: The three pillars
description: Fixed stack, modular domains and reusable components — the foundation of the method.
sidebar: The three pillars
---

Every yohacase project rests on three pillars. If one is missing, it is not yohacase.

## 1. Fixed stack

The same toolset in every project. Knowing the stack by heart beats chasing
the trendy framework: fewer surprises, better code.

The growth rule: the stack only grows when a tool proves it solves something
the current ones do not solve well. Never because of hype.

## 2. Modular domains

Every system splits into **single-responsibility domain modules**. A module
owns its data, its schema version and its routes — and never enters another
module's domain. They communicate through public functions or hooks.

Low coupling → easy to test, easy to extend, easy to delete.

## 3. Reusable components

Shared pieces live **once** and get composed everywhere:

- An in-house design system (karibase).
- Shared helpers that run the same in AJAX and REST.
- A single source of truth per business rule.

> If a piece is used twice, it gets extracted. Nothing is duplicated.
