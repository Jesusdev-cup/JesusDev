---
title: Checklist de adopción
description: Siete preguntas que determinan si un proyecto es yohacase — y cómo adoptarlo.
sidebar: Checklist
---

## Las siete preguntas

1. ¿Identifiqué los dominios y qué datos posee cada uno?
2. ¿El contrato REST + schema existe antes de escribir la UI?
3. ¿Cada regla de negocio vive en un solo lugar?
4. ¿Toda mutación acepta `request_id` y tiene rate limit?
5. ¿Hay una suite QA que corre en test antes de prod?
6. ¿El release se puede apagar sin desplegar?
7. ¿Diagnósticos y audit logs incluidos desde el día uno?

> Si las 7 son sí → es yohacase.

## Cómo adoptarlo

- **Proyecto nuevo** — empieza por Mapear: nombra los dominios, dibuja el
  contrato y sólo entonces escribe el primer módulo.
- **Proyecto existente** — no reescribas: envuelve un dominio por vez. Extrae
  la primera regla duplicada a una función única y ya estás aplicando el pilar 3.
- **En equipo** — el checklist es la definición de "done": un PR que incumple
  una pregunta no se mergea.
