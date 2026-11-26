// routes/time-entries/index.tsx — Qwik City: the route IS the contract.
// routeLoader$ = GET, routeAction$ + zod$ = validated POST,
// Form = progressive enhancement (works without JS).

import { component$ } from '@builder.io/qwik';
import { routeLoader$, routeAction$, zod$, z, Form } from '@builder.io/qwik-city';
import { entries, type NewEntry } from '../../domain/entries';

// GET contract: data ready on the server.
export const useEntries = routeLoader$(async ({ query }) => {
  const day = query.get('day') ?? new Date().toISOString().slice(0, 10);
  return { day, list: await entries.list(day) };
});

// POST contract: zod$ schema validates, requestEvent carries the request.
export const useCreateEntry = routeAction$(
  async (data: NewEntry, requestEvent) => {
    const requestId =
      requestEvent.request.headers.get('X-Request-Id') ?? crypto.randomUUID();
    return entries.create(data, requestId); // dedup by requestId
  },
  zod$({
    task: z.string().min(1),
    minutes: z.number().int().min(1),
  }),
);

export default component$(() => {
  const data = useEntries();
  const create = useCreateEntry();

  return (
    <main>
      <h1>Time entries — {data.value.day}</h1>

      <Form action={create} spaReset>
        <input name="task" placeholder="Task" required />
        <input name="minutes" type="number" min="1" value={30} required />
        <button type="submit">Add entry</button>
      </Form>

      {create.value?.error && <p role="alert">{String(create.value.error)}</p>}

      <ul>
        {data.value.list.map((e) => (
          <li key={e.id}>
            {e.task} — {e.minutes}min
          </li>
        ))}
      </ul>
    </main>
  );
});
