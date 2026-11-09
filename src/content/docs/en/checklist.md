---
title: Adoption checklist
description: Seven questions that decide whether a project is yohacase — and how to adopt it.
sidebar: Checklist
---

## The seven questions

1. Did I identify the domains and what data each one owns?
2. Does the REST contract + schema exist before the UI gets written?
3. Does every business rule live in a single place?
4. Does every mutation accept a `request_id` and is it rate-limited?
5. Is there a QA suite that runs on test before prod?
6. Can the release be turned off without a deploy?
7. Are diagnostics and audit logs included from day one?

> If all 7 are yes → it is yohacase.

## How to adopt it

- **New project** — start with Map: name the domains, draw the contract and
  only then write the first module.
- **Existing project** — do not rewrite: wrap one domain at a time. Extract
  the first duplicated rule into a single function and you are already
  applying pillar 3.
- **In a team** — the checklist is the definition of done: a PR that fails one
  question does not get merged.
