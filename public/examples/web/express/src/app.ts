// app.ts — wiring only: routers per domain, one error boundary.
// The error handler returns the contract's error shape — internals
// never leak to the client.

import express, { type NextFunction, type Request, type Response } from 'express';
import { timeEntries } from './routes/time-entries';

export const app = express();

app.use(express.json());
app.use('/api', timeEntries);

// Health endpoint — every domain reports its own status too.
app.get('/api/status', (_req, res) => res.json({ ok: true }));

// Error boundary: contract error shape, no stack traces out.
app.use((err: unknown, _req: Request, res: Response, _next: NextFunction) => {
  console.error(err);
  res.status(500).json({
    error: { code: 'internal_error', message: 'Something went wrong' },
  });
});
