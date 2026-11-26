// features/time-entries/api.ts — the only module that talks to the backend
// for this domain. Types + endpoints = the typed contract.

import { api } from '../../shared/api';

export interface TimeEntry {
  id: string;
  task: string;
  minutes: number;
  created_at: string;
}

export interface NewEntry {
  task: string;
  minutes: number;
}

export const listEntries = (day: string) =>
  api.get<TimeEntry[]>(`/time-entries?day=${encodeURIComponent(day)}`);

// Idempotent mutation: retries with the same requestId return the stored
// response instead of creating a duplicate.
export const createEntry = (body: NewEntry, requestId: string) =>
  api.post<TimeEntry>('/time-entries', body, {
    headers: { 'X-Request-Id': requestId },
  });
