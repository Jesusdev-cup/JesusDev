import type { Lang } from '../i18n/ui';

export interface Pillar {
  num: string;
  title: Record<Lang, string>;
  desc: Record<Lang, string>;
  items: string[];
}

export interface Principle {
  title: Record<Lang, string>;
  desc: Record<Lang, string>;
}

export interface Phase {
  name: Record<Lang, string>;
  desc: Record<Lang, string>;
  deliverable: Record<Lang, string>;
}

export interface StackLayer {
  layer: Record<Lang, string>;
  tools: Record<Lang, string>;
}

export const stackLayers: StackLayer[] = [
  {
    layer: { es: 'Backend / CMS', en: 'Backend / CMS' },
    tools: { es: 'WordPress · PHP 8 · REST API', en: 'WordPress · PHP 8 · REST API' },
  },
  {
    layer: { es: 'Front-end', en: 'Front-end' },
    tools: { es: 'Vanilla JS · React · Tailwind', en: 'Vanilla JS · React · Tailwind' },
  },
  {
    layer: { es: 'Datos', en: 'Data' },
    tools: {
      es: 'MySQL · SQLite · Supabase · PocketBase',
      en: 'MySQL · SQLite · Supabase · PocketBase',
    },
  },
  {
    layer: { es: 'Extensión', en: 'Extension' },
    tools: { es: 'Chrome MV3 · service workers', en: 'Chrome MV3 · service workers' },
  },
  {
    layer: { es: 'Sitios', en: 'Sites' },
    tools: { es: 'Astro (static output)', en: 'Astro (static output)' },
  },
  {
    layer: { es: 'Diseño', en: 'Design' },
    tools: {
      es: 'Karibase (design system propio)',
      en: 'Karibase (in-house design system)',
    },
  },
  {
    layer: { es: 'Automatización', en: 'Automation' },
    tools: { es: 'n8n · Make · Google Apps Script', en: 'n8n · Make · Google Apps Script' },
  },
];

export interface ModulePart {
  title: Record<Lang, string>;
  desc: Record<Lang, string>;
}

export const moduleOwns: ModulePart[] = [
  {
    title: { es: 'Sus datos', en: 'Its data' },
    desc: {
      es: 'Cada módulo posee su tabla — o las tablas que le tocan. Ningún otro módulo las escribe.',
      en: 'Each module owns its table — or the tables it touches. No other module writes to them.',
    },
  },
  {
    title: { es: 'Su versión de schema', en: 'Its schema version' },
    desc: {
      es: 'Estampa su versión (ej. schema 2.9.38) y migra sola. Nada de bases en estado desconocido.',
      en: 'It stamps its version (e.g. schema 2.9.38) and migrates itself. No databases in unknown state.',
    },
  },
  {
    title: { es: 'Sus rutas', en: 'Its routes' },
    desc: {
      es: 'Rutas REST propias, con contrato explícito y sus propios permisos.',
      en: 'Its own REST routes, with an explicit contract and its own permissions.',
    },
  },
  {
    title: { es: 'Sus límites', en: 'Its boundaries' },
    desc: {
      es: 'Un módulo nunca entra en el dominio de otro: se comunican por funciones públicas o por hooks.',
      en: 'A module never enters another module\u2019s domain: they communicate through public functions or hooks.',
    },
  },
];

export const moduleTree = `arc-<product>/
├── includes/
│   ├── class-<domain>.php        # one file, one domain
│   ├── class-schema.php          # stamps its version, migrates itself
│   └── helpers.php               # shared pieces live once
├── api/
│   └── class-rest-<domain>.php   # routes with an explicit contract
├── assets/
└── module.php                    # wires everything through hooks`;

export const pillars: Pillar[] = [
  {
    num: '01',
    title: { es: 'Stack fijo', en: 'Fixed stack' },
    desc: {
      es: 'El mismo conjunto de herramientas en cada proyecto. Conocer el stack de memoria vale más que perseguir el framework de moda: menos sorpresas, mejor código.',
      en: 'The same toolset in every project. Knowing the stack by heart beats chasing the trendy framework: fewer surprises, better code.',
    },
    items: ['WordPress · PHP 8', 'Vanilla JS · React', 'MySQL · SQLite', 'REST APIs', 'MV3 · Astro', 'Karibase'],
  },
  {
    num: '02',
    title: { es: 'Dominios modulares', en: 'Modular domains' },
    desc: {
      es: 'Cada sistema se divide en módulos de dominio de responsabilidad única — cada uno posee sus datos, su versión de schema y sus rutas. Bajo acoplamiento, fácil de probar, fácil de extender.',
      en: 'Every system splits into single-responsibility domain modules — each owns its data, its schema version and its routes. Low coupling, easy to test, easy to extend.',
    },
    items: ['time-entries', 'eod', 'security', 'permissions', 'cron', 'integrations'],
  },
  {
    num: '03',
    title: { es: 'Componentes reutilizables', en: 'Reusable components' },
    desc: {
      es: 'Las piezas compartidas viven una vez y se componen en todos lados: un design system propio, helpers comunes y una única fuente de verdad por regla de negocio.',
      en: 'Shared pieces live once and get composed everywhere: an in-house design system, common helpers and a single source of truth per business rule.',
    },
    items: ['karibase design system', 'shared helpers', 'single source of truth', 'i18n layer'],
  },
];

export const principles: Principle[] = [
  {
    title: { es: 'Canonical first', en: 'Canonical first' },
    desc: {
      es: 'Existe un solo árbol canónico del código. Las copias de desarrollo se sincronizan desde él — nunca divergen, nunca se parchan por separado.',
      en: 'There is a single canonical code tree. Dev copies sync from it — they never diverge, never get patched separately.',
    },
  },
  {
    title: { es: 'Contrato antes que código', en: 'Contract before code' },
    desc: {
      es: 'Las rutas REST, el schema y los errores se definen primero. La UI consume el contrato — nunca lo inventa.',
      en: 'REST routes, schema and error shapes get defined first. The UI consumes the contract — never invents it.',
    },
  },
  {
    title: { es: 'Una sola fuente de verdad', en: 'Single source of truth' },
    desc: {
      es: 'Cada regla de negocio vive en una función. La UI, la API y los diagnósticos leen la misma — si cambia la regla, cambia en un solo sitio.',
      en: 'Every business rule lives in one function. UI, API and diagnostics all read it — change the rule once, it changes everywhere.',
    },
  },
  {
    title: { es: 'Idempotente por defecto', en: 'Idempotent by default' },
    desc: {
      es: 'Toda mutación lleva un request_id. Reintentar es gratis: la red puede fallar, los datos no se duplican.',
      en: 'Every mutation carries a request_id. Retrying is free: the network can fail, data never duplicates.',
    },
  },
  {
    title: { es: 'Verificar de punta a punta', en: 'Verify end to end' },
    desc: {
      es: 'Nada llega a producción sin pasar por el entorno de test y una suite QA real — permisos, OAuth, formularios, todo comprobado con asserts.',
      en: 'Nothing reaches production without going through the test environment and a real QA suite — permissions, OAuth, forms, all verified with asserts.',
    },
  },
  {
    title: { es: 'Ship seguro', en: 'Ship safe' },
    desc: {
      es: 'Feature flags, safe no-ops y shims de compatibilidad. Cada release puede apagarse sin desplegar — romper producción no es una opción.',
      en: 'Feature flags, safe no-ops and compatibility shims. Every release can be turned off without a deploy — breaking production is not an option.',
    },
  },
];

export const phases: Phase[] = [
  {
    name: { es: 'Mapear', en: 'Map' },
    desc: {
      es: 'Entender el dominio antes de escribir código: actores, datos, flujos y reglas de negocio.',
      en: 'Understand the domain before writing code: actors, data, flows and business rules.',
    },
    deliverable: {
      es: 'Mapa de módulos y contratos',
      en: 'Module map and contracts',
    },
  },
  {
    name: { es: 'Contratar', en: 'Contract' },
    desc: {
      es: 'Definir rutas, schema y errores — el contrato que UI y clientes van a consumir.',
      en: 'Define routes, schema and errors — the contract that UI and clients will consume.',
    },
    deliverable: {
      es: 'El contrato, antes de la UI',
      en: 'The contract, before the UI',
    },
  },
  {
    name: { es: 'Construir', en: 'Build' },
    desc: {
      es: 'Módulos de dominio + componentes compartidos sobre el stack fijo. Nada se construye dos veces.',
      en: 'Domain modules + shared components on the fixed stack. Nothing gets built twice.',
    },
    deliverable: {
      es: 'Código que no se repite',
      en: 'Code that does not repeat',
    },
  },
  {
    name: { es: 'Verificar', en: 'Verify' },
    desc: {
      es: 'Suites QA end-to-end en entorno de test: permisos, flujos, errores — con asserts, no a ojo.',
      en: 'End-to-end QA suites on a test environment: permissions, flows, errors — with asserts, not eyeballs.',
    },
    deliverable: {
      es: 'Asserts verdes, no "parece que funciona"',
      en: 'Green asserts, not "seems to work"',
    },
  },
  {
    name: { es: 'Desplegar', en: 'Ship' },
    desc: {
      es: 'Del árbol canónico a dev, a test y solo entonces a producción. Cada paso es reversible.',
      en: 'From the canonical tree to dev, to test, and only then to production. Every step is reversible.',
    },
    deliverable: {
      es: 'Cada paso reversible',
      en: 'Every step reversible',
    },
  },
  {
    name: { es: 'Observar', en: 'Observe' },
    desc: {
      es: 'Diagnósticos, health de crons y audit logs dentro del producto — el sistema se explica solo.',
      en: 'Diagnostics, cron health and audit logs inside the product — the system explains itself.',
    },
    deliverable: {
      es: 'El sistema se explica solo',
      en: 'The system explains itself',
    },
  },
];

export interface Influence {
  source: string;
  trait: Record<Lang, string>;
  applied: Record<Lang, string>;
}

export const influences: Influence[] = [
  {
    source: 'WordPress',
    trait: { es: 'Hooks & filters', en: 'Hooks & filters' },
    applied: {
      es: 'Todo es un punto de extensión: los módulos se enchufan por hooks, nunca se modifican entre sí.',
      en: 'Everything is an extension point: modules plug in through hooks, never modify each other.',
    },
  },
  {
    source: 'Laravel',
    trait: { es: 'Convención sobre configuración', en: 'Convention over configuration' },
    applied: {
      es: 'Misma estructura siempre: includes/, api/, class-<dominio>.php. Cualquier archivo se encuentra sin buscar.',
      en: 'Same structure every time: includes/, api/, class-<domain>.php. Any file is found without searching.',
    },
  },
  {
    source: 'React',
    trait: { es: 'Composición', en: 'Composition' },
    applied: {
      es: 'La UI se arma componiendo piezas pequeñas, no escribiendo páginas monolíticas.',
      en: 'UI gets assembled by composing small pieces, not by writing monolithic pages.',
    },
  },
  {
    source: 'Astro',
    trait: { es: 'Cero JS por defecto', en: 'Zero JS by default' },
    applied: {
      es: 'HTML primero. JavaScript solo donde aporta — el sitio pesa lo que necesita, no lo que puede.',
      en: 'HTML first. JavaScript only where it earns its place — the site weighs what it needs, not what it can.',
    },
  },
  {
    source: 'Unix',
    trait: { es: 'Haz una cosa y hazla bien', en: 'Do one thing well' },
    applied: {
      es: 'Cada módulo un dominio, cada función una tarea, cada script un propósito.',
      en: 'Each module owns one domain, each function one task, each script one purpose.',
    },
  },
  {
    source: 'REST',
    trait: { es: 'Stateless + semántica', en: 'Stateless + semantics' },
    applied: {
      es: 'Rutas con contrato explícito, verbos con significado y mutaciones idempotentes.',
      en: 'Routes with an explicit contract, verbs with meaning and idempotent mutations.',
    },
  },
  {
    source: 'Chrome MV3',
    trait: { es: 'Permisos mínimos declarados', en: 'Declared least privilege' },
    applied: {
      es: 'Capability checks, scopes de OAuth y nonces — cada capa pide solo lo que necesita.',
      en: 'Capability checks, OAuth scopes and nonces — every layer asks only for what it needs.',
    },
  },
  {
    source: 'n8n · Make',
    trait: { es: 'Flujos observables', en: 'Observable flows' },
    applied: {
      es: 'La automatización no es magia: cada cron registra health, duración y errores.',
      en: 'Automation is not magic: every cron records health, duration and errors.',
    },
  },
  {
    source: '12-factor',
    trait: { es: 'Config fuera del código', en: 'Config outside code' },
    applied: {
      es: 'Secretos en wp-config y variables de entorno — jamás en el repositorio.',
      en: 'Secrets live in wp-config and environment variables — never in the repository.',
    },
  },
  {
    source: 'SQLite · MySQL',
    trait: { es: 'Schema versionado', en: 'Versioned schema' },
    applied: {
      es: 'Cada módulo estampa su versión de schema y migra solo — nada de bases en estado desconocido.',
      en: 'Each module stamps its schema version and migrates itself — no databases in unknown state.',
    },
  },
];

export const antipatterns: string[] = [
  'framework_hop',
  'copy_paste',
  'magic_automation',
  'secrets_in_repo',
  'prod_first',
  'divergent_copies',
];

export const antipatternText: Record<string, Record<Lang, { title: string; desc: string }>> = {
  framework_hop: {
    es: {
      title: 'Saltar de framework',
      desc: 'El stack no cambia por proyecto. Cambiar herramientas por moda es deuda que no aporta.',
    },
    en: {
      title: 'Framework hopping',
      desc: 'The stack does not change per project. Switching tools for fashion is debt with no upside.',
    },
  },
  copy_paste: {
    es: {
      title: 'Copy-paste entre proyectos',
      desc: 'Si una pieza se usa dos veces, se extrae al componente compartido. Nada se duplica.',
    },
    en: {
      title: 'Copy-paste across projects',
      desc: 'If a piece is used twice, it gets extracted into a shared component. Nothing duplicates.',
    },
  },
  magic_automation: {
    es: {
      title: 'Automatización mágica',
      desc: 'Un cron o flujo sin logs ni health tracking es un fallo esperando a ocurrir en silencio.',
    },
    en: {
      title: 'Magic automation',
      desc: 'A cron or flow without logs and health tracking is a failure waiting to happen silently.',
    },
  },
  secrets_in_repo: {
    es: {
      title: 'Secretos en el repo',
      desc: 'Ningún token, clave o contraseña toca el control de versiones. Sin excepciones.',
    },
    en: {
      title: 'Secrets in the repo',
      desc: 'No token, key or password ever touches version control. No exceptions.',
    },
  },
  prod_first: {
    es: {
      title: 'Producción primero',
      desc: 'Nada toca producción sin pasar por test. El orden siempre es canonical → dev → test → prod.',
    },
    en: {
      title: 'Production first',
      desc: 'Nothing touches production without passing through test. The order is always canonical → dev → test → prod.',
    },
  },
  divergent_copies: {
    es: {
      title: 'Copias divergentes',
      desc: 'Un parche que solo existe en una copia de desarrollo es un bug futuro. Todo vuelve al canónico.',
    },
    en: {
      title: 'Divergent copies',
      desc: 'A patch that only exists in one dev copy is a future bug. Everything returns to canonical.',
    },
  },
};

export const checklist: Record<Lang, string>[] = [
  {
    es: '¿Identifiqué los dominios y qué datos posee cada uno?',
    en: 'Did I identify the domains and what data each one owns?',
  },
  {
    es: '¿El contrato REST + schema existe antes de escribir la UI?',
    en: 'Does the REST contract + schema exist before the UI gets written?',
  },
  {
    es: '¿Cada regla de negocio vive en un solo lugar?',
    en: 'Does every business rule live in a single place?',
  },
  {
    es: '¿Toda mutación acepta request_id y tiene rate limit?',
    en: 'Does every mutation accept a request_id and is it rate-limited?',
  },
  {
    es: '¿Hay una suite QA que corre en test antes de prod?',
    en: 'Is there a QA suite that runs on test before prod?',
  },
  {
    es: '¿El release se puede apagar sin desplegar?',
    en: 'Can the release be turned off without a deploy?',
  },
  {
    es: '¿Diagnósticos y audit logs incluidos desde el día uno?',
    en: 'Are diagnostics and audit logs included from day one?',
  },
];

export interface AppliedProject {
  name: string;
  desc: Record<Lang, string>;
}

export const applied: AppliedProject[] = [
  {
    name: 'arc-intranet',
    desc: {
      es: 'Módulos por dominio (google, permissions, onboarding), matriz de permisos como fuente única y diagnósticos integrados.',
      en: 'Domain modules (google, permissions, onboarding), permission matrix as the single source and built-in diagnostics.',
    },
  },
  {
    name: 'arc-time-clock',
    desc: {
      es: 'API idempotente con request_id, rate limiting, drafts por día y suite E2E.',
      en: 'Idempotent API with request_id, rate limiting, per-day drafts and an E2E suite.',
    },
  },
  {
    name: 'arc-time-clock-extension',
    desc: {
      es: 'MV3 con permisos mínimos, cola offline con dedup y cero login extra — reutiliza la sesión del portal.',
      en: 'MV3 with minimal permissions, offline queue with dedup and no extra login — it reuses the portal session.',
    },
  },
  {
    name: 'arc-starter-templates',
    desc: {
      es: 'Payload remoto on-demand, wizard con dry-run y reset — reversible por diseño.',
      en: 'Remote on-demand payload, wizard with dry-run and reset — reversible by design.',
    },
  },
  {
    name: 'karibase',
    desc: {
      es: 'El componente compartido por excelencia: design system + plugins JS reutilizables en cualquier proyecto.',
      en: 'The shared component par excellence: a design system + JS plugins reusable in any project.',
    },
  },
  {
    name: 'lienzo-astra',
    desc: {
      es: 'Convención sobre configuración en forma de tema: todo se ajusta desde el Customizer, nada hardcodeado.',
      en: 'Convention over configuration as a theme: everything is tuned from the Customizer, nothing hardcoded.',
    },
  },
];
