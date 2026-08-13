# Documentos (PDF)

> Verificado contra `app/Http/Controllers/WelcomeController.php`. Ninguna de las 2 rutas está dentro del grupo `auth:sanctum` — su única protección es que el `{id}` va cifrado (`Crypt::encryptString`/`decryptString`), cerrado como `SEG-003` (`docs/auditoria/seguridad.md`). El cliente nunca construye estos IDs: los recibe ya armados en los campos `archivo`/`carta` de la respuesta de `listar-mis-cartas` (ver `cartas.md`).

**Nota de versionado:** a diferencia del resto de la API, estas 2 rutas están registradas con nombre (`Route::name()`) y el resto del backend genera sus URLs con el helper `route()`. Por eso, aunque técnicamente también existen bajo `/api/v1/dcpdf/{id}` y `/api/v1/ver-archivo-pdf-por-ninio/{id}`, las URLs que `listar-mis-cartas` efectivamente devuelve siempre apuntan a la variante `/api/...` (sin `v1`) — ver `docs/cambios/decisiones-arquitectura.md`, entrada "ARQ-005", para el porqué.

---

### `GET /ver-archivo-pdf-por-ninio/{id}`

Sirve el PDF **adjunto por el gestor** al crear la carta (`Carta::archivo_pdf`), tal cual está guardado en `Storage` — no lo genera ni lo transforma.

**Autenticación:** ninguna (protegido solo por `{id}` cifrado).
**Parámetro:** `{id}` — el `id` de la `Carta` cifrado con `Crypt::encryptString`.
**Response 200:** binario, `Storage::response($carta->archivo_pdf)` (headers de descarga los pone el driver de `Storage`).
**Response 200 con error (`{id}` no descifra o no corresponde a ninguna carta):**
```json
{"error": "Carta resgitrado exitosamente"}
```
**Bug real, no es un error de esta documentación:** el mensaje de error es literalmente el texto de éxito de otro endpoint (`procesarRespuesta()` en `cartas.md`), copiado por error a esta rama de `catch`, y la respuesta se envía con HTTP `200`, no `404`/`422`. No se corrige aquí — está fuera del alcance de esta documentación (es un hallazgo de calidad de código, no de contrato), se deja registrado para evaluar en una fase de calidad/performance.

**Impacto móvil:** el cliente nunca construye esta URL manualmente, solo sigue la que recibió — el bug de arriba en la práctica solo se dispararía si alguien manipulara el `{id}` a mano.

---

### `GET /dcpdf/{id}`

Genera y sirve el PDF de **respuesta del niño** (`cartas.verpdf`), regenerado en cada petición — no hay caché ni archivo pre-generado en disco para esta ruta.

**Autenticación:** ninguna (protegido solo por `{id}` cifrado).
**Parámetro:** `{id}` — el `id` de la `Carta` cifrado.
**Response 200:** binario, PDF generado con `snappy`/`wkhtmltopdf` a partir de la vista `cartas.verpdf`, descargado con nombre `CARTA DE <nombres_completos>.pdf`.
**Response 404 (`{id}` no descifra o no corresponde a ninguna carta):** `{"error": "Carta no encontrada"}` — a diferencia de `pdfninio`, este caso sí devuelve el código HTTP correcto.
**Reglas de negocio:** no valida que la carta esté `Respondida` — si se llama con el `id` de una carta sin responder, igual intenta renderizar `cartas.verpdf` con los campos de respuesta vacíos (comportamiento no verificado exhaustivamente aquí; en la práctica el cliente solo recibe esta URL cuando `estado === 'Respondida'`, ver `cartas.md`).

**Impacto móvil:** el cliente nunca construye esta URL manualmente. Confirmar antes de reforzar la validación de `estado` que ningún flujo legítimo dependa del comportamiento actual sin esa restricción.
