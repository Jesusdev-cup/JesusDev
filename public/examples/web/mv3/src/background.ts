// src/background.ts — the service worker IS the backend.
// It owns storage and answers the typed message contract.

import { isMsg } from './messages';
import type { MsgResponse, TimeEntry } from './messages';

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
// isMsg is the runtime gate — anything that isn't a fully-valid contract
// message is refused before a single byte of storage is read.
chrome.runtime.onMessage.addListener((msg: unknown, _sender, sendResponse) => {
  const respond = (r: MsgResponse) => sendResponse(r);

  (async () => {
    try {
      if (!isMsg(msg)) {
        respond({
          ok: false,
          error: { code: 'invalid_msg', message: 'Message failed contract validation' },
        });
        return;
      }
      if (msg.type === 'entries.list') {
        respond({ ok: true, data: await listEntries(msg.payload.day) });
      } else {
        // isMsg already guaranteed payload is a valid NewEntry + requestId.
        respond({ ok: true, data: await createEntry(msg.payload, msg.requestId) });
      }
    } catch {
      respond({ ok: false, error: { code: 'internal', message: 'Something went wrong' } });
    }
  })();

  return true; // keep the channel open for the async response
});
