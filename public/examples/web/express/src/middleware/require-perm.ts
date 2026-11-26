// middleware/require-perm.ts — the permission matrix, enforced per route.
// Same role as WordPress's permission_callback.

import type { NextFunction, Request, Response } from 'express';

// In a real app, req.user.permissions comes from auth middleware upstream.
interface AuthedRequest extends Request {
  user?: { permissions: string[] };
}

export const requirePerm = (perm: string) =>
  (req: AuthedRequest, res: Response, next: NextFunction) => {
    if (!req.user?.permissions.includes(perm)) {
      res.status(403).json({
        error: { code: 'forbidden', message: `Missing permission: ${perm}` },
      });
      return;
    }
    next();
  };
