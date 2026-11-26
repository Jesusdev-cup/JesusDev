// features/time-entries/components/EntriesPage.tsx — composition:
// the page wires the provider and the pieces; business logic stays
// in the domain hook.

import { EntriesProvider, useEntriesContext } from '../EntriesContext';
import { EntryForm } from './EntryForm';
import { EntryList } from './EntryList';

function EntriesView() {
  const { entries, loading, error, create } = useEntriesContext();

  return (
    <main>
      <h1>Time entries</h1>
      <EntryForm onCreate={create} />
      {error && <p role="alert">{error}</p>}
      {loading ? <p>Loading…</p> : <EntryList entries={entries} />}
    </main>
  );
}

export function EntriesPage({ day }: { day: string }) {
  return (
    <EntriesProvider day={day}>
      <EntriesView />
    </EntriesProvider>
  );
}
