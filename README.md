# JesusDev

Personal portfolio — Astro, static output, ES/EN i18n.

**Live:** https://jesusdev-cup.github.io/JesusDev/

## Commands

```bash
npm run dev       # dev server
npm run build     # static build → dist/
npm run preview   # preview the build locally
node scripts/generate-og.mjs  # regenerate public/og.png
```

## Deploy — GitHub Pages

The site deploys automatically via `.github/workflows/deploy.yml` on every push
to `main`. One-time setup in the repo:

1. **Settings → Pages → Source: GitHub Actions**
2. Push to `main` — the workflow builds with `withastro/action` and deploys `dist/`.

Configured as a project page: `site` + `base: '/JesusDev'` in `astro.config.mjs`.
