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
      'flujo-de-trabajo',
      'escalabilidad',
    ],
  },
  {
    label: { es: 'En la práctica', en: 'In practice' },
    slugs: ['aplicar', 'frameworks', 'ejemplo-plugin', 'ejemplos-web', 'ejemplos-plugins'],
  },
  {
    label: { es: 'Referencia', en: 'Reference' },
    slugs: ['principios', 'seguridad', 'antipatrones', 'checklist'],
  },
  {
    label: { es: 'Ecosistema', en: 'Ecosystem' },
    slugs: ['proyectos', 'karibase'],
  },
];
