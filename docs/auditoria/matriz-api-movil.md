# Matriz API ↔ React Native

> Auditoría cruzada Fase 0.1. Compara `routes/api.php` (backend real) contra las llamadas encontradas en `/home/persontech/workspace/CactuApp` (ver `api-consumida-por-movil.md` para el detalle de cada fila). Estados según convención del encargo: `OK`, `NO CONSUMIDO`, `CONSUMIDO PERO DIFERENTE`, `BACKEND SIN DOCUMENTACIÓN`, `MÓVIL SIN DOCUMENTACIÓN`, `POSIBLE INCOMPATIBILIDAD`.

> **Actualización Fase 5 (ARQ-005, 2026-08-08):** cada endpoint listado abajo bajo `/api/...` también responde de forma idéntica bajo `/api/v1/...` (alias aditivo registrado en `RouteServiceProvider`, ver `docs/cambios/decisiones-arquitectura.md`). CactuApp sigue consumiendo únicamente `/api/...`; no se ha requerido ningún cambio en el móvil.

> **Actualización Fase 7 (FIREBASE-002, 2026-08-08):** `/api/login` internamente registra el dispositivo en la tabla `dispositivos` en vez de escribir una columna simple `fcm_token` — sin cambio de contrato observable para el móvil (mismo request/response). Se agregaron 2 endpoints nuevos (`/api/actualizar-token-fcm`, `/api/eliminar-token-fcm`), listados abajo como **NO CONSUMIDO**: existen desde Fase 7 paso 1, pero `CactuApp` todavía no los llama.

| Endpoint | Backend | React Native | Método | Auth | Request (móvil) | Response (backend) | Estado |
|---|---|---|---|---|---|---|---|
| `/api/login` | Sí | Sí (`Login.js`) | POST | No | `numero_child`, `fcm_token` | `message`, `id`, `numero_child`, `nombres`, `token`, `roles_permisos` | OK |
| `/api/listar-mis-cartas` | Sí | Sí (`inicio/Index.js`) | POST | Sanctum | `userId` (ignorado por backend desde el cierre de SEG-004) | `{data: [...]}` | OK — campo `userId` es ruido inofensivo, ver nota |
| `/api/responder-carta-contestacion` | Sí | Sí (`Contestacion.js`) | POST multipart | Sanctum | `ninio_id` (ignorado), `id`, `imagen`, `detalle`, `nombre_patrocinador`, `agradezco_por`, `te_cuento_que`, `pregunta_al_patrocinador` | `errors`/`error`/`info`/`success` | OK |
| `/api/responder-carta-agradecimiento` | Sí | Sí (`Agradecimiento.js`) | POST multipart | Sanctum | ídem + `regalo_usar_para` | ídem | OK |
| `/api/responder-carta-iniciada` | Sí | Sí (`Iniciada.js`) | POST multipart | Sanctum | ídem + `en_cactu_aprendi` | ídem | OK |
| `/api/responder-carta-presentacion-mayores` | Sí | Sí (`PresentacionMayores.js`) | POST multipart | Sanctum | `userId` (ignorado), `id`, `imagen`, `foto_familia` + 21 campos | ídem | OK — único caso con validación cliente/servidor 1:1 |
| `/api/responder-carta-presentacion-menores` | Sí | Sí (`PresentacionMenores.js`) | POST multipart | Sanctum | `userId` (ignorado), `id`, `imagen`, `foto_familia` + 24 campos, incl. `mifamila` (typo compartido con el backend) | ídem | OK |
| `/api/enviar-mensaje` | Sí (placeholder, retorna JSON fijo) | **No** | POST | Sanctum | — | `{"success":"Carta registrado exitosamente"}` | **NO CONSUMIDO** |
| `/api/eliminar-carta` | Sí | Sí (`DescargarEliminar.js`) | POST | Sanctum | `id` | `errors`/`success` | OK |
| `/api/dcpdf/{id}` | Sí | Sí, indirecto (campo `carta`) | GET | Público (ID cifrado, SEG-003 resuelto) | — (solo sigue el URL recibido) | Binario PDF | OK |
| `/api/ver-archivo-pdf-por-ninio/{id}` | Sí | Sí, indirecto (campo `archivo`) | GET | Público (ID cifrado) | — | Binario PDF | OK |
| `/api/user` | Sí (estándar Sanctum) | **No** (la app reusa los datos del login) | GET | Sanctum | — | Usuario/niño autenticado | **NO CONSUMIDO** |
| `/api/actualizar-token-fcm` | Sí (Fase 7) | **No** | POST | Sanctum | `fcm_token` | `success` | **NO CONSUMIDO** |
| `/api/eliminar-token-fcm` | Sí (Fase 7) | **No** | POST | Sanctum | `fcm_token` (desde `FIREBASE-002`) | `success` | **NO CONSUMIDO** |

## Hallazgos de la matriz

1. **NO CONSUMIDO — `/api/enviar-mensaje`:** ningún archivo del móvil lo invoca. Combinado con que el backend lo implementa como placeholder fijo (no persiste nada), es candidato a **código muerto en ambos lados** — confirmar con negocio si la funcionalidad "mensajería" fue planeada y descartada, o si sigue pendiente de construir en el móvil.
2. **NO CONSUMIDO — `/api/user`:** la app nunca refresca los datos del niño autenticado tras el login inicial; toda la sesión vive de lo que `AsyncStorage` guardó una sola vez. No es un problema de seguridad, pero si el backend cambiara `nombres_completos` de un niño, la app no lo reflejaría hasta el siguiente login.
3. **Campos "ignorados" (`userId`/`ninio_id` en 6 de las 8 llamadas POST protegidas):** desde que se cerraron SEG-004 y SEG-005 (ver `docs/cambios/changelog.md`), el backend ya no lee estos valores del body — usa siempre `$request->user()` o compara `$carta->ninio_id` contra el niño del token. El cliente sigue enviándolos por costumbre/código heredado. **No representa un riesgo** (el backend los ignora), pero es deuda técnica menor a limpiar en un futuro release móvil, listada en `docs/movil/cambios-requeridos.md`.
4. **Inconsistencia de nombre de campo:** `Contestacion`/`Agradecimiento`/`Iniciada` envían `ninio_id`; `PresentacionMayores`/`PresentacionMenores` envían `userId`. Ninguno de los dos es leído por el backend actualmente, así que no rompe nada, pero refleja que las 5 pantallas de "responder carta" fueron escritas sin una capa compartida (confirma la ausencia de una Action/Service único mencionada como ARQ-001 pendiente en el roadmap, Fase 4).
5. **Ningún endpoint está `BACKEND SIN DOCUMENTACIÓN` ni `MÓVIL SIN DOCUMENTACIÓN`** en sentido estricto: ~~los 12 endpoints~~ (hoy 14, tras agregar los 2 de Fase 7) de `routes/api.php` fueron localizados y los 10 endpoints realmente usados por el móvil coinciden exactamente con rutas existentes del backend. No se encontró ninguna URL hardcodeada en el móvil que no correspondiera a una ruta real (sin URLs "fantasma").
6. **Ningún endpoint quedó clasificado como `POSIBLE INCOMPATIBILIDAD`** ni `CONSUMIDO PERO DIFERENTE`: para las 10 llamadas activas, los nombres de campos que el backend sí valida (`request->validate([...])`) coinciden con lo que el cliente envía. Las únicas discrepancias de forma están en los campos ignorados (punto 3), que no generan error de validación porque no son `required` en el backend.
