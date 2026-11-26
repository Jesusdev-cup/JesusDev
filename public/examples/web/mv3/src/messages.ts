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

// Maximum validation: runtime guards narrow unknown payloads to the contract.
// The worker never trusts a message that fails these checks.
const DAY_RE = /^\d{4}-\d{2}-\d{2}$/;

const isNewEntry = (v: unknown): v is NewEntry =>
  typeof v === 'object' &&
  v !== null &&
  typeof (v as NewEntry).task === 'string' &&
  (v as NewEntry).task.trim().length >= 1 &&
  (v as NewEntry).task.length <= 255 &&
  typeof (v as NewEntry).minutes === 'number' &&
  Number.isInteger((v as NewEntry).minutes) &&
  (v as NewEntry).minutes >= 1 &&
  (v as NewEntry).minutes <= 1440;

// isMsg validates the whole message shape — unknown types and missing
// requestId are rejected before the domain sees them.
export const isMsg = (v: unknown): v is Msg => {
  if (typeof v !== 'object' || v === null) return false;
  const m = v as Partial<Msg>;
  if (m.type === 'entries.list') {
    return (
      typeof (m as { payload?: { day?: unknown } }).payload?.day === 'string' &&
      DAY_RE.test((m as { payload: { day: string } }).payload.day)
    );
  }
  if (m.type === 'entries.create') {
    const c = m as { payload?: unknown; requestId?: unknown };
    return isNewEntry(c.payload) && typeof c.requestId === 'string' && c.requestId.length >= 1;
  }
  return false;
};
