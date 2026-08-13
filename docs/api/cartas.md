# Cartas

> Verificado contra `app/Http/Controllers/Api/ResponderCarta.php`, las 6 Actions en `app/Actions/` y los 5 `FormRequest` en `app/Http/Requests/Cartas/`. Todas las rutas de este documento están dentro del grupo `Route::middleware(['auth:sanctum'])` de `routes/api.php` — requieren `Authorization: Bearer <token>` en todos los casos.

---

### `POST /listar-mis-cartas` (también `/v1/listar-mis-cartas`)

Lista las cartas del niño autenticado (resuelto por el token, no por ningún parámetro del body — ver SEG-004).

**Autenticación:** `Bearer <token>`. **Permisos:** ninguno adicional — cada niño solo puede ver las suyas, sin excepción de rol.
**Request:** cualquier body es aceptado; el campo `userId` que el cliente envía (heredado) se ignora por completo.

**Response 200:**
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
      "archivo": "URL absoluta cifrada, o 'no'",
      "carta": "URL absoluta cifrada, o 'no'",
      "carta_nombre": "Carta-de-<Tipo>-<id>.pdf, o cadena vacía",
      "eliminado": false
    }
  ]
}
```

**Reglas de negocio:**
- Excluye cartas con `eliminado = true` (borrado lógico, ver `eliminar-carta` más abajo).
- Orden: primero `estado = 'Enviado'`, luego el resto; dentro de cada grupo, más recientes primero.
- `edad` se recalcula en cada petición a partir de `fecha_nacimiento` del niño (no es un valor guardado en la carta).
- `archivo` solo es distinto de `'no'` si la carta tiene `archivo_pdf` (el documento que el gestor adjuntó al crear la carta). `carta` solo es distinto de `'no'` si `estado === 'Respondida'` (el PDF de respuesta generado por el sistema). Ver `documentos.md` para las dos rutas de descarga referenciadas.

**Impacto móvil:** contrato activo, consumido por `Index.js`. `estado` y `tipo_carta_nombre` se usan para lógica condicional en la UI — cualquier cambio de estos valores es ruptura de contrato.

---

### `POST /responder-carta-contestacion`, `/responder-carta-agradecimiento`, `/responder-carta-iniciada`

**Autenticación:** `Bearer <token>`. **Permisos:** el `ninio_id` de la carta debe coincidir con el niño del token (`SEG-005`); si no, `403`.
**Content-Type:** `multipart/form-data` (llevan archivo).
**Form Request:** `ResponderCarta{Contestacion|Agradecimiento|Iniciada}Request` — mismas reglas para web y API, ver `docs/cambios/decisiones-arquitectura.md` ("Form Requests para responder-carta").

**Campos `required` (validados por el `FormRequest`, los 3 endpoints comparten la misma regla mínima):** `id`, `imagen`, `detalle`.
**Campos aceptados pero no `required`** (el Controller los lee del `Request` y los pasa tal cual a la Action, sin validar presencia): `nombre_patrocinador`, `te_cuento_que`, `pregunta_al_patrocinador`, `agradezco_por` (Contestación y Agradecimiento, no en Iniciada), `foto_familia` (mismo tratamiento que `imagen`: si viene y es un archivo válido, se pasa a la Action; si no, `null`). Específico de Agradecimiento: `regalo_usar_para`. Específico de Iniciada: `en_cactu_aprendi`.
**Campos ignorados por el backend (enviados por el cliente por herencia):** `ninio_id`.

**Responses posibles (los 3 endpoints comparten la misma lógica vía `procesarRespuesta()`):**

| Caso | HTTP | Body |
|---|---|---|
| Éxito | 200 | `{"success": "Carta resgitrado exitosamente"}` — typo real (`resgitrado`), no corregir sin confirmar que el cliente no lo compara literal |
| Carta de otro niño | 403 | `{"error": "No autorizado"}` |
| Carta ya respondida | 200 | `{"info": "Carta ya fue respondida"}` — no es un error, no persiste nada |
| Falta un campo requerido | 422 | `{"errors": {"campo": ["..."]}}` (formato estándar de `FormRequest`) |
| Excepción no controlada | 200 | `{"error": "Carta no registrado vuelva intentar"}` — nótese que este caso también devuelve HTTP 200, no 4xx/5xx |

**Reglas de negocio:** la resolución de la carta y el guardado (imagen, `estado`, `fecha_respondida`, notificación al gestor) están centralizados en `GuardarRespuestaCartaAction`, compartida por los 5 endpoints de "responder carta" (web y API) — ver ARQ-001 en `docs/cambios/decisiones-arquitectura.md`. El armado del texto `detalle` es específico por tipo (una Action por tipo).

**Impacto móvil:** contrato activo. `Contestacion.js`, `Agradecimiento.js`, `Iniciada.js`.

---

### `POST /responder-carta-presentacion-mayores`, `/responder-carta-presentacion-menores`

Igual patrón que el grupo anterior (`multipart/form-data`, mismas responses de la tabla de arriba), con dos diferencias:

- **Campos, todos `required`:** `imagen`, `foto_familia`, `id`, más 21 campos de texto en Mayor / 25 en Menor, específicos del formulario (ver `ResponderCartaPresentacionMayorRequest`/`ResponderCartaPresentacionMenorRequest` para la lista exacta de nombres — son nombres de campo arbitrarios heredados del formulario original, ej. `hola`, `soy`, `meDicen`, no nombres semánticos). Es el único par de endpoints donde la validación del `FormRequest` coincide 1:1 con lo que el cliente realmente envía (todo `required`, sin campos opcionales).
- **Campo con typo compartido cliente/backend:** `mifamila` (sin "i") en Presentación Menor — el `FormRequest` valida exactamente ese nombre porque es lo que envía `PresentacionMenores.js`. No corregir sin coordinar (ver decisión de arquitectura de Form Requests).
- El cliente elige uno u otro endpoint según `edad > 5` (lógica en `Presentacion.js`, no en el backend).

**Impacto móvil:** contrato activo. `PresentacionMayores.js`, `PresentacionMenores.js`.

---

### `POST /enviar-mensaje` (también `/v1/enviar-mensaje`)

**Autenticación:** `Bearer <token>`. **Estado real:** placeholder — no lee el request, no persiste nada, siempre responde:
```json
{"success": "Carta registrado exitosamente"}
```
**No consumido por ninguna pantalla de `CactuApp`** (verificado por búsqueda exhaustiva). No construir funcionalidad nueva asumiendo que este endpoint hace algo — confirmar con negocio si "mensajería" es una funcionalidad planeada y descartada o pendiente de construir en ambos lados.

---

### `POST /eliminar-carta` (también `/v1/eliminar-carta`)

Borrado lógico (`eliminado = true`), no elimina el registro.

**Autenticación:** `Bearer <token>`. **Permisos:** mismo chequeo de pertenencia que los endpoints de responder (`SEG-005`).
**Request:** `{"id": 1}` (`required`).
**Response 200 (éxito):** `{"success": "Carta eliminado exitosamente.!"}`.
**Response 403:** `{"error": "No autorizado"}` (carta de otro niño).
**Response 404:** si el `id` no existe (`findOrFail`, sin capturar — devuelve la página de error estándar de Laravel para API, no un JSON custom).

**Impacto móvil:** contrato activo. `DescargarEliminar.js`.
