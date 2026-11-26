// middleware/require-json.ts — the contract declares its media type:
// mutations without application/json are rejected before parsing.

import type { NextFunction, Request, Response } from 'express';

export const requireJson = () =>
  (req: Request, res: Response, next: NextFunction) => {
    if (!req.is('application/json')) {
      res.status(415).json({
        error: {
          code: 'unsupported_media_type',
          message: 'Content-Type must be application/json',
        },
      });
      return;
    }
    next();
  };
