// src/middleware.ts — the edge guard: locale resolution + request context.
// Runs before every route — same role as the interceptor/middleware layer.

import { defineMiddleware } from 'astro:middleware';

export const onRequest = defineMiddleware(async (context, next) => {
  // Tracing id for observability — downstream handlers read it.
  context.locals.requestId =
    context.request.headers.get('X-Request-Id') ?? crypto.randomUUID();

  const response = await next();
  response.headers.set('X-Request-Id', context.locals.requestId);
  return response;
});
