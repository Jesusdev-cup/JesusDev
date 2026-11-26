// features/time-entries/entries.svelte.test.ts — the rune module is a
// plain class under the hood: tested like any module, no DOM needed.

import { describe, expect, it, vi, beforeEach } from 'vitest';
import { entries } from './entries.svelte';

const entry = { id: 'e1', task: 'Deep work', minutes: 90, created_at: '2025-01-01' };

beforeEach(() => {
  entries.list = [];
  vi.stubGlobal(
    'fetch',
    vi.fn(async (_url: string, init?: RequestInit) => ({
      ok: true,
      json: async () => (init?.method === 'POST' ? entry : [entry]),
    })),
  );
});

describe('entries store', () => {
  it('fetches entries for a day', async () => {
    await entries.fetchAll('2025-01-01');
    expect(entries.list).toEqual([entry]);
    expect(entries.loading).toBe(false);
  });

  it('create posts with X-Request-Id and appends', async () => {
    const spy = vi.fn(async (_u: string, i?: RequestInit) => ({
      ok: true,
      json: async () => entry,
    }));
    vi.stubGlobal('fetch', spy);

    await entries.create({ task: 'Deep work', minutes: 90 });

    const headers = spy.mock.calls[0][1]?.headers as Record<string, string>;
    expect(headers['X-Request-Id']).toBeTruthy();
    expect(entries.list).toContainEqual(entry);
  });
});
