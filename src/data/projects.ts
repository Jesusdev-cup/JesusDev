import type { Lang } from '../i18n/ui';

export interface Project {
  name: string;
  kind: Record<Lang, string>;
  status: 'prod' | 'dev';
  description: Record<Lang, string>;
  tags: string[];
  links?: { label: Record<Lang, string>; href: string }[];
}

export const projects: Project[] = [
  {
    name: 'arc-intranet',
    kind: { es: 'Plugin de WordPress', en: 'WordPress plugin' },
    status: 'prod',
    description: {
      es: 'Intranet y portal de cliente sobre WordPress: inicio de sesión con Google OAuth con páginas de error brandeadas, sincronización bidireccional con Gmail (espejo de bandeja, archivar, borrar, mover), proxy de Drive y Calendar, tickets, matriz de permisos por empresa, onboarding de usuarios externos y auditoría de accesos.',
      en: 'Intranet and client portal on WordPress: Google OAuth sign-in with branded error pages, bidirectional Gmail sync (inbox mirror, archive, delete, move), Drive & Calendar proxy, ticketing, per-company permission matrix, external-user onboarding and access auditing.',
    },
    tags: ['PHP', 'WordPress', 'REST API', 'OAuth 2.0', 'Gmail API', 'Security'],
  },
  {
    name: 'arc-time-clock',
    kind: { es: 'Plugin de WordPress', en: 'WordPress plugin' },
    status: 'prod',
    description: {
      es: 'Control horario de empleados de punta a punta: entrada/salida con pausa y cambio de tarea, reportes EOD con autoguardado de borradores, vacaciones, turnos y festivos, revisión de timesheets y una API REST idempotente con rate limiting por usuario.',
      en: 'End-to-end employee time tracking: clock in/out with pause and task switching, EOD reports with draft autosave, leave requests, shifts and holidays, timesheet review, and an idempotent REST API with per-user rate limiting.',
    },
    tags: ['PHP', 'REST API', 'MySQL / SQLite', 'WP Cron', 'Idempotency'],
  },
  {
    name: 'ix-projects',
    kind: { es: 'Plugin de WordPress', en: 'WordPress plugin' },
    status: 'prod',
    description: {
      es: 'Gestión de proyectos y tareas multi-empresa: tableros, control de acceso por compañía, notificaciones, crons de recordatorios con health tracking, enlaces de aprobación y exportaciones.',
      en: 'Multi-company project & task management: boards, per-company access control, notifications, reminder crons with health tracking, approval links and exports.',
    },
    tags: ['PHP', 'WordPress', 'REST API', 'Cron health'],
  },
  {
    name: 'arc-time-clock-extension',
    kind: { es: 'Extensión de navegador', en: 'Browser extension' },
    status: 'dev',
    description: {
      es: 'Extensión Chrome/Edge (MV3) del time clock: badge con temporizador en vivo, popup stale-while-revalidate, cola offline con dedup por request-id, notificaciones clicables y atajo Ctrl+Shift+Y — reutiliza la sesión del portal, sin login extra.',
      en: 'Chrome/Edge (MV3) extension for the time clock: live timer badge, stale-while-revalidate popup, offline queue with request-id dedup, clickable notifications and a Ctrl+Shift+Y quick toggle — rides the portal session, no extra login.',
    },
    tags: ['JavaScript', 'Chrome MV3', 'Service Worker', 'Offline-first'],
  },
  {
    name: 'arc-starter-templates',
    kind: { es: 'Plugin de WordPress + repo de demos', en: 'WordPress plugin + demo repo' },
    status: 'prod',
    description: {
      es: 'Importador de un clic: convierte cualquier demo del repo en páginas reales de WordPress — contenedores de Elementor o bloques nativos de Gutenberg — con imágenes en la Media Library, menús y portada configurados. Wizard paso a paso con progreso en vivo, dry-run y reset. El payload (manifest, documentos, imágenes) vive en el repo y se descarga bajo demanda: 16 demos, 106 páginas listas.',
      en: 'One-click importer: turns any demo in the repo into real WordPress pages — Elementor containers or native Gutenberg blocks — with images in the Media Library, menus assigned and the front page set. Step-by-step wizard with live progress, dry-run and reset. The payload (manifest, documents, images) lives in the repo and downloads on demand: 16 demos, 106 ready-made pages.',
    },
    tags: ['PHP', 'WordPress', 'Elementor', 'Gutenberg', 'Tailwind', 'GitHub Pages'],
    links: [
      {
        label: { es: 'Sitio demo ↗', en: 'Demo site ↗' },
        href: 'https://ops147.github.io/tema/',
      },
      {
        label: { es: 'Repositorio ↗', en: 'Repository ↗' },
        href: 'https://github.com/ops147/tema',
      },
    ],
  },
  {
    name: 'karibase',
    kind: { es: 'Framework CSS + docs', en: 'CSS framework + docs' },
    status: 'prod',
    description: {
      es: 'Framework CSS propio con utilidades, componentes, container queries y bundles JS (ESM/min) — más su sitio de documentación construido con Astro.',
      en: 'In-house CSS framework with utilities, components, container queries and JS bundles (ESM/min) — plus its documentation site built with Astro.',
    },
    tags: ['CSS', 'JavaScript', 'Astro', 'Design system'],
  },
  {
    name: 'lienzo-astra',
    kind: { es: 'Tema de WordPress', en: 'WordPress theme' },
    status: 'prod',
    description: {
      es: 'Tema ligero derivado de Hello Elementor con Customizer estilo Astra: paletas globales, tipografía y layouts, 3 widgets de Elementor propios, 8 block patterns, dark mode, controles de WooCommerce, hardening de SEO y rendimiento e integración nativa con ARC Starter Templates.',
      en: 'Lightweight theme derived from Hello Elementor with an Astra-style Customizer: global palettes, typography and layouts, 3 custom Elementor widgets, 8 block patterns, dark mode, WooCommerce controls, SEO/performance hardening, and native integration with ARC Starter Templates.',
    },
    tags: ['PHP', 'WordPress', 'Elementor', 'theme.json', 'WooCommerce'],
    links: [
      {
        label: { es: 'Ficha del tema ↗', en: 'Theme page ↗' },
        href: 'https://ops147.github.io/tema/theme.html',
      },
    ],
  },
];

export const skillGroups: { key: string; items: string[] }[] = [
  {
    key: 'skills.backend',
    items: [
      'PHP 8.x',
      'WordPress (plugins/temas)',
      'REST APIs',
      'MySQL · SQLite',
      'Supabase · PocketBase',
      'Golang (básico)',
    ],
  },
  {
    key: 'skills.frontend',
    items: ['JavaScript ES2022+', 'React', 'HTML5 · CSS3', 'Tailwind', 'Chrome MV3'],
  },
  {
    key: 'skills.platforms',
    items: ['n8n · Make', 'Google Apps Script', 'Git', 'Figma', 'Laragon', 'Google APIs'],
  },
  {
    key: 'skills.practices',
    items: [
      'Security hardening',
      'Nonces & capabilities',
      'Rate limiting',
      'Idempotent APIs',
      'E2E QA suites',
      'i18n ES/EN',
    ],
  },
];
