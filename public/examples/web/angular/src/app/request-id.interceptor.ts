// app/request-id.interceptor.ts — a functional interceptor registered once.
// It covers every domain: auth + idempotency key on mutating requests,
// like permission_callback + the contract error shape in WordPress.

import { HttpInterceptorFn } from '@angular/common/http';

export const reqIdInterceptor: HttpInterceptorFn = (req, next) => {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  // Idempotent mutations: the server dedups by this header, so a retry
  // returns the stored response instead of a duplicate.
  if (req.method !== 'GET' && !req.headers.has('X-Request-Id')) {
    headers['X-Request-Id'] = crypto.randomUUID();
  }

  const token = localStorage.getItem('auth_token');
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  return next(req.clone({ setHeaders: headers }));
};
