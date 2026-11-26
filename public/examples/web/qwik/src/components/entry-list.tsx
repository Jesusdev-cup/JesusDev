// components/entry-list.tsx — a dumb presentational component.
// Resumability: Qwik serializes state into the HTML — zero hydration cost.

import { component$ } from '@builder.io/qwik';
import type { TimeEntry } from '../domain/entries';

interface Props {
  entries: TimeEntry[];
}

export const EntryList = component$<Props>(({ entries }) => {
  const total = entries.reduce((s, e) => s + e.minutes, 0);

  return (
    <section>
      {entries.length === 0 ? (
        <p>No entries yet.</p>
      ) : (
        <>
          <ul>
            {entries.map((e) => (
              <li key={e.id}>
                {e.task} — {e.minutes}min
              </li>
            ))}
          </ul>
          <p>Total: {total}min</p>
        </>
      )}
    </section>
  );
});
