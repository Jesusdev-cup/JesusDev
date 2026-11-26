// astro.config.mjs — i18n routing is part of the site contract:
// locales declared once, Astro generates the localized routes.

import { defineConfig } from 'astro/config';

export default defineConfig({
  output: 'server', // required for endpoints + actions
  i18n: {
    defaultLocale: 'es',
    locales: ['es', 'en'],
    routing: {
      prefixDefaultLocale: false,
    },
  },
});
