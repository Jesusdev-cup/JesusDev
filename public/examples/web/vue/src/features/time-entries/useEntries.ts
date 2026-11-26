// features/time-entries/useEntries.ts — the domain lives in a composable.
// It owns the state and exposes public functions; the view consumes,
// it does not build requests.

import { ref } from 'vue';
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

export function useEntries() {
  const list = ref<TimeEntry[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const fetchAll = async (day: string) => {
    loading.value = true;
    try {
      list.value = await api.get<TimeEntry[]>(
        `/time-entries?day=${encodeURIComponent(day)}`,
      );
      error.value = null;
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'failed to load';
    } finally {
      loading.value = false;
    }
  };

  // Idempotent mutation: same requestId on retry → same stored response.
  const create = async (e: NewEntry) => {
    const created = await api.post<TimeEntry>('/time-entries', e, {
      headers: { 'X-Request-Id': crypto.randomUUID() },
    });
    list.value.push(created);
    return created;
  };

  return { list, loading, error, fetchAll, create };
}
