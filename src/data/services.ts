import type { Lang } from '../i18n/ui';

export interface Service {
  title: Record<Lang, string>;
  desc: Record<Lang, string>;
}

export const services: Service[] = [
  {
    title: { es: 'Plugins de WordPress a medida', en: 'Custom WordPress plugins' },
    desc: {
      es: 'Sistemas internos con módulos por dominio, REST API segura y diagnósticos integrados — no plugins desechables.',
      en: 'Internal systems with domain modules, secure REST API and built-in diagnostics — not throwaway plugins.',
    },
  },
  {
    title: { es: 'Intranets y portales de cliente', en: 'Intranets & client portals' },
    desc: {
      es: 'Acceso por empresa, documentos, tickets, onboarding y auditoría — sobre WordPress.',
      en: 'Per-company access, documents, tickets, onboarding and auditing — on WordPress.',
    },
  },
  {
    title: { es: 'Frontends en React', en: 'React frontends' },
    desc: {
      es: 'Interfaces modernas conectadas a tus APIs y servicios existentes, con i18n incluido.',
      en: 'Modern interfaces wired to your existing APIs and services, i18n included.',
    },
  },
  {
    title: { es: 'Integraciones y APIs', en: 'Integrations & APIs' },
    desc: {
      es: 'Gmail, Drive, Calendar, OAuth 2.0, pasarelas de pago y sincronización de datos entre sistemas.',
      en: 'Gmail, Drive, Calendar, OAuth 2.0, payment gateways and data sync across systems.',
    },
  },
  {
    title: { es: 'Automatización de procesos', en: 'Process automation' },
    desc: {
      es: 'n8n, Make y Apps Script: flujos observables con logs, health checks y alertas — nada de magia.',
      en: 'n8n, Make and Apps Script: observable flows with logs, health checks and alerts — no magic.',
    },
  },
  {
    title: { es: 'Extensiones de navegador', en: 'Browser extensions' },
    desc: {
      es: 'Chrome/Edge MV3 con permisos mínimos que llevan tu producto directo al navegador del usuario.',
      en: 'Chrome/Edge MV3 with minimal permissions bringing your product into the user\u2019s browser.',
    },
  },
];

export const companies: string[] = [
  'Ash River Collective',
  'Automate Your Life',
  'RippedCFO',
  'CESDE',
  'Universidad EAFIT',
];
