---
title: Anti-patrones
description: Lo que yohacase evita por diseño — y por qué.
sidebar: Anti-patrones
---

- **Saltar de framework** — el stack no cambia por proyecto; cambiar
  herramientas por moda es deuda que no aporta.
- **Copy-paste entre proyectos** — si una pieza se usa dos veces, se extrae al
  componente compartido. Nada se duplica.
- **Automatización mágica** — un cron o flujo sin logs ni health tracking es un
  fallo esperando a ocurrir en silencio.
- **Secretos en el repo** — ningún token, clave o contraseña toca el control de
  versiones. Sin excepciones.
- **Producción primero** — nada toca producción sin pasar por test. El orden
  siempre es canonical → dev → test → prod.
- **Copias divergentes** — un parche que solo existe en una copia de desarrollo
  es un bug futuro. Todo vuelve al canónico.
