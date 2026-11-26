// routes/time-entries/index.tsx — Qwik City: the route IS the contract.
// routeLoader$ = GET, routeAction$ + zod$ = validated POST,
// Form = progressive enhancement (works without JS).

import { component$ } from '@builder.io/qwik';
import {
  routeLoader$,
  routeAction$,
  zod$,
  z,
  Form,
  type RequestEventAction,
} from '@builder.io/qwik-city';
import { entries, type NewEntry } from '../../domain/entries';

// GET contract: data ready on the server. Day format is validated —
// an arbitrary string never reaches the domain.
export const useEntries = routeLoader$(async ({ query, fail }) => {
  const day = query.get('day') ?? new Date().toISOString().slice(0, 10);
  if (!/^\d{4}-\d{2}-\d{2}$/.test(day)) {
    throw fail(400, { error: { code: 'invalid_day', message: 'day must be YYYY-MM-DD' } });
  }
  return { day, list: await entries.list(day) };
});

// Permission check against the authenticated principal — the real
// enforcement is always server-side.
const requirePerm = (requestEvent: RequestEventAction, perm: string) => {
  const perms = requestEvent.sharedMap.get('permissions') as string[] | undefined;
  if (!perms?.includes(perm)) {
    throw requestEvent.fail(403, {
      error: { code: 'forbidden', message: `Missing permission: ${perm}` },
    });
  }
};

// POST contract: strict zod$ schema (unknown keys rejected, real bounds),
// permission checked before the domain runs, dedup by request id.
export const useCreateEntry = routeAction$(
  async (data: NewEntry, requestEvent) => {
    requirePerm(requestEvent, 'time.write');
    const requestId =
      requestEvent.request.headers.get('X-Request-Id') ?? crypto.randomUUID();
    return entries.create(data, requestId); // dedup by requestId
  },
  zod$({
    task: z.string().trim().min(1).max(255),
    minutes: z.number().int().min(1).max(1440),
  }).strict(), // reject unknown fields outright
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
