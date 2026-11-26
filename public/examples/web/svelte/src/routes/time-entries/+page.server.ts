// routes/time-entries/+page.server.ts — SvelteKit's contract layer:
// load() supplies data (SSR), actions are the validated mutations.
// The form posts here — the client never touches the table.

import type { Actions, PageServerLoad } from './$types';
import { fail } from '@sveltejs/kit';
import { entries } from '$lib/server/entries';

export const load: PageServerLoad = async ({ url }) => {
  const day = url.searchParams.get('day') ?? new Date().toISOString().slice(0, 10);
  return { day, list: await entries.list(day) };
};

export const actions: Actions = {
  create: async ({ request }) => {
    const data = await request.formData();
    const task = String(data.get('task') ?? '').trim();
    const minutes = Number(data.get('minutes'));

    // Server-side contract validation — the error shape is part of it.
    if (!task || !Number.isInteger(minutes) || minutes < 1) {
      return fail(400, { error: { code: 'invalid_entry', message: 'Invalid entry' } });
    }

    // Idempotent: X-Request-Id dedups retries → same stored entry.
    const requestId = request.headers.get('X-Request-Id') ?? crypto.randomUUID();
    const entry = await entries.create({ task, minutes }, requestId);
    return { entry };
  },
};
