---
title: Proyectos y ecosistema
description: Dónde está aplicado el método en producción y qué aporta cada pieza.
sidebar: Proyectos
---

El método no es teoría — estos productos lo corren en producción:

- **arc-intranet** — módulos por dominio (google, permissions, onboarding),
  matriz de permisos como fuente única y diagnósticos integrados.
- **arc-time-clock** — API idempotente con `request_id`, rate limiting, drafts
  por día y suite E2E.
- **arc-time-clock-extension** — MV3 con permisos mínimos, cola offline con
  dedup y cero login extra (reutiliza la sesión del portal).
- **arc-starter-templates** — payload remoto on-demand, wizard con dry-run y
  reset — reversible por diseño.
- **karibase** — el componente compartido por excelencia: design system +
  plugins JS reutilizables en cualquier proyecto.
- **lienzo-astra** — convención sobre configuración en forma de tema: todo se
  ajusta desde el Customizer, nada hardcodeado.

## Karibase, la pieza central

Todo lo demás compone sobre **karibase**: utilidades, componentes y plugins JS
que viven una vez. Cuando un proyecto necesita una pieza nueva, nace en el
proyecto y vuelve a karibase si se usa dos veces — así el ecosistema crece sin
duplicarse.
