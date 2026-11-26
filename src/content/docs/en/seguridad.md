---
title: Security in layers
description: How security applies at every layer of the method — from the request to the database, never bolted on at the end.
sidebar: Security
---

Security is not a layer added at the end — it is a decision made at every
layer a request travels through. These are the rules, in that order.

## The request

- **Permission on the route** — `permission_callback` reads the single
  permission matrix; no route stays "public by accident".
- **Nonce + capability** — double check on admin actions; the REST API
  verifies capability, it never trusts the front-end.
- **Rate limit per endpoint** — a window per user or IP. An idempotent
  mutation is no excuse to accept spam.

## The data

- **Sanitize on input** — `sanitize_text_field`, `absint`, enum allowlists.
  Whatever comes in, comes in clean.
- **Escape on output** — `esc_html`, `esc_attr`, `esc_url` matching the
  context where it is printed.
- **`$wpdb->prepare` always** — not a single concatenated query. Not one.

## The credentials

- **Nothing in the repo** — tokens and keys live in `wp-config`, environment
  variables or the secrets manager. Version control never sees them.
- **Minimal scopes** — OAuth asks only for what the flow uses; the extension
  manifest declares only what it needs.
- **Rotation without deploy** — keys change outside the code.

## The trail

- **Audit log on mutations** — who, what, when and from where. Every change
  leaves a signature.
- **Diagnostics without secrets** — the health snapshot shows flags, versions
  and crons; never tokens or personal data.
- **Errors without internals** — the contract's error shape exposes a code
  and a message; never stack traces or SQL to the client.
