// middleware/security.ts — security headers on every response
// (helmet-style, zero deps): the contract also covers how the
// response is delivered.

import type { NextFunction, Request, Response } from 'express';

export const securityHeaders = () =>
  (_req: Request, res: Response, next: NextFunction) => {
    res.set({
      'X-Content-Type-Options': 'nosniff',
      'X-Frame-Options': 'DENY',
      'Referrer-Policy': 'no-referrer',
      'Content-Security-Policy': "default-src 'none'; frame-ancestors 'none'",
      'Cache-Control': 'no-store',
    });
    next();
  };
