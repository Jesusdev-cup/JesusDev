// features/time-entries/entries.svelte.ts — Svelte 5 runes in a .svelte.ts
// module: the class IS the domain. Components read entries.list and call
// entries.create() — the mutation always passes through the domain.

import { api } from '../../shared/api';

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

class EntriesStore {
  list = $state<TimeEntry[]>([]);
  loading = $state(false);
  error = $state<string | null>(null);

  async fetchAll(day: string) {
    this.loading = true;
    try {
      this.list = await api.get<TimeEntry[]>(
        `/time-entries?day=${encodeURIComponent(day)}`,
      );
      this.error = null;
    } catch (e) {
      this.error = e instanceof Error ? e.message : 'failed to load';
    } finally {
      this.loading = false;
    }
  }

  // Idempotent mutation: same requestId on retry → same stored response.
  async create(e: NewEntry) {
    const entry = await api.post<TimeEntry>('/time-entries', e, {
      headers: { 'X-Request-Id': crypto.randomUUID() },
    });
    this.list.push(entry);
    return entry;
  }
}

export const entries = new EntriesStore();
