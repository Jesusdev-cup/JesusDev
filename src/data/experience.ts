import type { Lang } from '../i18n/ui';

export interface Job {
  company: string;
  period: Record<Lang, string>;
  role: Record<Lang, string>;
  description: Record<Lang, string>;
}

export const jobs: Job[] = [
  {
    company: 'Ash River Collective',
    period: { es: '2023 — Presente', en: '2023 — Present' },
    role: {
      es: 'Desarrollador de Software & Analista',
      en: 'Software Developer & Analyst',
    },
    description: {
      es: 'Desarrollo integral de la intranet corporativa y el panel interno con plugins de WordPress a medida (clientportal.ashrivercollective.com). Arquitectura e implementación del sistema web para Braintree. Soluciones modulares de gestión documental, control de usuarios y automatización operativa.',
      en: 'End-to-end development of the corporate intranet and internal panel with custom WordPress plugins (clientportal.ashrivercollective.com). Architecture and implementation of the Braintree web system. Modular solutions for document management, user control and operational automation.',
    },
  },
  {
    company: 'Automate Your Life · RippedCFO',
    period: { es: '2023 — Presente', en: '2023 — Present' },
    role: {
      es: 'Desarrollador Web Frontend & React',
      en: 'Frontend & React Developer',
    },
    description: {
      es: 'Desarrollo y maquetación interactiva en React para la plataforma principal de AYL (ayltoday.com). Construcción del sitio corporativo de servicios financieros RippedCFO (rippedcfo.us). Integración de interfaces modernas con APIs REST y servicios de automatización de flujos.',
      en: 'Interactive React development and markup for the main AYL platform (ayltoday.com). Built the RippedCFO financial-services corporate site (rippedcfo.us). Integrated modern interfaces with REST APIs and workflow automation services.',
    },
  },
  {
    company: 'CCO — Conocimiento Corporativo',
    period: { es: 'Proyectos institucionales', en: 'Institutional projects' },
    role: {
      es: 'Desarrollador Web & Administrador',
      en: 'Web Developer & Administrator',
    },
    description: {
      es: 'Desarrollo y mantenimiento de plataformas web institucionales y educativas en WordPress para CESDE (cesde.edu.co) y la Universidad EAFIT (eafit.edu.co). Optimización de rendimiento, bases de datos MySQL y seguridad del sitio.',
      en: 'Built and maintained institutional and educational WordPress platforms for CESDE (cesde.edu.co) and Universidad EAFIT (eafit.edu.co). Performance optimization, MySQL databases and site security.',
    },
  },
  {
    company: 'Freelance',
    period: { es: 'Continuo', en: 'Ongoing' },
    role: {
      es: 'Analista de Sistemas & Proyectos Personales',
      en: 'Systems Analyst & Personal Projects',
    },
    description: {
      es: 'Diseño e implementación de demos interactivos y plantillas web. Configuración de bases de datos locales e integraciones con n8n/Make para automatización comercial.',
      en: 'Design and implementation of interactive demos and web templates. Local database setup and n8n/Make integrations for business automation.',
    },
  },
];
