// src/actions/index.ts — Astro Actions: the validated mutation contract.
// defineAction + zod schema = the same contract as routeAction$+zod$.

import { defineAction } from 'astro:actions';
import { z } from 'astro:schema';
import { entries } from '../lib/entries';

export const server = {
  createEntry: defineAction({
    accept: 'form',
    input: z
      .object({
        task: z.string().trim().min(1).max(255),
        minutes: z.coerce.number().int().min(1).max(1440),
      })
      .strict(),
    handler: async (input, context) => {
      // Idempotent mutation: the request id dedups retries.
      const requestId =
        context.request.headers.get('X-Request-Id') ?? crypto.randomUUID();
      return entries.create(input, requestId);
    },
  }),
};
