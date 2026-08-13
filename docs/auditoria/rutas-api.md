# Auditoría de rutas — `routes/api.php`

> El grupo `api` aplica `EnsureFrontendRequestsAreStateful`, `throttle:api` y `SubstituteBindings` (definido en `app/Http/Kernel.php`). El prefijo real es `/api/...` (definido por Laravel por defecto en `RouteServiceProvider`).

> **Nota de actualización (auditoría cruzada de documentación, 2026-08-08):** este documento es la fotografía original de la Fase 0 y **no se reescribió** para no perder el registro histórico. Desde entonces: los "Problemas detectados" de `listar-mis-cartas`, `responder-carta-*`, `eliminar-carta` y `dcpdf/{id}` listados abajo **ya fueron corregidos** (SEG-003, SEG-004, SEG-005 — ver `docs/auditoria/problemas-detectados.md`). El import muerto de `SensorDataController` (sección de abajo) **ya no existe** (`ARQ-003`, resuelto). El login **ya no "guarda `fcm_token`"** tal como se describe en la fila de abajo: desde Fase 7 (`SEG-014`, `FIREBASE-002`) solo registra un dispositivo en la tabla `dispositivos` cuando el request trae el campo, vía `RegistrarDispositivoAction`. Se agregaron 2 rutas nuevas no listadas aquí originalmente: `POST /actualizar-token-fcm` y `POST /eliminar-token-fcm`. Y existe un alias `/api/v1/...` para todo este grupo (`ARQ-005`). Ver `docs/api/autenticacion.md` para el contrato vigente y completo.

## Rutas públicas (sin `auth:sanctum`)

| Método | URI | Handler | Consumidor | Propósito | Problemas detectados |
|---|---|---|---|---|---|
| POST | `/api/login` | Closure inline en `routes/api.php` | App móvil (niño) | Autenticación del niño por `numero_child`; crea token Sanctum y guarda `fcm_token` | Lógica de autenticación completa dentro de una closure en el archivo de rutas (no un Controller) — mala práctica de mantenibilidad. No hay rate limiting explícito adicional al `throttle:api` global; `numero_child` es enumerable (no hay contraseña ni PIN), por lo que quien conozca o adivine un número de niño puede "iniciar sesión" como ese niño |
| GET | `/api/ver-archivo-pdf-por-ninio/{id}` | `WelcomeController@pdfninio` | App móvil | Descarga de PDF de carta, `id` cifrado con `Crypt::encryptString` | Correcto en cuanto a ofuscación del ID, pero sigue siendo un endpoint público sin autenticación real (cualquiera con el enlace cifrado puede descargar) — aceptable si el enlace solo se distribuye por canales privados (email) |
| GET | `/api/dcpdf/{id}` | `WelcomeController@descargarCartaPdf` | App móvil / enlaces de correo | Descarga de PDF de carta respondida | **CRÍTICO**: `id` es el ID autoincremental de la tabla `cartas`, sin cifrar y sin autenticación. Cualquiera puede iterar `/api/dcpdf/1`, `/api/dcpdf/2`, ... y descargar cartas (con fotos y datos personales de niños) de forma completamente anónima. Ver `seguridad.md` SEG-003 |

## Rutas protegidas — `middleware(['auth:sanctum'])`

| Método | URI | Handler | Propósito | Problemas detectados |
|---|---|---|---|---|
| POST | `/api/listar-mis-cartas` | `Api\ResponderCarta@listaCartas` | Listar cartas del niño autenticado | **CRÍTICO**: usa `Ninio::find($request->userId)` (valor enviado por el cliente) en vez de `$request->user()` (el niño autenticado por el token). Un niño autenticado puede pasar el `userId` de otro niño y ver su listado de cartas. Ver SEG-004 |
| POST | `/api/responder-carta-contestacion` | `Api\ResponderCarta@responderContestacion` | Responder carta tipo Contestación | Delega en `guardarCarta()`, que no verifica que la carta pertenezca al niño autenticado — ver SEG-005 |
| POST | `/api/responder-carta-agradecimiento` | `Api\ResponderCarta@responderAgradecimineto` | Ídem Agradecimiento | Igual |
| POST | `/api/responder-carta-iniciada` | `Api\ResponderCarta@responderIniciada` | Ídem Iniciada | Igual |
| POST | `/api/responder-carta-presentacion-menores` | `Api\ResponderCarta@responderCartaPresentacionMenor` | Ídem Presentación (menor) | Igual |
| POST | `/api/responder-carta-presentacion-mayores` | `Api\ResponderCarta@responderCartaPresentacionMayor` | Ídem Presentación (mayor) | Igual |
| POST | `/api/enviar-mensaje` | `Api\ResponderCarta@enviarMensaje` | Sin implementar (retorna JSON fijo) | Endpoint aparentemente placeholder — confirmar con el equipo móvil si está en uso |
| POST | `/api/eliminar-carta` | `Api\ResponderCarta@eliminarCarta` | Marcar carta como `eliminado=true` | **ALTO**: `Carta::find($request->id)` sin verificar que pertenezca al niño autenticado, y sin `null`-check antes de `$carta->eliminado=true` (posible error 500 si el ID no existe). Ver SEG-005 |
| POST | `/api/actualizar-token-fcm` | `Api\TokenFcmController@actualizar` | Registrar/reasignar un dispositivo del niño autenticado (Fase 7, agregado 2026-08-08, no existía en la auditoría original) | Ninguno — ver `docs/api/autenticacion.md` |
| POST | `/api/eliminar-token-fcm` | `Api\TokenFcmController@eliminar` | Eliminar un dispositivo puntual del niño autenticado (Fase 7, agregado 2026-08-08) | Ninguno — ver `docs/api/autenticacion.md` |
| GET | `/api/user` | Closure inline | Retorna el usuario/niño autenticado | Estándar de Sanctum, sin problemas |

## Imports muertos

~~`routes/api.php` importa `App\Http\Controllers\SensorDataController`, que **no existe en el repositorio** y no se usa en ninguna ruta. Mismo patrón que `GestorController` en `web.php` — indicio de funcionalidad retirada sin limpiar imports.~~ **Resuelto (`ARQ-003`, 2026-08-08).**

## Contrato API y consumo móvil

~~Todas las rutas de este archivo son, por diseño, el contrato con la app React Native (el niño patrocinado). No se encontró versión de API (`/api/v1/`) ni documentación de contrato (`docs/api/` no existe aún).~~ **Actualizado (2026-08-08):** existe versionado (`/api/v1/...`, `ARQ-005`) y contrato formal en `docs/api/` (`autenticacion.md`, `cartas.md`, `documentos.md`). Antes de modificar cualquier respuesta JSON de estas rutas, se debe seguir confirmando con el repositorio móvil qué campos consume realmente — ver `docs/auditoria/integracion-movil.md` y `docs/movil/`.
