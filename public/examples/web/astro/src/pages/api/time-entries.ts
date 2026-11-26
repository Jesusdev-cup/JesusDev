// src/pages/api/time-entries.ts — API endpoint: the REST contract
// as an Astro endpoint (server-rendering mode).

import type { APIRoute } from 'astro';
import { z } from 'astro:schema';
import { entries } from '../../lib/entries';

const newEntrySchema = z.object({
  task: z.string().min(1),
  minutes: z.number().int().min(1),
});

export const GET: APIRoute = async ({ url }) => {
  const day = url.searchParams.get('day') ?? new Date().toISOString().slice(0, 10);
  return Response.json(await entries.list(day));
};

export const POST: APIRoute = async ({ request }) => {
  const parsed = newEntrySchema.safeParse(await request.json());
  if (!parsed.success) {
    // The contract's error shape — same as every other runtime.
    return Response.json(
      { error: { code: 'invalid_payload', message: 'Invalid entry' } },
      { status: 400 },
    );
  }

  const requestId = request.headers.get('X-Request-Id') ?? crypto.randomUUID();
  const entry = await entries.create(parsed.data, requestId);
  return Response.json(entry, { status: 201 });
};
