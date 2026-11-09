---
title: Principles
description: The six principles that govern every decision in the method.
sidebar: Principles
---

## 01 — Canonical first

There is a single canonical code tree. Dev copies sync from it — they never
diverge, never get patched separately.

## 02 — Contract before code

REST routes, schema and error shapes get defined first. The UI consumes the
contract — never invents it.

## 03 — Single source of truth

Every business rule lives in one function. UI, API and diagnostics all read
it — change the rule once, it changes everywhere.

## 04 — Idempotent by default

Every mutation carries a `request_id`. Retrying is free: the network can fail,
data never duplicates.

## 05 — Verify end to end

Nothing reaches production without going through the test environment and a
real QA suite — permissions, OAuth, forms, all verified with asserts.

## 06 — Ship safe

Feature flags, safe no-ops and compatibility shims. Every release can be
turned off without a deploy — breaking production is not an option.
