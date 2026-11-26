// app.routes.server.ts — per-route render modes: the list is prerendered,
// the day page renders on the server per request.

import { RenderMode, ServerRoute } from '@angular/ssr';

export const serverRoutes: ServerRoute[] = [
  {
    path: 'time-entries/:day',
    renderMode: RenderMode.Server, // fresh data per request
  },
  {
    path: '**',
    renderMode: RenderMode.Prerender, // static where possible
  },
];
