# yohacase — cómo funciona

> **stack + módulos + componentes = producto mantenible**

**yohacase** es un método de trabajo, no un framework. Es la forma en que
construyo software: **arquitectura modular por dominios** +
**componentes reutilizables**, todo montado sobre un **stack fijo** que
conozco de memoria. Cada proyecto sale del mismo molde — por eso es
mantenible.

El nombre es un juego con *"your handcrafted case"*: cada proyecto es un
caso construido a mano con el mismo método.

---

## Los tres pilares

### 1. Stack fijo

El mismo conjunto de herramientas en cada proyecto. Conocer el stack de
memoria vale más que perseguir el framework de moda: menos sorpresas,
mejor código.

| Capa | Herramienta |
|---|---|
| Backend / CMS | WordPress · PHP 8 · REST API |
| Front-end | Vanilla JS · React · Tailwind |
| Datos | MySQL · SQLite · Supabase · PocketBase |
| Extensión | Chrome MV3 · service workers |
| Sitios | Astro (static output) |
| Diseño | Karibase (design system propio) |
| Automatización | n8n · Make · Google Apps Script |

Regla: el stack solo crece cuando una herramienta demuestra que resuelve
algo que las actuales no resuelven bien.

### 2. Dominios modulares

Cada sistema se divide en **módulos de dominio de responsabilidad
única**. Cada módulo posee:

- Sus **datos** y su tabla (o las tablas que le tocan).
- Su **versión de schema** — estampa y migra solo (ej. schema `2.9.38`).
- Sus **rutas** REST y sus permisos.

Un módulo nunca entra en el dominio de otro: se comunican por funciones
públicas o por hooks. Bajo acoplamiento → fácil de probar, fácil de
extender, fácil de borrar.

En la práctica: `arc-employee-time-clock` separa `class-time-entries`,
`class-eod`, `class-security`, `class-cron`… cada archivo un dominio.

### 3. Componentes reutilizables

Las piezas compartidas viven **una vez** y se componen en todos lados:

- **Karibase** — el design system: utilidades, componentes, plugins JS.
- **Helpers compartidos** — ej. `normalize_entry_data()` corre igual en
  AJAX y en REST.
- **Una sola fuente de verdad** — ej. `access_matrix()` alimenta la UI
  del admin *y* el endpoint de diagnósticos. Cambia la regla una vez,
  cambia en todas partes.

Si una pieza se usa dos veces, se extrae. Nada se duplica.

---

## Los seis principios

| # | Principio | En la práctica |
|---|---|---|
| 01 | **Canonical first** | Un solo árbol canónico del código. Las copias dev se sincronizan desde él — nunca divergen. |
| 02 | **Contrato antes que código** | Rutas REST, schema y errores se definen primero. La UI consume el contrato, no lo inventa. |
| 03 | **Una sola fuente de verdad** | Cada regla de negocio vive en una función. UI, API y diagnósticos leen la misma. |
| 04 | **Idempotente por defecto** | Toda mutación lleva `request_id`. Reintentar es gratis; los datos no se duplican. |
| 05 | **Verificar de punta a punta** | Nada va a producción sin entorno de test + suite QA real con asserts. |
| 06 | **Ship seguro** | Feature flags, safe no-ops, shims de compatibilidad. Toda release se puede apagar sin desplegar. |

---

## El pipeline

```
Mapear → Contratar → Construir → Verificar → Desplegar → Observar
```

| Fase | Qué pasa | Entregable |
|---|---|---|
| **Mapear** | Entender el dominio: actores, datos, flujos, reglas. | Mapa de módulos y contratos. |
| **Contratar** | Definir rutas, schema, errores. | El contrato (antes de la UI). |
| **Construir** | Módulos de dominio + componentes compartidos. | Código que no se repite. |
| **Verificar** | Suites E2E en test: permisos, flujos, errores. | Asserts verdes, no "parece que funciona". |
| **Desplegar** | canonical → dev → test → producción. | Cada paso reversible. |
| **Observar** | Diagnósticos, health de crons, audit logs dentro del producto. | El sistema se explica solo. |

---

## El ADN del stack

yohacase toma prestado el mejor rasgo de cada herramienta que usa:

| Fuente | Rasgo | Cómo se aplica |
|---|---|---|
| **WordPress** | Hooks & filters | Los módulos se enchufan por hooks; nunca se modifican entre sí. |
| **Laravel** | Convención sobre configuración | Misma estructura siempre: `includes/`, `api/`, `class-<dominio>.php`. |
| **React** | Composición | UI = piezas pequeñas compuestas, no páginas monolíticas. |
| **Astro** | Cero JS por defecto | HTML primero; JavaScript solo donde aporta. |
| **Unix** | Una cosa, hecha bien | Un módulo, un dominio. Una función, una tarea. |
| **REST** | Stateless + semántica | Rutas con contrato, verbos con significado, mutaciones idempotentes. |
| **Chrome MV3** | Permisos mínimos | Capability checks, scopes OAuth, nonces — cada capa pide solo lo que necesita. |
| **n8n/Make** | Flujos observables | Ninguna automatización es magia: health, duración y errores registrados. |
| **12-factor** | Config fuera del código | Secretos en `wp-config`/env — jamás en el repo. |
| **SQLite/MySQL** | Schema versionado | Cada módulo estampa su versión y migra solo. |

---

## Lo que yohacase evita

- **Saltar de framework** — el stack no cambia por moda.
- **Copy-paste entre proyectos** — si se repite, se extrae.
- **Automatización mágica** — sin logs ni health, es un fallo silencioso.
- **Secretos en el repo** — ningún token toca el control de versiones.
- **Producción primero** — el orden es canonical → dev → test → prod.
- **Copias divergentes** — un parche en una sola copia es un bug futuro.

---

## Checklist para un proyecto nuevo

1. ¿Identifiqué los dominios y qué datos posee cada uno?
2. ¿El contrato REST + schema existe antes de escribir la UI?
3. ¿Cada regla de negocio vive en un solo lugar?
4. ¿Toda mutación acepta `request_id` y es rate-limited?
5. ¿Hay suite QA que corre en test antes de prod?
6. ¿El release se puede apagar sin desplegar?
7. ¿Diagnósticos y audit logs incluidos desde el día uno?

Si las 7 son sí → es yohacase.

---

## Dónde está aplicado

- **arc-intranet** — módulos por dominio (google, permissions, onboarding),
  matriz de permisos como fuente única, diagnósticos integrados.
- **arc-time-clock** — API idempotente con `request_id`, rate limiting,
  drafts por día, suite E2E.
- **arc-time-clock-extension** — MV3 con permisos mínimos, cola offline
  con dedup, cero login extra (reutiliza la sesión).
- **arc-starter-templates** — payload remoto on-demand, wizard con
  dry-run, reversible por diseño.
- **karibase** — el componente compartido por excelencia: design system
  + plugins JS reutilizables en cualquier proyecto.
- **lienzo-astra** — convención sobre configuración en forma de tema:
  todo se ajusta desde el Customizer, nada hardcodeado.

*Este documento es la referencia legible del método. La versión viva
está en `/yohacase` del portfolio.*
