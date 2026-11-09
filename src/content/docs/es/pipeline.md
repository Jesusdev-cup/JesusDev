---
title: El pipeline
description: Las seis fases por las que pasa todo proyecto — de mapear a observar.
sidebar: El pipeline
---

```
Mapear → Contratar → Construir → Verificar → Desplegar → Observar
```

## Mapear

Entender el dominio antes de escribir código: actores, datos, flujos y reglas
de negocio. **Entregable:** mapa de módulos y contratos.

## Contratar

Definir rutas, schema y errores — el contrato que la UI y los clientes van a
consumir. **Entregable:** el contrato, antes de la UI.

## Construir

Módulos de dominio + componentes compartidos sobre el stack fijo.
**Entregable:** código que no se repite.

## Verificar

Suites QA end-to-end en entorno de test: permisos, flujos, errores — con
asserts, no a ojo. **Entregable:** asserts verdes, no "parece que funciona".

## Desplegar

Del árbol canónico a dev, a test y solo entonces a producción.
**Entregable:** cada paso reversible.

## Observar

Diagnósticos, health de crons y audit logs dentro del producto.
**Entregable:** el sistema se explica solo.
