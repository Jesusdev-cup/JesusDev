// features/time-entries/hooks.test.ts — the domain is tested
// without rendering a single component. (Vitest + Testing Library)

import { renderHook, act, waitFor } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { useEntries } from './hooks';

const entry = { id: 'e1', task: 'Deep work', minutes: 90, created_at: '2025-01-01' };

function mockFetch(impl: (init?: RequestInit) => unknown) {
  return vi.fn(async (_url: string, init?: RequestInit) => ({
    ok: true,
    json: async () => impl(init),
  }));
}

beforeEach(() => {
  vi.stubGlobal('fetch', mockFetch(() => [entry]));
});

describe('useEntries', () => {
  it('loads the entries for the day', async () => {
    const { result } = renderHook(() => useEntries('2025-01-01'));
    await waitFor(() => expect(result.current.loading).toBe(false));
    expect(result.current.entries).toEqual([entry]);
  });

  it('sends X-Request-Id when creating an entry', async () => {
    const spy = mockFetch((init) =>
      init?.method === 'POST' ? entry : [entry],
    );
    vi.stubGlobal('fetch', spy);

    const { result } = renderHook(() => useEntries('2025-01-01'));
    await waitFor(() => expect(result.current.loading).toBe(false));

    await act(async () => {
      await result.current.create({ task: 'Deep work', minutes: 90 });
    });

    const post = spy.mock.calls.find(([, i]) => i?.method === 'POST');
    expect((post?.[1]?.headers as Record<string, string>)['X-Request-Id']).toBeTruthy();
    expect(result.current.entries).toHaveLength(2);
  });
});
