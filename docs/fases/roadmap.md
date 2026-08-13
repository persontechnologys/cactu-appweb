# Roadmap de evolución de CACTU

> Basado en los hallazgos de la Fase 0 (auditoría). Prioriza seguridad sobre estética, y correcciones de bajo riesgo antes que refactorizaciones grandes, conforme a `CLAUDE.md`. Ninguna de estas fases se ha ejecutado todavía — este documento es la propuesta a validar con el equipo antes de comenzar.

## Fase 0 — Auditoría ✅ (este documento y los demás en `docs/auditoria/`)

Completada. Sin cambios funcionales realizados.

## Fase 1 — Arquitectura (fundamentos, bajo riesgo)

Objetivo: preparar el terreno sin tocar comportamiento visible.

- Registrar middleware `role`/`permission` de `spatie/laravel-permission` en `app/Http/Kernel.php` (necesario como base técnica para la Fase 2, sin aplicarlo aún a rutas).
- Limpiar imports muertos (`GestorController`, `SensorDataController`, `UserRqStore`) — ARQ-003.
- Extraer la Action de registro de carta (`RegistrarCartaAction`) desde `CartaController::store` — ARQ-002, sin cambiar comportamiento.
- Definir convención de nombres de Policies alineada a Laravel (`view`, `update`, `delete`) para permitir `authorizeResource()` a futuro.

## Fase 2 — Seguridad (prioridad crítica)

Objetivo: cerrar los hallazgos de `seguridad.md`, empezando por los CRÍTICOS.

1. **SEG-001 / SEG-002:** aplicar middleware de rol `ADMINISTRADOR` a las rutas `usuarios`, `comunidad`, `ninios`; filtrar `NinioDataTable`/`UserDataTable` por defensa en profundidad.
2. **SEG-003:** cifrar el ID en `GET /api/dcpdf/{id}` (o exigir autenticación + verificación de pertenencia).
3. **SEG-004:** cambiar `listaCartas` para usar `$request->user()` en vez de `$request->userId` — **requiere coordinación con el equipo móvil** antes de desplegar (posible cambio de contrato si la app actual ya no envía `userId`, aunque el cambio es retrocompatible si el backend simplemente ignora el campo del cliente).
4. **SEG-005:** verificar pertenencia de la carta al niño autenticado en `guardarCarta`/`eliminarCarta`.
5. **SEG-006 / SEG-007:** reforzar `CartaPolicy` y añadir `$this->authorize()` en `show`/`edit`/`update` de `CartaController`.
6. **SEG-008:** dejar de exponer `$th->getMessage()` en mensajes de usuario; loguear internamente en su lugar.
7. Añadir pruebas de autorización (Feature tests) que cubran cada uno de estos escenarios para evitar regresiones — ver TEST-001.

**`SEG-015` (Resuelto, 2026-08-08, transversal — surgió durante Fase 3, no forma parte de ella):** `numero_child` viajaba en texto plano en la URL de `cartas-ninio.index`, sin límite de intentos, y los valores reales no son de alta entropía — enumerable. Se agregó rate limiting (5/min por IP, también aplicado a `POST /login` de la API) y se movió el ingreso a un formulario que abre una ventana de sesión de 45 min en vez de dejar el número embebido en una URL persistente. De paso se corrigió un bug funcional preexistente: responder una carta redirigía a una URL rota (404) en vez de mostrar la confirmación. **Parte 2 (mismo día, a pedido del usuario):** el link directo de una carta puntual (el que manda la notificación por correo) seguía funcionando solo con el link cifrado, sin sesión — si el correo se extraviaba o quedaba cacheado, alcanzaba para responder. `ver()`, `verArchivo()` y `procesarRespuesta()` ahora exigen también la sesión; sin ella, se pide el número y se vuelve directo a esa carta puntual (no a la lista general). No se tocó `SEG-012` (decisión de negocio pendiente sobre si `numero_child` debe seguir siendo la única credencial). Detalle completo en `docs/auditoria/problemas-detectados.md` y `docs/cambios/decisiones-arquitectura.md`.

## Fase 3 — UX/UI (núcleo cerrado 2026-08-08)

Objetivo: modernizar visualmente sobre Limitless, sin romper flujos existentes.

**Actualizado (2026-08-08):** el análisis previo a implementar ya se completó — `docs/auditoria/ux-ui-actual.md`. Hallazgo que cambia el marco original de esta fase: **CACTU ya usa Limitless** como base del panel administrativo desde el primer commit; no es una migración por hacer. El trabajo real de esta fase es de consistencia y limpieza, no de introducción de un template nuevo. Se registraron los hallazgos concretos como `UX-002` a `UX-005` en `docs/auditoria/problemas-detectados.md`:

- ~~Completar el análisis de `ux-ui-actual.md`~~ **Hecho.**
- ~~Definir plan de retiro de las plantillas PDF "v1" una vez confirmado que "v2" cubre todos los casos~~ **`UX-001` (Resuelto, 2026-08-08):** se confirmó en código que "v2" NO cubría todos los casos — nunca estuvo conectada a ningún flujo de producción real. El usuario decidió retirarla (el cliente no la aprobó): se eliminó `resources/views/cartas/v2/` completo y se simplificó `CartaController::verPDF()`/la ruta. CACTU se queda solo con v1.
- **`UX-002` (Resuelto, 2026-08-08):** layout compartido entre panel y flujo público — se extrajo `sections/head.blade.php`, eliminando la duplicación de `<head>` y de paso el jQuery cargado desde un CDN externo en el flujo público. Primer paso concreto de esta fase.
- **`UX-003` (Resuelto, 2026-08-08):** primer Blade Component del proyecto (`<x-tabla-acciones>`), reemplaza el dropdown de "acciones por fila" duplicado en `ninios`/`usuarios`/`comunidad`/`mis-ninios`. `cartas/action.blade.php` queda fuera a propósito (contenido genuinamente distinto).
- **`UX-004` (Resuelto, 2026-08-08):** nueva `app/DataTables/MisNinioDataTable.php`, calcada de `NinioDataTable`; `mis-ninios/index.blade.php` queda estructuralmente idéntica al resto de los listados. Alcance por gestor verificado idéntico al anterior contra datos reales (353/353 niños).
- **`UX-005` (Resuelto, 2026-08-08):** las 5 vistas de `responder-cartas` (el flujo que usan los patrocinadores y niños/familias, no el staff) se envuelven en `card`, se quita la clase CSS muerta `input`, y los campos pasan a `form-label` (no `form-floating` — varias etiquetas son oraciones largas, ver decisión completa en `docs/cambios/decisiones-arquitectura.md`). Cero cambios de comportamiento.

**`TEST-002` (Resuelto, 2026-08-08) — incidente crítico, no UX/UI pero cerrado en esta misma sesión:** durante las pruebas de `UX-002` se detectó que `php artisan test` corría contra la base de datos real (`mysql`/`cactu`) en vez de SQLite, por una config cacheada obsoleta que ignoraba las variables de `phpunit.xml`. Cada corrida de tests vaciaba la base real (`RefreshDatabase` ejecuta `migrate:fresh`). La base quedó completamente vacía; el usuario restauró su propio backup y corrió `php artisan migrate` para aplicar las 2 migraciones de Fase 7 faltantes (`jobs`, `dispositivos`, esta última con backfill automático de datos). Causa raíz corregida con `php artisan config:clear`. Detalle completo en `docs/cambios/changelog.md` y `docs/auditoria/problemas-detectados.md`.

**Estado de Fase 3 tras esta sesión: los 5 hallazgos identificados en la auditoría (`UX-001` a `UX-005`) están resueltos.** El núcleo de la fase — layout compartido, retiro de PDF v2, componente de acciones, `mis-ninios` alineado al resto de listados, y el flujo público de `responder-cartas` con estructura de tarjetas — queda cerrado. No quedan hallazgos de UX/UI pendientes de esta auditoría; cualquier trabajo adicional de Fase 3 requeriría una nueva ronda de auditoría o un pedido explícito nuevo del usuario.

**Estado de Fase 3 tras esta sesión:** `UX-001`, `UX-002` y `UX-005` resueltos — los tres hallazgos de mayor impacto real. Quedan `UX-003` (componente de acciones duplicado) y `UX-004` (mis-ninios con patrón de listado distinto), ambos de severidad baja y sin autorizar todavía.

## Fase 4 — Unificación Web/API

Objetivo: eliminar la duplicación de reglas de negocio.

- Extraer Actions únicas para cada tipo de respuesta de carta (Contestación, Agradecimiento, Iniciada, Presentación mayor/menor), consumidas tanto por `ResponderCartaController` (web) como por `Api\ResponderCarta` (móvil) — ARQ-001.
- Validar que el comportamiento resultante sea idéntico al actual en ambos canales antes de eliminar el código duplicado.

## Fase 5 — API + React Native

Objetivo: consolidar el contrato API.

- Introducir versionado (`/api/v1/`) antes de cualquier cambio de forma de respuesta.
- Documentar formalmente `docs/api/` y `docs/movil/` con los hallazgos de `integracion-movil.md`, confirmando con el repositorio móvil real.
- Evaluar Laravel API Resources para estandarizar respuestas JSON.
- Evaluar reforzar la autenticación del niño (SEG-012) en coordinación con negocio.

## Fase 6 — PDF + Email + Jobs

Objetivo: mover procesos pesados fuera del ciclo de vida del request HTTP.

- Confirmar y corregir la configuración de `config/snappy.php` para producción (PDF-001) — **verificar primero contra el servidor real**, ya que el sistema es reportado como funcional en producción.
- Implementar `ShouldQueue` real en las notificaciones existentes (PERF-001).
- Introducir `QUEUE_CONNECTION=database` + Scheduler (`schedule:run` vía cron) + `queue:work --stop-when-empty` como patrón compatible con cPanel compartido (ver `infraestructura-actual.md`).
- Mover generación de PDF pesada a un Job cuando aplique (verPDF/descargarPdf siguen siendo síncronos hoy).
- Añadir tarea programada de limpieza de la carpeta `storage/app/temp`.

## Fase 7 — Firebase + Notificaciones

Objetivo: robustecer el flujo de notificaciones push.

- ~~Migrar de una sola columna `fcm_token` en `Ninio` a una tabla de dispositivos (Usuario/Niño → múltiples dispositivos → token), conforme al modelo objetivo de `CLAUDE.md` sección 25.~~ **Resuelto (2026-08-08).** Confirmado con el usuario que el caso de negocio era real (144 grupos de tokens compartidos entre hermanos en la BD). Nueva tabla `dispositivos`, `RegistrarDispositivoAction` centraliza el upsert por token. `ninios.fcm_token` se deprecia sin dropear todavía (decisión en `docs/cambios/decisiones-arquitectura.md`). Ver `docs/auditoria/problemas-detectados.md` (`FIREBASE-002`). **Con esto, Fase 7 queda cerrada.**
- ~~Agregar manejo de tokens inválidos/expirados y reintentos.~~ **Resuelto (2026-08-08).** De paso se detectó y corrigió `FIREBASE-001`: el paquete/endpoint usados hasta ahora (`kutia-software-company/larafirebase`, API legacy `fcm.googleapis.com/fcm/send`) estaban decomisionados por Google desde 2024 — se migró a FCM HTTP v1 vía `kreait/laravel-firebase`. Tokens inválidos (`NotFound`) se limpian automáticamente; otros errores reintentan vía `$tries`/`$backoff` de la Notification. **Verificado end-to-end en local** (credenciales reales cargadas, envío real confirmado recibido en un dispositivo). Ver `docs/auditoria/problemas-detectados.md` (`FIREBASE-001`). Pendiente fuera de este repositorio: cargar las mismas credenciales de cuenta de servicio en producción.
- ~~Endpoint explícito para actualizar/eliminar el token FCM fuera del login.~~ **Resuelto (paso 1, 2026-08-08):** `POST /actualizar-token-fcm` y `POST /eliminar-token-fcm`. De paso se corrigió `SEG-014` (el login borraba el `fcm_token` existente si el body no lo incluía). Ver `docs/auditoria/problemas-detectados.md` (`SEG-014`) y `docs/api/autenticacion.md`. El endpoint sigue sin consumidor en `CactuApp` — integrarlo es trabajo del repositorio móvil (`docs/movil/cambios-requeridos.md` #10/#11).

## Fase 8 — Performance + Calidad

Objetivo: consolidar lo construido.

- Suite de pruebas Feature/Unit cubriendo cartas, autorización y endpoints API (TEST-001).
- Revisión de N+1 en vistas Blade no cubiertas por DataTables.
- Revisión de límites de importación Excel (SEG-010).

---

## Priorización resumida

| Prioridad | Fase | Motivo |
|---|---|---|
| 1 | Fase 2 — Seguridad | Vulnerabilidades explotables hoy, algunas sin autenticación, afectando PII de menores |
| 2 | Fase 1 — Arquitectura (fundamentos) | Prerrequisito técnico de la Fase 2 (middleware de roles) |
| 3 | Fase 6 — PDF/Email/Jobs (verificación de `config/snappy.php`) | Riesgo de romper la función núcleo (generación de PDF) si no se verifica contra producción antes de tocarla |
| 4 | Fase 4 — Unificación Web/API | Reduce riesgo de que Web y API diverjan en reglas de negocio |
| 5 | Fase 5 — API + React Native | Consolidación de contrato, depende de coordinación externa (equipo móvil) |
| 6 | Fase 3 — UX/UI | Valor de negocio alto pero sin urgencia técnica |
| 7 | Fase 7 — Firebase | Mejora, no bloqueante |
| 8 | Fase 8 — Performance + Calidad | Transversal, se nutre de las fases anteriores |

No se recomienda iniciar ninguna fase de implementación sin antes revisar los hallazgos correspondientes con el responsable del proyecto y obtener autorización explícita, conforme a `CLAUDE.md` sección 48.
