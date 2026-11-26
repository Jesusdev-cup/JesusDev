// routes/time-entries.ts — the middleware chain IS the contract
// declaration: permission → schema → idempotency → domain.

import { Router } from 'express';
import { z } from 'zod';
import { entries } from '../domain/entries';
import { requirePerm } from '../middleware/require-perm';
import { validate } from '../middleware/validate';
import { idempotent } from '../middleware/idempotent';

const newEntrySchema = z.object({
  task: z.string().min(1),
  minutes: z.number().int().min(1),
});

export const timeEntries = Router();

timeEntries.get('/time-entries', requirePerm('time.read'), async (req, res, next) => {
  try {
    const day = String(req.query.day ?? new Date().toISOString().slice(0, 10));
    res.json(await entries.list(day));
  } catch (e) {
    next(e);
  }
});

timeEntries.post(
  '/time-entries',
  requirePerm('time.write'),
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
