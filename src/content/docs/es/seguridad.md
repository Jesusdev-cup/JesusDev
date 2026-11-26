---
title: Seguridad en capas
description: Cómo se aplica la seguridad en cada capa del método — del request a la base de datos, sin capa final improvisada.
sidebar: Seguridad
---

La seguridad no es una capa que se añade al final — es una decisión en cada
capa por la que viaja un request. Estas son las reglas, en ese orden.

## El request

- **Permiso en la ruta** — `permission_callback` lee la matriz única de
  permisos; ninguna ruta queda "pública por olvido".
- **Nonce + capability** — doble chequeo en acciones de admin; la API REST
  verifica capability, nunca confía en el front.
- **Rate limit por endpoint** — ventana por usuario o IP. La mutación
  idempotente no es excusa para aceptar spam.

## Los datos

- **Sanitizar a la entrada** — `sanitize_text_field`, `absint`, allowlists de
  enums. Lo que entra, entra limpio.
- **Escapar a la salida** — `esc_html`, `esc_attr`, `esc_url` según el
  contexto donde se imprime.
- **`$wpdb->prepare` siempre** — ni una query con concatenación. Ni una.

## Las credenciales

- **Nada en el repo** — tokens y claves viven en `wp-config`, variables de
  entorno o el gestor de secretos. El control de versiones nunca los ve.
- **Scopes mínimos** — OAuth pide solo lo que el flujo usa; el manifest de la
  extensión declara solo lo que necesita.
- **Rotación sin deploy** — las claves se cambian fuera del código.

## El rastro

- **Audit log en mutaciones** — quién, qué, cuándo y desde dónde. Todo cambio
  deja firma.
- **Diagnósticos sin secretos** — el health snapshot muestra flags, versiones
  y crons; jamás tokens ni datos personales.
- **Errores sin interior** — la forma de error del contrato expone código y
  mensaje; nunca stack traces ni SQL al cliente.
