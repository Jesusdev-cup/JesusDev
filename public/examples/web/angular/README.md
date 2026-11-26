# Angular — time-entries

El molde yohacase en Angular 21 (standalone + signals): el dominio es un
servicio `providedIn: 'root'`, el contrato es el interceptor + el service,
la UI solo consume.

## Los temas de la documentación oficial (angular.dev) → archivos

| Tema oficial | Archivo | Qué aporta el molde |
|---|---|---|
| Components | `entry-form.component.ts`, `entry-list.component.ts`, `entries.page.ts` | standalone + `input()` signals + OnPush |
| Templates | templates de `entry-list` | `@for`/`@if`/`@empty`, control flow nuevo |
| Directives | `highlight.directive.ts` | comportamiento DOM reutilizable por dominios |
| Dependency injection | `entries.service.ts`, `entries.routes.ts` | `inject()` funcional, dominio como servicio |
| Routing | `app.routes.ts`, `entries.routes.ts` | lazy por dominio, guard = matriz de permisos, resolver + `input()` binding |
| Forms | `entry-form.component.ts` | **signal forms**: `form()` + `FormField` + schema de validación = contrato en cliente |
| HTTP client | `entries.service.ts`, `request-id.interceptor.ts`, `app.config.ts` | `HttpInterceptorFn` global: `X-Request-Id` + auth en una sola cadena |
| SSR / hybrid rendering | `app.config.server.ts`, `app.routes.server.ts`, `main.server.ts` | render mode por ruta, mismo contrato en cliente y servidor |
| Testing | `entries.service.spec.ts` | `HttpTestingController` — el dominio se prueba sin UI |
| Angular Aria | `entries-accordion.component.ts` | `ngAccordion*` headless: accesibilidad gratis, estilo propio |
| Internationalization | `messages.es.xlf` + atributos `i18n` en templates | `@@id` por string, un archivo por idioma |
| Animations | `styles.css` | `animate.enter`/`animate.leave` — CSS puro, sin `@angular/animations` |

## Contrato

```
GET  /api/time-entries?day=YYYY-MM-DD
POST /api/time-entries        headers: X-Request-Id (interceptor) → idempotente
```

## Restricciones aplicadas (máxima validación)

- Signal form schema: `required` + `maxLength(255)` + `max(1440)` — el contrato en cliente
- Route guard `can()` consulta la matriz de permisos real — no es un stub
- Interceptor: headers fijos (request id + auth), nunca ciegos del request entrante
- `providedIn: 'root'` + `inject()` — un solo punto de construcción del dominio
