// server/entries.ts — server$(): explicit client→server RPC boundary.
// The client calls this like a normal function; Qwik splits it at build
// time so it runs on the server only — the same contract seam as REST.

import { server$ } from '@builder.io/qwik-city';
import { entries, type TimeEntry } from '../domain/entries';

export const listEntries = server$(async function (day: string): Promise<TimeEntry[]> {
  return entries.list(day);
});
