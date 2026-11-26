// routes/time-entries.ts — the middleware chain IS the contract
// declaration: permission → schema → idempotency → domain.

import { Router } from 'express';
import { z } from 'zod';
import { entries } from '../domain/entries';
import { requirePerm } from '../middleware/require-perm';
import { requireJson } from '../middleware/require-json';
import { validate } from '../middleware/validate';
import { idempotent } from '../middleware/idempotent';

// .strict(): unknown fields are rejected outright — the contract is closed.
const newEntrySchema = z
  .object({
    task: z.string().trim().min(1).max(255),
    minutes: z.number().int().min(1).max(1440),
  })
  .strict();

const daySchema = z.string().regex(/^\d{4}-\d{2}-\d{2}$/);

export const timeEntries = Router();

timeEntries.get('/time-entries', requirePerm('time.read'), async (req, res, next) => {
  try {
    const day = daySchema.safeParse(req.query.day);
    if (!day.success) {
      res.status(400).json({
        error: { code: 'invalid_day', message: 'day must be YYYY-MM-DD' },
      });
      return;
    }
    res.json(await entries.list(day.data));
  } catch (e) {
    next(e);
  }
});

timeEntries.post(
  '/time-entries',
  requirePerm('time.write'),
  requireJson(),
  validate(newEntrySchema),
  idempotent(),
  async (req, res, next) => {
    try {
      const entry = await entries.create(req.body);
      res.status(201).json(entry);
    } catch (e) {
      next(e);
    }
  },
);
