---
title: Escalabilidad
description: Cómo crece un proyecto yohacase — por adición, no por edición. Escala de producto, datos, equipo y ecosistema.
sidebar: Escalabilidad
---

El método existe para que el proyecto número diez cueste menos que el
primero — y para que el módulo número veinte no rompa al uno.

## Las cuatro escalas

- **Producto** — cada feature nueva es un módulo nuevo enchufado por hooks,
  no un `if` más dentro de uno viejo.
- **Datos** — cada dominio versiona y migra su schema: la base crece sin
  rewrites ni estados desconocidos.
- **Equipo** — el molde es la documentación: cualquier archivo se encuentra
  sin preguntar; el checklist es el "done" compartido.
- **Ecosistema** — el segundo proyecto empieza con el 80% hecho: stack,
  helpers, karibase y el contrato ya existen.

## Crecer por adición, no por edición

La regla central de escala: un módulo nuevo **no modifica** módulos viejos —
se enchufa a sus hooks. Editar código estable para añadir features es la
escala que se paga con bugs.

```php
// el módulo nuevo se suscribe, no edita
add_action('arc/eod/before_generate', [$reports, 'snapshot']);
```

## El contrato escala los clientes

El contrato REST declarado primero permite que nazcan clientes sin tocar el
backend: la extensión MV3, los flujos de n8n y un futuro mobile consumen el
mismo contrato. Escalar clientes = cero cambios en el dominio.

## Performance sin magia

- **Lectura** — caché stale-while-revalidate: datos frescos sin bloquear.
- **Escritura** — colas con dedup: la idempotencia ya estaba, la cola solo
  la usa.
- **Consultas** — cada módulo indexa las columnas que consulta, en su propia
  migración.

## Cuándo NO escalar

- **Microservicios prematuros** — un monolito modular bien delimitado escala
  más barato que cinco servicios mal repartidos.
- **Abstracciones "por si acaso"** — la regla de dos decide cuándo extraer,
  nunca antes.
- **Reescritas** — si un dominio duele, se envuelve; no se tira el sistema.

> La escala no se diseña al inicio — se garantiza con límites. Los límites
> son el pilar 2.
