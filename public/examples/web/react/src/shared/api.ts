// shared/api.ts — single fetch wrapper for every feature.
// UI code never builds raw fetch calls; it goes through this contract.

export interface ApiError {
  status: number;
  message: string;
  code?: string;
}

async function request<T>(
  method: string,
  path: string,
  body?: unknown,
  init: RequestInit = {},
): Promise<T> {
  const res = await fetch(`/api${path}`, {
    method,
    headers: {
      'Content-Type': 'application/json',
      ...init.headers,
    },
    body: body === undefined ? undefined : JSON.stringify(body),
    ...init,
  });

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
