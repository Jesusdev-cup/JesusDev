// features/time-entries/entries.routes.ts — the domain owns its subtree:
// route + guard + resolver live next to the feature, not in a global file.

import { Routes, ResolveFn, CanActivateFn } from '@angular/router';
import { inject } from '@angular/core';
import { EntriesService } from './entries.service';
import { EntriesPage } from './entries.page';
import type { TimeEntry } from './entry.model';

// Guard = the client-side mirror of the backend's permission matrix.
export const canReadEntries: CanActivateFn = () => inject(EntriesService).can('time.read');

// Resolver = data ready before the component renders (no loading flicker).
export const entriesResolver: ResolveFn<TimeEntry[]> = (route) =>
  inject(EntriesService).fetchAll(route.paramMap.get('day') ?? today());

const today = () => new Date().toISOString().slice(0, 10);

export const ENTRIES_ROUTES: Routes = [
  {
    path: ':day',
    component: EntriesPage,
    canActivate: [canReadEntries],
    resolve: { entries: entriesResolver },
    title: 'Time entries',
  },
];
