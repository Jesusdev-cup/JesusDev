// domain/entries.ts — pure domain: no express imports, no req/res.
// Testable in isolation, mounted by the router.

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

const rows = new Map<string, TimeEntry>();

export const entries = {
  async list(day: string): Promise<TimeEntry[]> {
    return [...rows.values()].filter((e) => e.created_at.startsWith(day));
  },

  async create(e: NewEntry): Promise<TimeEntry> {
    const entry: TimeEntry = {
      id: randomUUID(),
      ...e,
      created_at: new Date().toISOString(),
    };
    rows.set(entry.id, entry);
    return entry;
  },
};
