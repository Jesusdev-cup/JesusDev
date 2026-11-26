# Symfony — time-entries

El molde en Symfony 7: los atributos PHP declaran el contrato
(route + permiso + payload validado), el dominio es PHP puro.

## Temas de la documentación oficial (symfony.com/doc) → archivos

| Tema oficial | Archivo | Qué aporta el molde |
|---|---|---|
| Routing (`#[Route]`) | `src/Domain/TimeEntries/EntryController.php` | rutas por dominio, atributos |
| Controllers | `EntryController.php` | controller delgado → delega al dominio |
| Validation (`#[MapRequestPayload]`) | `NewEntry.php` (DTO + `Assert\*`) | DTO validado = schema; violación → respuesta automática |
| Security / Voters | `TimeEntryVoter.php` | voter = matriz única; `#[IsGranted]` la lee |
| Services / DI | `Entries.php` (autowired en el controller) | dominio como servicio inyectado |
| Testing | `tests/TimeEntryControllerTest.php` | `WebTestCase` — contrato probado end-to-end |
| DTO pattern | `NewEntry.php` | el input es un tipo, no un array |
| Error shape | `#[MapRequestPayload]` | violations → problema JSON estándar |

## Contrato

```
GET  /api/time-entries?day=YYYY-MM-DD   voter: TIME_READ
POST /api/time-entries                  voter: TIME_WRITE + X-Request-Id dedup
```

## Restricciones aplicadas (máxima validación)

- `declare(strict_types=1)` en todos los archivos — sin coerciones implícitas
- Assert completos: `NotBlank`/`Type`/`Length(1-255)` en `task`, `NotNull`/`Type`/`Range(1-1440)` en `minutes`
- `#[MapRequestPayload]` — payload desconocido → `422` automático
- `#[IsGranted]` + voter — `401` sin principal, `403` sin permiso, antes del dominio
- `day` validado con regex `YYYY-MM-DD` → `400`
