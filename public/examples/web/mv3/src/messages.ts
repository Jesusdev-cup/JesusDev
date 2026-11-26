// src/messages.ts — the typed message contract.
// The popup never reads storage directly: it asks via messages —
// the service worker is the backend, the popup is just another UI.

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

// Contract: discriminated union of every message the domain accepts.
export type Msg =
  | { type: 'entries.list'; payload: { day: string } }
  | { type: 'entries.create'; payload: NewEntry; requestId: string };

export type MsgResponse =
  | { ok: true; data: TimeEntry[] | TimeEntry }
  | { ok: false; error: { code: string; message: string } };
