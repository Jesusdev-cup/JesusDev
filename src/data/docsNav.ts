import type { Lang } from '../i18n/ui';

export interface DocsGroup {
  label: Record<Lang, string>;
  slugs: string[];
}

export const docsGroups: DocsGroup[] = [
  {
    label: { es: 'Guía', en: 'Guide' },
    slugs: [
      'introduccion',
      'pilares',
      'stack',
      'modulos',
      'estructura-plugin',
      'pipeline',
      'aplicar',
      'frameworks',
    ],
  },
  {
    label: { es: 'Referencia', en: 'Reference' },
    slugs: ['principios', 'antipatrones', 'checklist'],
  },
  {
    label: { es: 'Ecosistema', en: 'Ecosystem' },
    slugs: ['proyectos', 'karibase'],
  },
];
