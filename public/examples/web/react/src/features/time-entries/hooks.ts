// features/time-entries/hooks.ts — domain state lives here, not in components.

import { useCallback, useEffect, useState } from 'react';
import { createEntry, listEntries, type NewEntry, type TimeEntry } from './api';

export function useEntries(day: string) {
  const [entries, setEntries] = useState<TimeEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      setEntries(await listEntries(day));
      setError(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'failed to load');
    } finally {
      setLoading(false);
    }
  }, [day]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  // One requestId per logical submit — safe to retry.
  const create = useCallback(async (entry: NewEntry) => {
    const requestId = crypto.randomUUID();
    const created = await createEntry(entry, requestId);
    setEntries((prev) => [...prev, created]);
    return created;
  }, []);

  return { entries, loading, error, refresh, create };
}
