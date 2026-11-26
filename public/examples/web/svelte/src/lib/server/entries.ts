// lib/server/entries.ts — the domain lives server-side in $lib/server.
// SvelteKit guarantees this code never ships to the client.

import { randomUUID } from 'node:crypto';

export interface TimeEntry {
  id: string;
  task: string;
  minutes: number;
  created_at: string;
}

export interface NewEntry {
  task: string;
  minutes: number;
}

// In-memory store + dedup — the real implementation hits the DB.
const rows = new Map<string, TimeEntry>();
const seen = new Map<string, TimeEntry>();

export const entries = {
  async list(day: string): Promise<TimeEntry[]> {
    return [...rows.values()].filter((e) => e.created_at.startsWith(day));
  },

  async create(e: NewEntry, requestId: string): Promise<TimeEntry> {
    const dup = seen.get(requestId);
    if (dup) return dup; // retry → same response, no duplicate

    const entry: TimeEntry = {
      id: randomUUID(),
      ...e,
      created_at: new Date().toISOString(),
    };
    rows.set(entry.id, entry);
    seen.set(requestId, entry);
    return entry;
  },
};
