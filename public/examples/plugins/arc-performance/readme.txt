=== ARC Performance ===
Contributors: arc
Tags: performance, cache, lazy-load, minify
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPL-2.0-or-later

Anonymous page cache + lazy images + safe HTML minify for the ARC site.

== Description ==

* Full-page HTML cache for anonymous GET requests (wp-content/cache/arc-perf/)
* Never caches: logged-in users, auth cookies, POST, wp-admin, wp-login, REST, feeds, previews, portal pages, any ?rp= token URL
* Responses emitting Cache-Control no-store/no-cache/private are never written — the portal's no-store contract (SEC-0053) is always honored
* Global purge on content/menu/theme changes; admin-bar purge button
* Forces loading="lazy" + decoding="async" on content images
* Conservative HTML minifier that protects pre/textarea/script/style
* Removes emoji script and wp-embed.js; optional heartbeat throttle
