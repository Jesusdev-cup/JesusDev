# PHP Composer — time-entries

El molde como paquete Composer: `src/Contracts/` es la API pública,
los eventos PSR-14 son los hooks, el consumer depende de interfaces.

## Convenciones oficiales (php.net + PSR) → archivos

| Estándar | Archivo | Qué aporta el molde |
|---|---|---|
| PSR-4 autoload | `composer.json` | `Arc\TimeEntries\` → `src/` |
| Contracts-first | `src/Contracts/EntryStore.php` | la API pública es una interfaz |
| Value objects | `src/Entry.php`, `src/NewEntry.php` | tipos readonly + validación en construcción |
| PSR-14 events | `src/Events/EntryCreated.php`, `Entries.php` | extensión por eventos = hooks |
| Idempotency | `Entries.php` (`$seen` por `$requestId`) | retry → misma respuesta |
| DI | `EventDispatcherInterface` en el ctor | dependencia opcional inyectada |
| PHPUnit | `tests/EntriesTest.php` | contrato probado sin HTTP |

## Contrato

```php
$entries->create(new NewEntry('Deep work', 90), $requestId);
// mismo $requestId → mismo Entry, sin duplicado
```

## Restricciones aplicadas (máxima validación)

- `declare(strict_types=1)` — sin coerciones implícitas en la frontera
- `NewEntry` valida en construcción: `task` 1-255 (tras trim), `minutes` 1-1440 → `InvalidArgumentException`
- El dominio nunca puede contener un valor fuera de contrato
- `Entry` readonly — estado inmutable una vez creado
