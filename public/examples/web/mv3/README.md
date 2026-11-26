# MV3 — time-entries

El molde en una extensión Chrome Manifest V3: el service worker es el
backend, el popup es una UI que solo consume mensajes tipados.

## Temas de la documentación oficial (developer.chrome.com/docs/extensions) → archivos

| Tema oficial | Archivo | Qué aporta el molde |
|---|---|---|
| `manifest.json` | `manifest.json` | permisos mínimos: solo `storage` |
| Service worker (background) | `src/background.ts` | el backend: storage + router de mensajes |
| Message passing | `src/messages.ts` | contrato: unión discriminada `Msg`/`MsgResponse` |
| `chrome.storage` | `background.ts` | solo el dominio toca storage — el popup pide |
| Popup UI | `popup.html`, `src/popup.ts` | consume `sendMessage<Msg>` — nada de storage directo |
| Idempotency | `requestId` en `entries.create` | retry → misma respuesta, sin duplicado |
| Error shape | `MsgResponse` (`ok`/`error.code`) | errores con la forma del contrato |

## Contrato

```ts
chrome.runtime.sendMessage({ type: 'entries.create', payload, requestId })
// → { ok: true, data: TimeEntry } | { ok: false, error: { code, message } }
```
