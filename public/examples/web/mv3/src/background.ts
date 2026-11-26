// src/background.ts — the service worker IS the backend.
// It owns storage and answers the typed message contract.

import type { Msg, MsgResponse, TimeEntry } from './messages';

const seen = new Map<string, TimeEntry>();

async function listEntries(day: string): Promise<TimeEntry[]> {
  const { entries = [] } = await chrome.storage.local.get('entries');
  return (entries as TimeEntry[]).filter((e) => e.created_at.startsWith(day));
}

async function createEntry(
  payload: { task: string; minutes: number },
  requestId: string,
): Promise<TimeEntry> {
  const dup = seen.get(requestId);
  if (dup) return dup; // idempotent: retry → same stored entry

  const entry: TimeEntry = {
    id: crypto.randomUUID(),
    ...payload,
    created_at: new Date().toISOString(),
  };

  const { entries = [] } = await chrome.storage.local.get('entries');
  await chrome.storage.local.set({ entries: [...entries, entry] });
  seen.set(requestId, entry);
  return entry;
}

// The contract router: validates the message before touching the domain.
chrome.runtime.onMessage.addListener((msg: Msg, _sender, sendResponse) => {
  const respond = (r: MsgResponse) => sendResponse(r);

  (async () => {
    try {
      if (msg.type === 'entries.list') {
        respond({ ok: true, data: await listEntries(msg.payload.day) });
      } else if (msg.type === 'entries.create') {
        const { task, minutes } = msg.payload;
        if (!task?.trim() || !Number.isInteger(minutes) || minutes < 1) {
          respond({
            ok: false,
            error: { code: 'invalid_entry', message: 'Invalid entry' },
          });
          return;
        }
        respond({ ok: true, data: await createEntry(msg.payload, msg.requestId) });
      } else {
        respond({ ok: false, error: { code: 'unknown_msg', message: 'Unknown message' } });
      }
    } catch {
      respond({ ok: false, error: { code: 'internal', message: 'Something went wrong' } });
    }
  })();

  return true; // keep the channel open for the async response
});
