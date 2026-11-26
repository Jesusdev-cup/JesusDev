=== ARC SEO ===
Contributors: arc
Tags: seo, open-graph, schema, meta
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPL-2.0-or-later

Meta descriptions, Open Graph/Twitter cards, JSON-LD schema and per-page SEO fields.

== Description ==

* Per-page SEO title + meta description (sidebar box on every public post type)
* Auto fallback: excerpt -> content -> tagline, capped at 160 chars
* Open Graph + Twitter cards (featured image or configurable default)
* JSON-LD: Organization + WebSite (front), WebPage/Article + BreadcrumbList (singular)
* The partner portal and branded login pages are always noindex/nofollow and carry no SEO meta
* Search results and 404s are noindex too
* Sitemap: relies on core /wp-sitemap.xml (portal pages excluded by ARC Security)
