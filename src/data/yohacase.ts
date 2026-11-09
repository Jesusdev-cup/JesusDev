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
}

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
  },
  {
    name: { es: 'Contratar', en: 'Contract' },
    desc: {
      es: 'Definir rutas, schema y errores — el contrato que UI y clientes van a consumir.',
      en: 'Define routes, schema and errors — the contract that UI and clients will consume.',
    },
  },
  {
    name: { es: 'Construir', en: 'Build' },
    desc: {
      es: 'Módulos de dominio + componentes compartidos sobre el stack fijo. Nada se construye dos veces.',
      en: 'Domain modules + shared components on the fixed stack. Nothing gets built twice.',
    },
  },
  {
    name: { es: 'Verificar', en: 'Verify' },
    desc: {
      es: 'Suites QA end-to-end en entorno de test: permisos, flujos, errores — con asserts, no a ojo.',
      en: 'End-to-end QA suites on a test environment: permissions, flows, errors — with asserts, not eyeballs.',
    },
  },
  {
    name: { es: 'Desplegar', en: 'Ship' },
    desc: {
      es: 'Del árbol canónico a dev, a test y solo entonces a producción. Cada paso es reversible.',
      en: 'From the canonical tree to dev, to test, and only then to production. Every step is reversible.',
    },
  },
  {
    name: { es: 'Observar', en: 'Observe' },
    desc: {
      es: 'Diagnósticos, health de crons y audit logs dentro del producto — el sistema se explica solo.',
      en: 'Diagnostics, cron health and audit logs inside the product — the system explains itself.',
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
