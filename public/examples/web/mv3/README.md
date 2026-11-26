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

## Restricciones aplicadas (máxima validación / mínimo privilegio)

- Sin `content_scripts` — la extensión no toca páginas; solo permiso `storage`
- `isMsg()` — todo mensaje entrante se valida contra el contrato antes del dominio (`invalid_msg` si falla)
- Bounds completos en el guard: `task` 1-255, `minutes` 1-1440, `day` regex `YYYY-MM-DD`, `requestId` requerido
- El popup renderiza solo con `textContent`/`createElement` — cero `innerHTML`, los datos nunca se vuelven markup
