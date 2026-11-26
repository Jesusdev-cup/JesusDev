---
title: El flujo de trabajo
description: Cómo se aplica el método en el día a día — orden de una feature, la regla de dos y la definición de done.
sidebar: Flujo de trabajo
---

El método no vive en el diagrama — vive en el orden en que se trabaja cada
feature. Este es ese orden.

## Orden de una feature

1. **Contrato** — ruta, schema y errores escritos primero. Sin contrato no hay UI.
2. **Dominio** — la clase o módulo con sus datos, su lógica y su función pública.
3. **UI** — compone piezas existentes y consume el contrato. Cero fetch suelto.
4. **Verificación** — asserts E2E en test antes de tocar el canónico.

La tentación siempre es empezar por la UI "para ver algo". Se resiste: la UI
es lo último que se escribe y lo primero que cambia.

## La regla de dos

- Una pieza nace en el proyecto que la necesita — no se diseña "por si acaso".
- Si un segundo proyecto la usa, migra al compartido (karibase, helpers, lib).
- La copia que quedó en el primer proyecto se reemplaza por la importación.

## ¿Módulo nuevo o extensión?

- **Mismo dominio, misma tabla** → extensión del módulo existente.
- **Datos propios o regla de negocio propia** → módulo nuevo con su schema.
- **En duda** → módulo nuevo: unir después es fácil, separar duele.

## Definición de done

Una feature está terminada cuando:

- el contrato está declarado en la ruta misma — args, permiso y forma de error;
- la mutación acepta `request_id` y tiene rate limit;
- al menos un assert E2E la cubre en el entorno de test;
- los diagnósticos la muestran — versión, flag y último cron;
- puede apagarse con un flag sin desplegar.

## Adoptarlo en código ajeno

No se reescribe — se envuelve:

1. Elegir el dominio que más duele.
2. Encerrar sus datos detrás de una función pública.
3. Extraer la primera regla duplicada a fuente única.
4. Repetir con el siguiente dominio cuando el primero ya respira.

> Cada paso deja el sistema mejor que antes. Ninguno exige un big bang.
