# Integración con React Native (auditoría desde el backend)

> El repositorio de la app móvil **no forma parte de este repositorio** y no fue auditado directamente. Todo lo aquí documentado se infiere del backend Laravel (`routes/api.php`, controladores `Api\*`, modelo `Ninio`). Antes de modificar cualquier endpoint listado aquí, confirmar con el código fuente real de la app móvil qué campos consume, según exige `CLAUDE.md` sección 22.

> **Nota de actualización (auditoría cruzada Fase 0.1, 2026-08-08):** el repositorio móvil (`CactuApp`) sí fue auditado directamente en esta fase posterior. Ver `docs/auditoria/movil-arquitectura-actual.md`, `api-consumida-por-movil.md`, `matriz-api-movil.md`, `firebase-movil-actual.md` y `docs/movil/`. En particular, la pregunta pendiente de la sección 7 sobre SEG-004 ya quedó resuelta: SEG-004 y SEG-005 fueron cerrados en el backend (`docs/cambios/changelog.md`) y **no requirieron ningún cambio en el móvil** — el cliente sigue enviando `userId`/`ninio_id`, pero el backend ya no los usa para autorizar nada.

## 1. Identidad del "usuario" móvil

El consumidor de la API móvil es el **niño patrocinado** (`App\Models\Ninio`), no un `User` (gestor/administrador). `Ninio` extiende `Illuminate\Foundation\Auth\User as Authenticatable` y usa `HasApiTokens` (Sanctum), lo que le permite tener tokens personales igual que un usuario normal, aunque no participa del sistema de roles (`spatie/laravel-permission`) ni del guard `web`.

## 2. Autenticación

**Endpoint:** `POST /api/login`
**Definido en:** closure inline en `routes/api.php` (no en un Controller).

**Request esperado:**
```json
{
  "numero_child": "EC-000123",
  "fcm_token": "opcional, token de push de Firebase"
}
```

**Response éxito (200):**
```json
{
  "message": "ok",
  "id": 1,
  "numero_child": "EC-000123",
  "nombres": "Nombre Completo del Niño",
  "token": "<plain text sanctum token>",
  "roles_permisos": []
}
```
`roles_permisos` siempre se retorna como arreglo vacío — el niño no tiene roles/permisos en este sistema; revisar con el equipo móvil si este campo es realmente consumido o es vestigial.

**Response error (422):** `{"errors": {"email": ["Número child no existe.!."]}}` — nótese que la clave de error es `email` aunque el campo validado es `numero_child`; posible copia-pega de un formulario de login estándar sin adaptar. Confirmar con el equipo móvil si el cliente lee esta clave específica antes de "corregirla".

**Seguridad:** no hay contraseña ni PIN — ver `seguridad.md` SEG-012. Cualquiera que conozca un `numero_child` válido obtiene un token completo para ese niño.

## 3. Autorización en rutas API

Todas las rutas de negocio bajo `/api/*` (excepto login y las dos de descarga de PDF) requieren header `Authorization: Bearer <token>` y usan `middleware('auth:sanctum')`.

## 4. Endpoints consumidos por la app (inferidos)

Ver detalle completo en `rutas-api.md`. Resumen orientado al desarrollador móvil:

| Endpoint | Método | Requiere token | Uso esperado en la app |
|---|---|---|---|
| `/api/login` | POST | No | Pantalla de inicio de sesión del niño |
| `/api/listar-mis-cartas` | POST | Sí | Pantalla principal — listado de cartas (pendientes y respondidas) |
| `/api/responder-carta-contestacion` | POST | Sí | Formulario de respuesta tipo "Contestación" |
| `/api/responder-carta-agradecimiento` | POST | Sí | Formulario tipo "Agradecimiento" |
| `/api/responder-carta-iniciada` | POST | Sí | Formulario tipo "Iniciada" |
| `/api/responder-carta-presentacion-mayores` | POST | Sí | Formulario de presentación (niño mayor) |
| `/api/responder-carta-presentacion-menores` | POST | Sí | Formulario de presentación (niño menor) |
| `/api/eliminar-carta` | POST | Sí | Ocultar/eliminar una carta de la lista (soft flag `eliminado`) |
| `/api/enviar-mensaje` | POST | Sí | Función aparentemente no implementada en backend (retorna JSON fijo) — **confirmar con el equipo móvil si esta pantalla existe y qué se espera que haga** |
| `/api/dcpdf/{id}` | GET | No | Descarga del PDF de una carta ya respondida (enlace `carta` en `listaCartas`) |
| `/api/ver-archivo-pdf-por-ninio/{id}` | GET | No | Descarga del PDF de la carta original enviada al niño (enlace `archivo` en `listaCartas`), con ID cifrado |
| `/api/user` | GET | Sí | Endpoint estándar de Sanctum para obtener el usuario/niño autenticado |

## 5. Campos consumidos por `listar-mis-cartas` (contrato de respuesta)

```json
{
  "data": [
    {
      "created_at": "08/08/2026 10:00:00",
      "id": 1,
      "asunto": "string",
      "tipo_carta_nombre": "Contestación | Agradecimiento | Presentación | Iniciada",
      "estado": "Enviado | Respondida",
      "edad": 10,
      "archivo": "URL o 'no'",
      "carta": "URL o 'no'",
      "carta_nombre": "string o vacío",
      "eliminado": false
    }
  ]
}
```
Cualquier cambio a esta forma de respuesta debe tratarse como **ruptura de contrato** con la app móvil (`CLAUDE.md` sección 22-23) y coordinarse antes de desplegar.

## 6. Firebase / Notificaciones push

> **Actualizado (Fase 7, 2026-08-08):** esta sección describía el estado previo a `FIREBASE-001`/`FIREBASE-002`. Ver `docs/auditoria/problemas-detectados.md` para el detalle completo; se deja el resto del punteo corregido inline.

- ~~El niño registra su `fcm_token` en el propio `POST /api/login` (se sobrescribe en cada login).~~ El niño sigue registrando su token en `POST /api/login`, pero ahora se traduce en un registro en la tabla `dispositivos` (upsert por token, vía `RegistrarDispositivoAction`) en vez de sobrescribir una columna — si el mismo token ya estaba asociado a otro niño (p. ej. hermanos compartiendo un teléfono), se reasigna en vez de duplicarse.
- ~~El backend solo **envía** notificaciones...; no hay endpoint API dedicado para que la app actualice el token fuera del login, ni para eliminarlo al cerrar sesión/desinstalar.~~ **Resuelto:** existen `POST /api/actualizar-token-fcm` y `POST /api/eliminar-token-fcm` desde Fase 7 paso 1 — sin consumidor móvil todavía.
- ~~No hay soporte para múltiples dispositivos por niño... **Implementación parcial.**~~ **Resuelto (`FIREBASE-002`).** Tabla `dispositivos`, sin requerir ningún cambio del lado móvil.
- El envío en sí también se reescribió por completo: el paquete original (`kutia-software-company/larafirebase`) usaba la API legacy de FCM, decomisionada por Google desde 2024 — se migró a `kreait/laravel-firebase` (FCM HTTP v1), verificado con un envío real recibido en un dispositivo (`FIREBASE-001`).

## 7. Información pendiente de obtener del repositorio móvil

Para completar esta documentación se necesita, del lado de React Native:

- Confirmar si `roles_permisos` y la clave de error `email` en el login se usan realmente o son vestigiales.
- Confirmar el uso real de `/api/enviar-mensaje`.
- Confirmar cómo maneja la app la ausencia de `fcm_token` (permisos de notificación denegados).
- Confirmar la versión mínima de la app en producción y si ya tolerará cambios de contrato antes de aplicar las correcciones de seguridad de `seguridad.md` (especialmente SEG-004, que cambia `listaCartas` para dejar de leer `userId` del body).
