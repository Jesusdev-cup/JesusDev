// shared/api.ts — single fetch wrapper for every feature.
// Maximum restriction: JSON only, 10s timeout, contract error shape —
// UI code never builds raw fetch calls.

export interface ApiError {
  status: number;
  message: string;
  code?: string;
}

const TIMEOUT_MS = 10_000;

async function request<T>(
  method: string,
  path: string,
  body?: unknown,
  init: RequestInit = {},
): Promise<T> {
  let res: Response;
  try {
    res = await fetch(`/api${path}`, {
      method,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...init.headers,
      },
      body: body === undefined ? undefined : JSON.stringify(body),
      signal: AbortSignal.timeout(TIMEOUT_MS),
      credentials: 'same-origin', // never send cookies cross-origin
      ...init,
    });
  } catch {
    throw { status: 0, message: 'Network error or timeout', code: 'network' } satisfies ApiError;
  }

  if (!res.ok) {
    const data = (await res.json().catch(() => ({}))) as Partial<ApiError>;
    throw {
      status: res.status,
      message: data.message ?? res.statusText,
      code: data.code,
    } satisfies ApiError;
  }

  return res.json() as Promise<T>;
}

export const api = {
  get: <T>(path: string, init?: RequestInit) => request<T>('GET', path, undefined, init),
  post: <T>(path: string, body: unknown, init?: RequestInit) =>
    request<T>('POST', path, body, init),
};
