# Contrato API consumido por CactuApp

> Solo se documentan aquí los campos realmente encontrados en el código (ninguno inventado), conforme a `CLAUDE.md` sección 28. Para el detalle exhaustivo (archivo/línea, manejo de errores por pantalla) ver `docs/auditoria/api-consumida-por-movil.md` en el repositorio backend. Para la comparación completa contra `routes/api.php`, ver `docs/auditoria/matriz-api-movil.md`.

Base URL: `API_URL` (`.env`, `@env`) — `https://cactu.ec/api/` en producción.

> **Fase 5 (ARQ-005, 2026-08-08):** todas las rutas de esta página también existen bajo `/api/v1/...` (alias aditivo, mismo comportamiento — ver `docs/cambios/decisiones-arquitectura.md`). CactuApp **no necesita cambiar `API_URL` todavía**: `/api/...` sigue funcionando sin fecha de retiro definida. Excepción a tener en cuenta si en el futuro se migra a `/api/v1/`: las URLs de descarga de PDF que llegan en los campos `archivo`/`carta` de `listar-mis-cartas` siempre apuntan a `/api/dcpdf/...` y `/api/ver-archivo-pdf-por-ninio/...` (sin `v1`), sin importar qué prefijo haya usado el cliente para pedir la lista — es un efecto colateral aceptado, no un bug.

---

### `POST /login`

**Autenticación:** ninguna. **Archivo móvil:** `src/view/auth/Login.js`.

Request:
```json
{ "numero_child": "EC-000123", "fcm_token": "token FCM o null" }
```

Response éxito (200):
```json
{ "message": "ok", "id": 1, "numero_child": "EC-000123", "nombres": "Nombre Completo", "token": "...", "roles_permisos": [] }
```

Response error (422): `{ "errors": { "email": ["Número child no existe.!."] } }` (la clave es `email` por herencia de un formulario genérico, no lo asumas como bug a corregir sin confirmar impacto).

Desde 2026-08-08 (`SEG-014`), si el login no envía `fcm_token`, el backend conserva el valor ya guardado en vez de borrarlo — no requiere ningún cambio del lado móvil, solo deja de ser un riesgo si alguna vez se hace login sin reenviar el valor cacheado.

---

### `POST /listar-mis-cartas`

**Autenticación:** `Bearer <token>`. **Archivo móvil:** `src/view/inicio/Index.js`.

Request: `{ "userId": "<AuthContext.userId>" }` — **ignorado por el backend** (usa el niño del token).

Response:
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
      "archivo": "URL cifrada o 'no'",
      "carta": "URL cifrada o 'no'",
      "carta_nombre": "string o vacío",
      "eliminado": false
    }
  ]
}
```

---

### `POST /responder-carta-contestacion` / `/responder-carta-agradecimiento` / `/responder-carta-iniciada`

**Autenticación:** `Bearer <token>`, `multipart/form-data`. **Archivos móvil:** `Contestacion.js`, `Agradecimiento.js`, `Iniciada.js`.

Campos comunes: `ninio_id` (ignorado), `id`, `imagen` (foto, requerida), `detalle`, `nombre_patrocinador`, `agradezco_por` *(no en Iniciada)*, `te_cuento_que`, `pregunta_al_patrocinador`.
Específico de Agradecimiento: `regalo_usar_para`. Específico de Iniciada: `en_cactu_aprendi` (sin `agradezco_por`).

Response: una de `{"errors": {...}}` (422, validación) / `{"error": "No autorizado"}` (403, carta de otro niño) / `{"info": "Carta ya fue respondida"}` / `{"success": "Carta resgitrado exitosamente"}` (sic — typo real del backend, no lo "corrijas" sin confirmar que el cliente no compara ese string literal).

---

### `POST /responder-carta-presentacion-mayores` / `/responder-carta-presentacion-menores`

**Autenticación:** `Bearer <token>`, `multipart/form-data`. **Archivos móvil:** `PresentacionMayores.js`, `PresentacionMenores.js`. Se elige una u otra desde `Presentacion.js` según `edad > 5`.

Campos: `imagen`, `foto_familia`, `userId` (ignorado, nótese que aquí el nombre es `userId`, no `ninio_id` como en las 3 pantallas anteriores), `id`, más ~21-24 campos de texto específicos del formulario (ver `docs/auditoria/api-consumida-por-movil.md` para la lista completa). El backend valida los mismos campos como `required` — es el único caso con validación cliente/servidor alineada 1:1.

Response: mismo patrón `errors`/`info`/`success` que arriba (sin `error` genérico en este par de métodos).

---

### `POST /enviar-mensaje`

**No consumido por ninguna pantalla de CactuApp** (verificado por búsqueda exhaustiva). El backend responde siempre `{"success":"Carta registrado exitosamente"}` sin persistir nada. No construir funcionalidad nueva asumiendo que este endpoint hace algo — confirmar con negocio primero.

---

### `POST /eliminar-carta`

**Autenticación:** `Bearer <token>`. **Archivo móvil:** `src/view/cartas/DescargarEliminar.js`.

Request: `{ "id": 1 }`. Response: `{"errors": {...}}` / `{"error": "No autorizado"}` (403) / `{"success": "Carta eliminado exitosamente.!"}`.

---

### `GET /dcpdf/{id}` y `GET /ver-archivo-pdf-por-ninio/{id}`

**Autenticación:** ninguna en la URL — el `{id}` va cifrado (`Crypt::encryptString`) y lo genera siempre el backend en la respuesta de `listar-mis-cartas` (campos `carta`/`archivo`). **El cliente nunca construye estos IDs**, solo sigue el URL recibido. No armar manualmente una URL a este endpoint con un ID propio.

---

### `GET /user`

Endpoint estándar de Sanctum, **no consumido** por CactuApp (la app reutiliza los datos obtenidos en el login durante toda la sesión).

---

### `POST /actualizar-token-fcm` y `POST /eliminar-token-fcm`

**Nuevos (2026-08-08, Fase 7 paso 1 del roadmap backend), no consumidos todavía por ninguna pantalla de CactuApp.** Pensados para conectarse a `messaging().onTokenRefresh()` (actualizar) y a un futuro `signOut()` (eliminar) — ver `docs/movil/firebase.md` y `cambios-requeridos.md` ítems #10/#11. Detalle de request/response en `docs/api/autenticacion.md`.

---

## Reglas antes de modificar cualquier respuesta

1. Confirmar en `docs/auditoria/api-consumida-por-movil.md` qué pantalla(s) consumen el campo que se quiere cambiar.
2. Si el campo se usa para navegación o lógica condicional en el móvil (p. ej. `estado`, `tipo_carta_nombre`, `edad`), tratar el cambio como ruptura de contrato (`CLAUDE.md` sección 22-23) y coordinar antes de desplegar.
3. Los campos `userId`/`ninio_id` enviados por el cliente y ya ignorados por el backend son seguros de eliminar del lado backend sin romper nada — pero limpiarlos del lado móvil es opcional/cosmético, no urgente.
