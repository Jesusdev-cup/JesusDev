=== ARC Security ===
Contributors: arc
Tags: security, hardening, audit, login-protection
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPL-2.0-or-later

Site-level hardening + append-only audit log + automated scanner mapped to the ARC security test catalog (test.txt, SEC-0001 to SEC-1000).

== Description ==

Covers the site-level portion of the catalog:

* Login lockout per IP+username (SEC-0036/0065/0796) and generic error messages (SEC-0002)
* XML-RPC fully disabled (SEC-0037/0038/0750/0782), application passwords off (SEC-0039/0797)
* User enumeration blocks: ?author=N (SEC-0041) and REST /wp/v2/users for anonymous (SEC-0042/0784)
* Security headers on frontend, wp-login.php and REST (SEC-0566/0567/0569/0570); optional CSP and HSTS
* DISALLOW_FILE_EDIT, hidden version, RSD/WLW/shortlink/oEmbed links removed (SEC-0775/0786-0788)
* Portal + login pages excluded from wp-sitemap.xml (SEC-0808)
* Request firewall: array/oversized/null-byte credentials rejected early (SEC-0063/0064)
* Central audit log: logins, lockouts, 429s, blocks — HMAC'd IPs, 90-day retention (SEC-0819/0866-0895)
* Scanner that reports every check against its SEC-IDs, plus fix buttons for uploads/.htaccess (SEC-0800/0801-0803)

Catalog items bound to arc-referral-portal internals (tokens, RBAC, nonces,
escaping, idempotency — findings H1-H17) are fixed in that plugin, not here.

== Installation ==

Upload to wp-content/plugins, activate, open ARC Security -> Audit and press "Run security scan".
