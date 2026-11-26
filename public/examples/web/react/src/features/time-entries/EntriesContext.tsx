// features/time-entries/EntriesContext.tsx — React's dependency injection:
// the domain API surface is provided once at the feature boundary,
// components consume it via context instead of prop drilling.

import { createContext, useContext, type ReactNode } from 'react';
import { useEntries } from './hooks';

type EntriesApi = ReturnType<typeof useEntries>;

const EntriesContext = createContext<EntriesApi | null>(null);

export function EntriesProvider({ day, children }: { day: string; children: ReactNode }) {
  const api = useEntries(day);
  return <EntriesContext.Provider value={api}>{children}</EntriesContext.Provider>;
}

export function useEntriesContext(): EntriesApi {
  const ctx = useContext(EntriesContext);
  if (!ctx) throw new Error('useEntriesContext must be used inside EntriesProvider');
  return ctx;
}
