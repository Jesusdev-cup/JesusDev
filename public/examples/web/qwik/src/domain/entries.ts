// domain/entries.ts — pure domain logic, runs on the server.
// Imported by routeAction$/routeLoader$/server$ — never shipped to the client.

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
const seen = new Map<string, TimeEntry>();

export const entries = {
  async list(day: string): Promise<TimeEntry[]> {
    return [...rows.values()].filter((e) => e.created_at.startsWith(day));
  },

  async create(e: NewEntry, requestId: string): Promise<TimeEntry> {
    const dup = seen.get(requestId);
    if (dup) return dup; // idempotent: retry → same stored entry

    const entry: TimeEntry = {
      id: crypto.randomUUID(),
      ...e,
      created_at: new Date().toISOString(),
    };
    rows.set(entry.id, entry);
    seen.set(requestId, entry);
    return entry;
  },
};
