# Deuda técnica

> **Nota de actualización (auditoría cruzada de documentación, 2026-08-08):** este documento es la fotografía original de la Fase 0 y **no se reescribió** para no perder el registro histórico. La mayoría de los puntos listados abajo ya fueron resueltos entre las Fases 1, 2, 4, 5, 6 y 7 (rama `security/fase-2-hallazgos-criticos`) — cada uno se marca inline con su estado real y el ID de hallazgo correspondiente en `docs/auditoria/problemas-detectados.md`.

## Arquitectura

- ~~**No implementado actualmente:** capas de `Actions`, `Services`, `Repositories`, `Jobs`, `Events`/`Listeners`. Toda la lógica de negocio vive en Controllers.~~ **Parcialmente resuelto.** Existen hoy 9 `Actions` (`app/Actions/`, incluida `RegistrarDispositivoAction` de Fase 7) y el primer `Service` del proyecto (`app/Services/FirebaseService.php`, `FIREBASE-001`). `Repositories`, `Jobs` dedicados y `Events`/`Listeners` siguen sin implementarse — no se encontró necesidad concreta que los justifique todavía (`CLAUDE.md` sección 56).
- ~~**Duplicación Web/API:** la lógica de respuesta de cartas está completamente duplicada entre `ResponderCartaController` y `Api\ResponderCarta`.~~ **Resuelto (`ARQ-001`, Fase 4).**
- ~~**Inconsistencia de autorización:** conviven dos patrones — uno correcto... y uno inseguro (`NinioController`, `UserController`, `ComunidadController`, sin scoping ni policy).~~ **Resuelto (`SEG-001`/`SEG-002`, Fase 2).**
- ~~**Policies con nombres no convencionales:** `CartaPolicy::ver`/`eliminar` no siguen la convención de Laravel...~~ **Resuelto (`ARQ-004`, Fase 4).** `ver()` se renombró a `mostrarEnlaceVer()` (no era alias de `view()`, son dos reglas de negocio distintas — decisión documentada en `docs/cambios/decisiones-arquitectura.md`). `eliminar()` se mantiene deliberadamente (no es `destroy` en el sentido de Laravel, ver la misma decisión).

## Seguridad

Ver `seguridad.md` para el detalle completo (SEG-001 a SEG-012). ~~Resumen de deuda estructural: falta de middleware de roles registrado pese a tener `spatie/laravel-permission` instalado.~~ **Resuelto (Fase 1/2).** `SEG-001` a `SEG-005`, `SEG-007`, `SEG-008` y `SEG-013`/`SEG-014` (detectados después de la auditoría original) están resueltos — ver `problemas-detectados.md`. `SEG-006` fue analizado y diferido deliberadamente. `SEG-010` y `SEG-012` siguen sin ficha propia, pendientes de decisión de negocio (Fase 5/8 del roadmap).

## API

- ~~Sin versionado (`/api/v1/`).~~ **Resuelto (`ARQ-005`).**
- Lógica de autenticación de niño implementada como closure inline en `routes/api.php` en vez de un Controller dedicado. **Sigue vigente** — no se tocó, no generó ningún problema funcional que lo justificara.
- ~~Sin `API Resources`... las respuestas JSON se arman manualmente.~~ **Parcialmente resuelto (`ARQ-006` Paso A).** `listar-mis-cartas` ya usa `CartaListaResource`. Un envelope común para los demás 8 endpoints de escritura se evaluó y **no se recomendó** (Paso B) — requeriría coordinar un release con el equipo móvil sin beneficio claro, ver `docs/auditoria/evaluacion-api-resources.md`.
- ~~Sin documentación de contrato (`docs/api/` aún no existe).~~ **Resuelto (`DOC-001`, Fase 5).** `docs/api/` completo (`README.md`, `autenticacion.md`, `cartas.md`, `documentos.md`).

## React Native / Móvil

- No se audita el repositorio móvil como parte del código de este repo (fuera de este repo), pero sí se inspeccionó puntualmente varias veces para verificar impacto de cambios — ver `docs/auditoria/integracion-movil.md`, `matriz-api-movil.md` y `docs/movil/`.
- El login del niño depende únicamente de `numero_child` (`SEG-012`) — **sigue vigente**, decisión de producto pendiente de confirmar con el negocio, no una tarea técnica a resolver por Claude Code sin autorización.

## PDF / Email / Firebase

- ~~`config/snappy.php` con ruta de binario de Windows hardcodeada.~~ **Resuelto (`PDF-001`, Fase 6).** Producción ya tenía el binario correcto configurado manualmente (no commiteado); se corrigió el repo para que coincida.
- ~~Notificaciones (`EnviarNotificacionCartaNueva`, `EnviarCartaRespondidaGestor`) importan `ShouldQueue` pero no lo implementan → envío síncrono.~~ **Resuelto (`PERF-001`, Fase 6).**
- ~~Sin reintentos ni manejo de errores de Firebase/SMTP.~~ **Resuelto del lado Firebase (`FIREBASE-001`, Fase 7):** `FirebaseService` limpia tokens inválidos (`NotFound`) sin relanzar; otros errores reintentan vía `$tries`/`$backoff`. **Del lado SMTP sigue sin manejo explícito de errores** — no auditado ni tocado en las fases posteriores, sigue siendo deuda real.

## Queue / Scheduler / Cron

- ~~`QUEUE_CONNECTION=sync` — no hay cola real configurada.~~ **Resuelto (`PERF-001`, Fase 6).** `QUEUE_CONNECTION=database` + `queue:work --stop-when-empty` vía Scheduler, patrón compatible con cPanel compartido.
- ~~`app/Console/Kernel.php::schedule()` está vacío.~~ **Resuelto.** Registra `queue:work` (`PERF-001`) y `temp:limpiar` (`INFRA-001`).
- ~~No existen `app/Console/Commands/` personalizados.~~ **Resuelto.** `app/Console/Commands/LimpiarArchivosTemporalesCommand.php` (`INFRA-001`).

## Performance

- Posibles N+1 no confirmados en vistas Blade que iteran fuera de los DataTables. **Sigue vigente, sin auditar en detalle** — listado como pendiente de Fase 8 en `docs/fases/roadmap.md`.
- Generación de PDF (wkhtmltopdf) siempre síncrona dentro del request HTTP. **Re-evaluado, no es deuda accionable:** los 3 usos de `PDF::loadView` son endpoints GET donde el PDF es la respuesta HTTP misma (vista inline o descarga) — no hay generación como efecto secundario de otra operación que pueda moverse a un Job. Ver `PERF-001` en `problemas-detectados.md`.
- ~~Carpeta `temp` usada para decodificar imágenes base64 nunca se limpia.~~ **Resuelto (`INFRA-001`, Fase 6).** Limpieza programada diaria (`temp:limpiar`).

## Testing

- ~~Cobertura real: 0% del dominio. Solo boilerplate de Laravel.~~ **Mejorado, pero `TEST-001` (suite sistemática) sigue abierto.** Hoy hay 75 tests reales en `tests/Feature/` cubriendo cada hallazgo cerrado (autorización, validación, Firebase, tokens FCM, etc.), pero es cobertura reactiva por hallazgo, no una suite sistemática diseñada para el dominio completo de Cartas — ver `TEST-001` en `problemas-detectados.md`, pendiente de Fase 8.

## Documentación

- ~~Este directorio (`docs/`) no existía antes de esta auditoría.~~ **Resuelto y ampliado.** Estructura real hoy: `docs/api/`, `docs/auditoria/`, `docs/cambios/`, `docs/fases/`, `docs/movil/`. Las carpetas `docs/arquitectura/`, `docs/seguridad/`, `docs/ux-ui/`, `docs/infraestructura/` sugeridas originalmente en `CLAUDE.md` sección 33 **no se crearon** — el contenido equivalente terminó viviendo en `docs/auditoria/` (p. ej. `infraestructura-actual.md`) sin necesidad de una carpeta separada; `CLAUDE.md` sección 33 ya anticipa que esa estructura es solo inicial y puede no ser la definitiva.

## Infraestructura

Ver `infraestructura-actual.md` (vigente, sin cambios sustanciales desde su redacción).
