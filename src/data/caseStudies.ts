import type { Lang } from '../i18n/ui';

export interface CaseStudy {
  slug: string;
  role: Record<Lang, string>;
  problem: Record<Lang, string>;
  solution: Record<Lang, string[]>;
  highlights: Record<Lang, string[]>;
}

export const caseStudies: Record<string, CaseStudy> = {
  'arc-intranet': {
    slug: 'arc-intranet',
    role: { es: 'Backend & APIs', en: 'Backend & APIs' },
    problem: {
      es: 'La empresa necesitaba una intranet segura con inicio de sesión único de Google, email integrado y permisos granulares por compañía — sin depender de plugins genéricos que no cubrían su flujo operativo.',
      en: 'The company needed a secure intranet with Google single sign-on, integrated email and granular per-company permissions — without relying on generic plugins that did not fit their operational flow.',
    },
    solution: {
      es: [
        'Flujo OAuth verificado en servidor con páginas de error brandeadas y auditoría de cada inicio de sesión.',
        'Sincronización bidireccional con Gmail: espejo de bandeja, archivar, borrar y mover se reflejan en Gmail.',
        'Proxy de Drive y Calendar, tickets internos y buscador global del portal.',
        'Matriz de permisos por empresa y usuario con una vista consolidada compartida entre admin y diagnósticos.',
        'Onboarding de usuarios externos con aprobación admin, roles limitados y asientos controlados.',
      ],
      en: [
        'Server-verified OAuth flow with branded error pages and an audit trail for every sign-in.',
        'Bidirectional Gmail sync: inbox mirror plus archive, delete and move actions reflected in Gmail.',
        'Drive & Calendar proxy, internal ticketing and a global portal search.',
        'Per-company and per-user permission matrix with a consolidated view shared by admin and diagnostics.',
        'External-user onboarding with admin approval, limited roles and controlled seats.',
      ],
    },
    highlights: {
      es: ['+30 rutas REST propias', 'Auditoría completa de accesos', 'CSP y hardening por capas'],
      en: ['30+ custom REST routes', 'Full access audit trail', 'Layered CSP & hardening'],
    },
  },
  'arc-time-clock': {
    slug: 'arc-time-clock',
    role: { es: 'Full-stack · API design', en: 'Full-stack · API design' },
    problem: {
      es: 'El equipo necesitaba fichar desde el portal con reportes EOD obligatorios, tolerancia a errores de red y cero duplicados cuando un usuario reintenta una acción.',
      en: 'The team needed to clock in from the portal with mandatory end-of-day reports, tolerance to network errors and zero duplicates when a user retries an action.',
    },
    solution: {
      es: [
        'Entradas de tiempo con pausa, reanudación y cambio de tarea sin cerrar el bloque abierto.',
        'Reportes EOD con autoguardado local y borrador en servidor, chips de campos faltantes y accesibilidad.',
        'API REST idempotente: cada mutación lleva un request_id que deduplica reintentos.',
        'Rate limiting por usuario (40 escrituras/min) y colas offline reintentadas con la misma clave.',
        'Vacaciones, turnos, festivos, revisión de timesheets y paneles de equipo.',
      ],
      en: [
        'Time entries with pause, resume and task switching without closing the open block.',
        'EOD reports with local autosave and server draft, missing-field chips and accessibility.',
        'Idempotent REST API: every mutation carries a request_id that deduplicates retries.',
        'Per-user rate limiting (40 writes/min) and offline queues retried with the same key.',
        'Leave requests, shifts, holidays, timesheet review and team dashboards.',
      ],
    },
    highlights: {
      es: ['Suite de tests E2E 15/15', 'Idempotencia end-to-end', 'Drafts por día con autoguardado'],
      en: ['15/15 E2E test suite', 'End-to-end idempotency', 'Per-day drafts with autosave'],
    },
  },
  'ix-projects': {
    slug: 'ix-projects',
    role: { es: 'Full-stack', en: 'Full-stack' },
    problem: {
      es: 'Gestión de proyectos multi-empresa dentro del portal: cada compañía solo ve sus tableros, con notificaciones y recordatorios que no fallen en silencio.',
      en: 'Multi-company project management inside the portal: each company only sees its own boards, with notifications and reminders that cannot fail silently.',
    },
    solution: {
      es: [
        'Tableros y tareas con control de acceso por compañía a nivel de consulta.',
        'Notificaciones y crons de recordatorios con health tracking — cada ejecución registra duración y errores.',
        'Enlaces de aprobación firmados para acciones externas sin login.',
        'Exportaciones de tablero y purga programada de tareas eliminadas.',
      ],
      en: [
        'Boards and tasks with company-scoped access control at query level.',
        'Notifications and reminder crons with health tracking — every run records duration and errors.',
        'Signed approval links for external actions without login.',
        'Board exports and scheduled purge of deleted tasks.',
      ],
    },
    highlights: {
      es: ['Health tracking de crons', 'Acceso multi-tenant por empresa', 'Aprobaciones sin login'],
      en: ['Cron health tracking', 'Multi-tenant company access', 'Login-free approvals'],
    },
  },

  'arc-time-clock-extension': {
    slug: 'arc-time-clock-extension',
    role: { es: 'Extensión MV3 completa', en: 'Full MV3 extension' },
    problem: {
      es: 'Los empleados olvidaban fichar o cerrar el día. Se necesitaba visibilidad permanente del temporizador sin abrir el portal — y que funcionara aunque se cayera la red.',
      en: 'Employees forgot to clock in or close the day. A persistent timer view was needed without opening the portal — working even when the network drops.',
    },
    solution: {
      es: [
        'Badge con temporizador en vivo (H:MM, pausa, EoD pendiente, error) sobre polling de 1 minuto.',
        'Popup stale-while-revalidate: pinta el último estado al instante y refresca después.',
        'Reutiliza la sesión del portal — el service worker extrae el nonce REST, sin login separado.',
        'Cola offline que reintenta mutaciones con el mismo request_id para dedup en servidor.',
        'Notificaciones clicables, atajo Ctrl+Shift+Y y detección de cambio de usuario.',
      ],
      en: [
        'Live timer badge (H:MM, paused, EoD pending, error) on a 1-minute poll.',
        'Stale-while-revalidate popup: paints last known state instantly, then refreshes.',
        'Rides the portal session — the service worker scrapes the REST nonce, no separate login.',
        'Offline queue retrying mutations with the same request_id for server-side dedup.',
        'Clickable notifications, Ctrl+Shift+Y quick toggle and user-change detection.',
      ],
    },
    highlights: {
      es: ['MV3 Chrome/Edge', 'Offline-first con idempotencia', '14 mejoras de piloto en v0.3'],
      en: ['Chrome/Edge MV3', 'Offline-first with idempotency', '14 pilot improvements in v0.3'],
    },
  },
  'arc-starter-templates': {
    slug: 'arc-starter-templates',
    role: { es: 'Plugin + infra de demos', en: 'Plugin + demo infra' },
    problem: {
      es: 'Crear sitios de WordPress completos para clientes tomaba horas de montaje manual. Se necesitaba un importador que dejara un sitio listo en un clic, sin inflar el plugin con assets.',
      en: 'Building complete WordPress sites for clients took hours of manual setup. An importer was needed to deliver a ready site in one click, without bloating the plugin with assets.',
    },
    solution: {
      es: [
        'Wizard paso a paso (Media → Páginas → Setup) con progreso en vivo, dry-run y reset opcional.',
        'Importa como contenedores de Elementor o bloques nativos de Gutenberg según el builder activo.',
        'Imágenes a la Media Library, menús asignados y portada configurada automáticamente.',
        'Payload remoto en GitHub: manifest, documentos e imágenes se descargan bajo demanda — el plugin pesa poco y siempre trae la última versión.',
        'Sitio de demos data-driven: páginas renderizadas desde JSON con un layout engine propio.',
      ],
      en: [
        'Step-by-step wizard (Media → Pages → Setup) with live progress, dry-run and optional reset.',
        'Imports as Elementor containers or native Gutenberg blocks depending on the active builder.',
        'Images into the Media Library, menus assigned and front page set automatically.',
        'Remote payload on GitHub: manifest, documents and images download on demand — the plugin stays light and always ships the latest version.',
        'Data-driven demo site: pages rendered from JSON through a custom layout engine.',
      ],
    },
    highlights: {
      es: ['16 demos · 106 páginas', '< 1 min por importación', 'GPL-2.0'],
      en: ['16 demos · 106 pages', '< 1 min per import', 'GPL-2.0'],
    },
  },
  'karibase': {
    slug: 'karibase',
    role: { es: 'Framework completo', en: 'Full framework' },
    problem: {
      es: 'Los proyectos repetían utilidades y componentes CSS una y otra vez. Se necesitaba un sistema de diseño propio, ligero y documentado, reutilizable en cualquier stack.',
      en: 'Projects kept repeating the same CSS utilities and components. A lightweight, documented, in-house design system reusable across stacks was needed.',
    },
    solution: {
      es: [
        'Framework CSS con utilidades, componentes base y extendidos, container queries y variantes para admin.',
        'Plugins JS vanilla: modales, detección de inactividad, auto-SEO en runtime y bundle ESM/minificado.',
        'Sitio de documentación construido con Astro — migrado desde Vue con un convertidor de templates propio.',
      ],
      en: [
        'CSS framework with utilities, base and extended components, container queries and admin variants.',
        'Vanilla JS plugins: modals, inactivity detection, runtime auto-SEO and an ESM/minified bundle.',
        'Documentation site built with Astro — migrated from Vue with a custom template converter.',
      ],
    },
    highlights: {
      es: ['Sistema de diseño propio', 'Docs site en Astro', 'Auto-SEO como plugin JS'],
      en: ['In-house design system', 'Astro docs site', 'Auto-SEO as a JS plugin'],
    },
  },
  'lienzo-astra': {
    slug: 'lienzo-astra',
    role: { es: 'Tema completo', en: 'Full theme' },
    problem: {
      es: 'Los starter sites importados necesitaban un tema base ligero, accesible y personalizable sin tocar código — algo que Hello Elementor no ofrece de fábrica.',
      en: 'The imported starter sites needed a lightweight, accessible base theme customizable without touching code — something Hello Elementor does not offer out of the box.',
    },
    solution: {
      es: [
        'Derivado de Hello Elementor con un panel Customizer estilo Astra: paletas globales, tipografía, contenedor, header y footer.',
        '3 widgets de Elementor propios (breadcrumbs, dark mode, tiempo de lectura) y 8 block patterns.',
        'Soporte completo de Elementor Theme Builder, WooCommerce, dark mode y RTL.',
        'SEO meta, JSON-LD y hardening de rendimiento y seguridad integrados.',
      ],
      en: [
        'Derived from Hello Elementor with an Astra-style Customizer panel: global palettes, typography, container, header and footer.',
        '3 custom Elementor widgets (breadcrumbs, dark mode, reading time) and 8 block patterns.',
        'Full Elementor Theme Builder support, WooCommerce, dark mode and RTL.',
        'Built-in SEO meta, JSON-LD and performance/security hardening.',
      ],
    },
    highlights: {
      es: ['~150 KB sin comprimir', 'Integración con ARC Starter Templates', 'GPL-3.0'],
      en: ['~150 KB uncompressed', 'ARC Starter Templates integration', 'GPL-3.0'],
    },
  },
};
