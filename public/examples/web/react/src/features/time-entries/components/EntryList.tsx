// features/time-entries/components/EntryList.tsx — pure render component:
// receives data via props, knows nothing about the backend.

import type { TimeEntry } from '../api';

export function EntryList({ entries }: { entries: TimeEntry[] }) {
  if (entries.length === 0) return <p>No entries yet.</p>;

  const total = entries.reduce((sum, e) => sum + e.minutes, 0);

  return (
    <section>
      <ul>
        {entries.map((e) => (
          <li key={e.id}>
            {e.task} — {e.minutes}min
          </li>
        ))}
      </ul>
      <p>
        Total: {total}min
      </p>
    </section>
  );
}
