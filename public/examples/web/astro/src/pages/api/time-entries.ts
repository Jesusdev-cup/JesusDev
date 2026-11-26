// src/pages/api/time-entries.ts — API endpoint: the REST contract
// as an Astro endpoint (server-rendering mode).
// Maximum validation: media-type guard, bounded body, strict schema.

import type { APIRoute } from 'astro';
import { z } from 'astro:schema';
import { entries } from '../../lib/entries';

// .strict(): unknown fields are rejected — the contract is closed.
const newEntrySchema = z
  .object({
    task: z.string().trim().min(1).max(255),
    minutes: z.number().int().min(1).max(1440),
  })
  .strict();

const daySchema = z.string().regex(/^\d{4}-\d{2}-\d{2}$/);

// The contract's declared body ceiling.
const MAX_BODY = 8 * 1024;

export const GET: APIRoute = async ({ url }) => {
  const raw = url.searchParams.get('day') ?? new Date().toISOString().slice(0, 10);
  const day = daySchema.safeParse(raw);
  if (!day.success) {
    return Response.json(
      { error: { code: 'invalid_day', message: 'day must be YYYY-MM-DD' } },
      { status: 400 },
    );
  }
  return Response.json(await entries.list(day.data));
};

export const POST: APIRoute = async ({ request }) => {
  // Media type is part of the contract — anything else is refused.
  const ct = request.headers.get('content-type') ?? '';
  if (!ct.startsWith('application/json')) {
    return Response.json(
      { error: { code: 'unsupported_media_type', message: 'Content-Type must be application/json' } },
      { status: 415 },
    );
  }

  // Content-Length guard before reading the stream at all.
  const len = Number(request.headers.get('content-length') ?? '0');
  if (len > MAX_BODY) {
    return Response.json(
      { error: { code: 'payload_too_large', message: 'body exceeds 8 KiB' } },
      { status: 413 },
    );
  }

  let json: unknown;
  try {
    const text = await request.text();
    if (text.length > MAX_BODY) {
      return Response.json(
        { error: { code: 'payload_too_large', message: 'body exceeds 8 KiB' } },
        { status: 413 },
      );
    }
    json = JSON.parse(text);
  } catch {
    return Response.json(
      { error: { code: 'invalid_payload', message: 'malformed JSON' } },
      { status: 400 },
    );
  }

  const parsed = newEntrySchema.safeParse(json);
  if (!parsed.success) {
    // The contract's error shape — same as every other runtime.
    return Response.json(
      { error: { code: 'invalid_payload', message: 'task (1-255) and minutes (1-1440) required' } },
      { status: 400 },
    );
  }

  const requestId = request.headers.get('X-Request-Id') ?? crypto.randomUUID();
  const entry = await entries.create(parsed.data, requestId);
  return Response.json(entry, { status: 201 });
};
