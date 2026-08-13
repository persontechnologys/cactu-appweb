# Autenticación

> Verificado contra `routes/api.php` (closure inline de `/login`, sin Controller dedicado) y `app/Models/Ninio.php` (trait `HasApiTokens` de Sanctum). Para el flujo completo del lado del cliente (`Login.js`, `AsyncStorage`, qué pasa si el token expira), ver `docs/movil/autenticacion.md`.

---

### `POST /login` (también `POST /v1/login`)

Autentica un niño patrocinado por `numero_child` y emite un token Sanctum. Sin contraseña ni PIN (`SEG-012`, informativo, `docs/auditoria/seguridad.md`).

**Autenticación requerida:** ninguna.
**Permisos:** ninguno (endpoint público).
**Headers:**
```text
Accept: application/json
Content-Type: application/json
```

**Request:**
```json
{
    "numero_child": "EC-000123",
    "fcm_token": "token FCM del dispositivo, o null"
}
```

`numero_child`: `required|string|max:255`. `fcm_token`: `nullable|string` (validado desde `SEG-014`, ver más abajo).

**Response 200 (éxito):**
```json
{
    "message": "ok",
    "id": 1,
    "numero_child": "EC-000123",
    "nombres": "Nombre Completo del Niño",
    "token": "1|abcdef...",
    "roles_permisos": []
}
```

`roles_permisos` siempre es `[]` — el niño no tiene roles en este sistema; confirmar con negocio si el campo sigue siendo necesario antes de construir cualquier lógica que dependa de él.

**Response 422 (validación — `numero_child` ausente o vacío):** formato estándar de Laravel, `{"errors": {"numero_child": ["..."]}}`. **Corregido en `SEG-013`** (2026-08-08): antes de esa corrección, `$request->validate(...)` estaba dentro del mismo `try` que el `catch(\Throwable)` de más abajo, así que una validación fallida devolvía HTTP `200` con un string plano en vez del `422` estándar — verificado y confirmado con `php artisan tinker` antes de corregirlo, no asumido.

**Response 200 con cuerpo de error (`numero_child` no existe en `Ninio`):**
```json
{ "errors": { "email": ["Número child no existe.!."] } }
```
**Nota real, no es un typo de esta documentación:** la clave es `email`, no `numero_child` ni `email` tiene sentido en este contexto — es un mensaje heredado de un formulario genérico de otro flujo. **No "corregirlo"** sin confirmar antes, contra el código real de `Login.js`, que el cliente no compara esa clave exacta (`CLAUDE.md` sección 22).

**Reglas de negocio:**
- Si el request trae `fcm_token` (no vacío), se registra como un dispositivo del niño (tabla `dispositivos`, desde `FIREBASE-002`, 2026-08-08 — antes era una columna simple `Ninio::fcm_token`). Si el mismo token ya estaba asociado a otro niño (p. ej. hermanos que comparten un teléfono), se reasigna a este. Si el campo no viene o viene vacío, los dispositivos existentes **se conservan sin cambios** (`SEG-014`, corregido 2026-08-08 — antes se asignaba sin condición, incluido `null`, y un login sin `fcm_token` en el body borraba el token de push ya guardado). Para actualizar o eliminar un dispositivo fuera de un login, ver `POST /actualizar-token-fcm` y `POST /eliminar-token-fcm` más abajo.
- **Actualizado (`SEG-015`, 2026-08-08):** además del `throttle:api` general (60/min), tiene un límite propio de **5 intentos por minuto por IP** (`RateLimiter::for('numero-child', ...)`, `RouteServiceProvider`) — `numero_child` es la única credencial (sin contraseña, `SEG-012`) y los valores reales no son de alta entropía, así que se agregó este límite para que no sea practicable enumerar números. No cambia el contrato de request/response; solo devuelve `429 Too Many Requests` si se supera.
- Cualquier excepción no prevista durante el login (`\Throwable`, p. ej. fallo de BD) se registra con `Log::error()` y responde `{"error": "No se pudo iniciar sesión, intente nuevamente"}`, HTTP `200` (mismo patrón de `SEG-008`, no se cambió el código HTTP para no alterar más comportamiento del necesario). **Antes de `SEG-013`** devolvía `response()->json($th->getMessage())` — el mensaje interno real de la excepción, expuesto tal cual al cliente.

**Impacto móvil:** contrato activo, consumido por `Login.js`. No cambiar la forma de esta respuesta sin confirmar que ninguna versión de producción de la app depende de un campo exacto.

---

### `GET /user` (también `GET /v1/user`)

Endpoint estándar de Sanctum (`$request->user()`), no un Controller propio de CACTU.

**Autenticación requerida:** `Bearer <token>` (`auth:sanctum`).
**Response 200:** el modelo `Ninio` autenticado, serializado por defecto (todos los atributos del modelo, sin ocultar ninguno explícitamente vía `$hidden`).
**Response 401:** `{"message": "Unauthenticated."}` si falta o es inválido el token.

**Impacto móvil:** **no consumido** por `CactuApp` (confirmado por búsqueda exhaustiva en `docs/auditoria/matriz-api-movil.md`) — la app reutiliza los datos obtenidos en el login durante toda la sesión y nunca los refresca. Si se decide usarlo en el futuro, evaluar si conviene ocultar campos del modelo `Ninio` antes de exponerlo tal cual. Nota (2026-08-08, `FIREBASE-002`): `fcm_token` sigue apareciendo en la serialización por defecto, pero es una columna deprecada — ya no se escribe desde código nuevo (ver `dispositivos` más abajo).

---

### `POST /actualizar-token-fcm` (también `POST /v1/actualizar-token-fcm`)

Fase 7 (paso 1, roadmap, 2026-08-08): registra/reasigna un dispositivo del niño autenticado, sin pasar por un login nuevo. Pensado para que el móvil lo llame desde `messaging().onTokenRefresh()` cuando Firebase rota el token en background (`docs/movil/cambios-requeridos.md`, ítem #10/#11). Desde `FIREBASE-002` (2026-08-08), internamente hace un upsert en la tabla `dispositivos` por `fcm_token` (`App\Actions\RegistrarDispositivoAction`) — si el mismo token ya estaba asociado a otro niño, se reasigna.

**Autenticación requerida:** `Bearer <token>` (`auth:sanctum`).
**Permisos:** ninguno adicional — solo actúa sobre el niño resuelto por el propio token Sanctum, nunca por un id recibido del cliente (mismo patrón que `SEG-004`/`SEG-005`).
**Request:**
```json
{ "fcm_token": "token FCM del dispositivo" }
```
`fcm_token`: `required|string` (`App\Http\Requests\ActualizarTokenFcmRequest`).

**Response 200 (éxito):**
```json
{ "success": "Token FCM actualizado correctamente" }
```
**Response 422 (validación):** formato estándar de Laravel si `fcm_token` falta o está vacío.
**Response 401:** si falta o es inválido el token Sanctum.

**Impacto móvil:** endpoint nuevo, sin consumidor todavía en `CactuApp` — requiere que el móvil agregue el listener `onTokenRefresh` (fuera del alcance de este repositorio, ver `docs/movil/cambios-requeridos.md` ítem #10).

---

### `POST /eliminar-token-fcm` (también `POST /v1/eliminar-token-fcm`)

Fase 7 (paso 1, roadmap, 2026-08-08): elimina un dispositivo puntual del niño autenticado. Pensado para llamarse antes de cerrar sesión, ya que la app hoy no tiene endpoint de logout ni notifica al backend al desloguear (`docs/movil/firebase.md`).

**Cambio de contrato (`FIREBASE-002`, 2026-08-08):** antes borraba "el único token que había" sin recibir body; ahora que un niño puede tener varios dispositivos, **exige `fcm_token` en el body** para identificar cuál borrar. Sin impacto real porque el endpoint no tiene consumidor móvil todavía.

**Autenticación requerida:** `Bearer <token>` (`auth:sanctum`).
**Permisos:** ninguno adicional — mismo alcance que el endpoint anterior; solo puede borrar dispositivos del propio niño autenticado, nunca de otro.
**Request:**
```json
{ "fcm_token": "token FCM del dispositivo a eliminar" }
```
`fcm_token`: `required|string` (`App\Http\Requests\EliminarTokenFcmRequest`).

**Response 200 (éxito):**
```json
{ "success": "Token FCM eliminado correctamente" }
```
**Response 422 (validación):** formato estándar de Laravel si `fcm_token` falta o está vacío.
**Response 401:** si falta o es inválido el token Sanctum.

**Impacto móvil:** endpoint nuevo, sin consumidor todavía en `CactuApp` — la app no tiene flujo de logout que lo llame hoy (ver `docs/movil/cambios-requeridos.md` ítem #1, sin relación directa pero mismo vacío de "logout" en el móvil). Cuando se implemente, debe mandar el valor cacheado en `AsyncStorage['fcmtoken']` en el body.

**Actualización (2026-08-08):** manejo de tokens inválidos/reintentos (`FIREBASE-001`) y migración a tabla de dispositivos para multi-dispositivo por niño (`FIREBASE-002`) — ambos puntos, que en su momento quedaron pendientes, ya están resueltos. Con esto, Fase 7 del roadmap queda cerrada. Ver `docs/fases/roadmap.md` y `docs/auditoria/problemas-detectados.md`.
