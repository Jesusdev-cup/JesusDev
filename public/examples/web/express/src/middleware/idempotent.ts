// middleware/idempotent.ts — dedup by Idempotency-Key header.
// A retry replays the stored response instead of running the mutation twice.

import type { NextFunction, Request, Response } from 'express';

interface Stored {
  status: number;
  body: unknown;
}

const cache = new Map<string, Stored>();

export const idempotent = () =>
  (req: Request, res: Response, next: NextFunction) => {
    const key = req.header('Idempotency-Key') ?? req.header('X-Request-Id');
    if (!key) return next();

    const hit = cache.get(key);
    if (hit) {
      res.status(hit.status).json(hit.body);
      return;
    }

    // Wrap res.json to store the first response for this key.
    const json = res.json.bind(res);
    res.json = ((body: unknown) => {
      cache.set(key, { status: res.statusCode, body });
      return json(body);
    }) as Response['json'];

    next();
  };
