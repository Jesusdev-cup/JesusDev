// src/popup.ts — the popup is just a UI over the contract:
// it sends typed messages, never touches chrome.storage directly.

import type { Msg, MsgResponse, TimeEntry } from './messages';

async function send<T>(msg: Msg): Promise<T> {
  const res = (await chrome.runtime.sendMessage(msg)) as MsgResponse;
  if (!res.ok) throw new Error(res.error.message);
  return res.data as T;
}

const list = document.getElementById('list')!;
const form = document.getElementById('form') as HTMLFormElement;

async function render() {
  const day = new Date().toISOString().slice(0, 10);
  const entries = await send<TimeEntry[]>({ type: 'entries.list', payload: { day } });
  list.innerHTML = entries
    .map((e) => `<li>${e.task} — ${e.minutes}min</li>`)
    .join('') || '<li>No entries yet.</li>';
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = new FormData(form);
  await send<TimeEntry>({
    type: 'entries.create',
    payload: {
      task: String(data.get('task')),
      minutes: Number(data.get('minutes')),
    },
    requestId: crypto.randomUUID(), // one id per logical submit
  });
  form.reset();
  await render();
});

void render();
