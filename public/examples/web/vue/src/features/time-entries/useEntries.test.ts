// features/time-entries/useEntries.test.ts — the composable is tested
// like any module: no component mounting needed. (Vitest)

import { describe, expect, it, vi, beforeEach } from 'vitest';
import { useEntries } from './useEntries';

const entry = { id: 'e1', task: 'Deep work', minutes: 90, created_at: '2025-01-01' };

beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn(async (_url: string, init?: RequestInit) => ({
      ok: true,
      json: async () => (init?.method === 'POST' ? entry : [entry]),
    })),
  );
});

describe('useEntries', () => {
  it('loads entries', async () => {
    const { list, loading, fetchAll } = useEntries();
    await fetchAll('2025-01-01');
    expect(loading.value).toBe(false);
    expect(list.value).toEqual([entry]);
  });

  it('create sends X-Request-Id and updates list', async () => {
    const spy = vi.fn(async (_u: string, i?: RequestInit) => ({
      ok: true,
      json: async () => (i?.method === 'POST' ? entry : [entry]),
    }));
    vi.stubGlobal('fetch', spy);

    const { list, create } = useEntries();
    await create({ task: 'Deep work', minutes: 90 });

    const headers = spy.mock.calls[0][1]?.headers as Record<string, string>;
    expect(headers['X-Request-Id']).toBeTruthy();
    expect(list.value).toContainEqual(entry);
  });
});
