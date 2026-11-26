// app.routes.ts — updated routing: lazy feature routes per domain.
// Registered with withComponentInputBinding() so route params land
// on components as input() signals.

import { Routes } from '@angular/router';

export const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'time-entries' },
  {
    path: 'time-entries',
    loadChildren: () =>
      import('./features/time-entries/entries.routes').then((m) => m.ENTRIES_ROUTES),
  },
];
