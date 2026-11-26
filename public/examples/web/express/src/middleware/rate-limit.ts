// middleware/rate-limit.ts — fixed-window limiter keyed by client IP.
// Mutations get a tighter budget than reads; abuse gets the contract's
// 429 error shape, not a hang or a crash.

import type { NextFunction, Request, Response } from 'express';

interface Bucket {
  count: number;
  resetAt: number;
}

const buckets = new Map<string, Bucket>();

// Sweep expired buckets periodically so the map stays bounded.
setInterval(() => {
  const now = Date.now();
  for (const [k, b] of buckets) if (b.resetAt <= now) buckets.delete(k);
}, 60_000).unref();

export const rateLimit = (limit: number, windowMs = 60_000) =>
  (req: Request, res: Response, next: NextFunction) => {
    const key = `${req.ip}:${req.method}:${req.path}`;
    const now = Date.now();
    const bucket = buckets.get(key) ?? { count: 0, resetAt: now + windowMs };

    if (bucket.resetAt <= now) {
      bucket.count = 0;
      bucket.resetAt = now + windowMs;
    }
    bucket.count += 1;
    buckets.set(key, bucket);

    res.set('X-RateLimit-Limit', String(limit));
    res.set('X-RateLimit-Remaining', String(Math.max(0, limit - bucket.count)));

    if (bucket.count > limit) {
      res.set('Retry-After', String(Math.ceil((bucket.resetAt - now) / 1000)));
      res.status(429).json({
        error: { code: 'rate_limited', message: 'Too many requests' },
      });
      return;
    }
    next();
  };
