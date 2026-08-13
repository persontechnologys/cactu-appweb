# Inventario de llamadas HTTP realizadas por CactuApp

> Auditoría cruzada Fase 0.1. Cada llamada fue localizada por lectura directa del código fuente de `/home/persontech/workspace/CactuApp` (no por suposición). No hay cliente HTTP centralizado: cada pantalla construye su propia petición con `fetch` (JSON) o `ReactNativeBlobUtil.fetch`/`RNFetchBlob` (multipart / descarga de archivos), concatenando `API_URL` + nombre del endpoint.

## Cliente HTTP

- **Librería para JSON:** `fetch` nativo de React Native (Login, listar-mis-cartas, eliminar-carta).
- **Librería para multipart (envío de fotos):** `react-native-blob-util` (`ReactNativeBlobUtil.fetch`) — usada en las 5 pantallas de respuesta de carta.
- **Librería para descarga de binarios:** `rn-fetch-blob` (`RNFetchBlob.fs`/`.config().fetch()`) — usada en `Contestacion.js` y `DescargarEliminar.js` para bajar el PDF a `DownloadDir/cartas-cactu/{id}.pdf` antes de mostrarlo con `react-native-pdf`.
- **Base URL:** `API_URL` desde `.env` vía `react-native-dotenv` (`https://cactu.ec/api/`), concatenada manualmente (`API_URL + "login"`, etc.) — no hay instancia de axios ni interceptor.
- **Headers estándar:** `Accept: application/json`, `Content-Type: application/json` (JSON) o `multipart/form-data` (envíos con foto). `Authorization: Bearer ${userToken}` se agrega manualmente en cada llamada que lo requiere — no hay interceptor global, por lo que cada pantalla es responsable de no olvidarlo.
- **Timeouts:** no configurados (**No implementado actualmente**).
- **Reintentos:** no implementados.
- **Refresh token:** no existe (Sanctum con `expiration => null`, ver `movil-arquitectura-actual.md`).
- **Manejo de errores HTTP:** ver `docs/movil/manejo-errores.md` — ninguna pantalla inspecciona `res.status`; todas dependen de la forma del JSON de respuesta (`errors`, `error`, `info`, `success`, `message`).

## Inventario detallado por llamada

### 1. `POST /login`

- **Archivo:** `src/view/auth/Login.js:25`
- **Autenticación:** ninguna (pantalla pública).
- **Request:**
  ```json
  { "numero_child": "string tecleado con teclado numérico en pantalla", "fcm_token": "valor de AsyncStorage['fcmtoken'] o null" }
  ```
- **Response esperada:** `data.errors` (objeto, se muestra cada valor en un toast) o `data.message === 'ok'` → dispara `signIn(data)` con `data.token`, `data.nombres`, `data.id`, `data.numero_child`, `data.roles_permisos`.
- **Manejo de errores:** solo si `data.errors` existe; una respuesta HTTP no-2xx sin ese formato no se distingue de un login fallido silencioso.
- **Pantalla:** `Login`.

### 2. `POST /listar-mis-cartas`

- **Archivo:** `src/view/inicio/Index.js:18`
- **Autenticación:** `Authorization: Bearer ${userToken}`.
- **Request:** `{ "userId": <valor de AuthContext.userId, string> }` — **este campo ya no es usado por el backend** (ver SEG-004, resuelto en `docs/cambios/changelog.md`; el backend usa `$request->user()`). El cliente sigue enviándolo, sin efecto funcional negativo, pero es código muerto a limpiar en un futuro release.
- **Response esperada:** `{ "data": [ { id, created_at, asunto, tipo_carta_nombre, estado, edad, archivo, carta, carta_nombre, eliminado } ] }`.
- **Pantalla:** `inicio/Index` (pantalla principal, `pull-to-refresh` y recarga automática al volver de responder/eliminar una carta vía `route.params.estado`, un valor aleatorio usado solo como trigger de `useEffect`).
- **Navegación derivada:** según `estado`/`tipo_carta_nombre` de cada carta, navega a `DescargarEliminar`, `Presentacion`, `Agradecimiento`, `Iniciada` o `Contestacion` (default), pasando el objeto `carta` completo como `route.params`.

### 3. `POST /responder-carta-contestacion`

- **Archivo:** `src/view/cartas/Contestacion.js:138`
- **Autenticación:** `Authorization: Bearer ${userToken}` + `multipart/form-data` vía `ReactNativeBlobUtil`.
- **Request (multipart):** `ninio_id` (=`userId`, **no usado por backend**, ver nota), `id`, `imagen` (foto tomada con cámara, base64), `detalle` (=`texto`), `nombre_patrocinador`, `agradezco_por`, `te_cuento_que`, `pregunta_al_patrocinador`.
- **Validación previa en cliente (`validar()`):** exige que los 6 campos de texto + la foto no estén vacíos, antes de permitir el envío — **duplica** (parcialmente) la regla `required` que el backend aplica en `guardarCarta` (`id`, `imagen`, `detalle`), pero el cliente valida más campos de los que el backend realmente exige como `required` (el backend concatena `nombre_patrocinador`/`agradezco_por`/etc. dentro de `detalle` sin volver a validarlos individualmente).
- **Response esperada:** `result.errors` (objeto de validación) / `result.error` (string, p.ej. `"No autorizado"` si la carta no pertenece al niño — SEG-005) / `result.info` (p.ej. carta ya respondida) / `result.success` → navega de regreso a `Inicio` con un parámetro aleatorio para forzar recarga.
- **Extra:** antes de mostrar el formulario, descarga el PDF original de la carta (`archivo`, recibido de `listar-mis-cartas`) a almacenamiento local con `RNFetchBlob` y lo muestra con `react-native-pdf`.
- **Pantalla:** `Contestacion`.

### 4. `POST /responder-carta-agradecimiento`

- **Archivo:** `src/view/cartas/Agradecimiento.js:110`
- **Request (multipart):** igual patrón que Contestación, más `regalo_usar_para`. Campo `ninio_id` (no usado por backend).
- **Diferencia de UX:** no descarga/muestra el PDF original embebido; en su lugar ofrece un `<Link href={archivo}>` que abre el navegador del sistema si `archivo !== 'no'`.
- **Pantalla:** `Agradecimiento`.

### 5. `POST /responder-carta-iniciada`

- **Archivo:** `src/view/cartas/Iniciada.js:104`
- **Request (multipart):** `ninio_id`, `id`, `imagen`, `detalle`, `nombre_patrocinador`, `en_cactu_aprendi`, `te_cuento_que`, `pregunta_al_patrocinador`.
- **Pantalla:** `Iniciada`.

### 6. `POST /responder-carta-presentacion-mayores`

- **Archivo:** `src/view/cartas/PresentacionMayores.js:147`
- **Se alcanza desde:** `Presentacion.js`, cuando `route.params.edad > 5`.
- **Request (multipart):** 21 campos de texto (`hola`, `soy`, `meDicen`, `edad`, `miMejorAmigo`, `esMejorAmigo`, `loquehago`, `miSueno`, `dondeAprendo`, `gustaAprendes`, `mePaso`, `meGustaria`, `miFamilia`, `nuestraPro`, `idioma`, `lugarFavorito`, `comidaTipica`, `comer`, `masMeGusta`, `pregunta`, `despedida`) + `imagen` + `foto_familia` + **`userId`** (no `ninio_id` — inconsistencia de nombre respecto a las otras 3 pantallas, ambos ignorados por el backend actual) + `id`.
- **Validación cliente:** exige los 21 campos de texto + 2 fotos no vacíos — coincide 1:1 con las reglas `required` del backend (`responderCartaPresentacionMayor`, `routes` en `Api/ResponderCarta.php:54-79`). Es la única pantalla donde la validación cliente/servidor está completamente alineada campo a campo.
- **Pantalla:** `PresentacionMayores`.

### 7. `POST /responder-carta-presentacion-menores`

- **Archivo:** `src/view/cartas/PresentacionMenores.js:164`
- **Se alcanza desde:** `Presentacion.js`, cuando `route.params.edad <= 5`.
- **Request (multipart):** 24 campos de texto + `imagen` + `foto_familia` + `userId` (no usado) + `id`. **Nota:** el campo se envía como `mifamila` (sin la segunda "i"), que coincide exactamente con el typo del backend (`'mifamila'=>'required'` en `Api/ResponderCarta.php:134`) — validado como intencional/consistente, no un bug a corregir unilateralmente en el cliente sin coordinar el backend.
- **Pantalla:** `PresentacionMenores`.

### 8. `POST /enviar-mensaje`

- **No se encontró ninguna llamada a este endpoint en `CactuApp`.** El backend lo expone (`Api\ResponderCarta::enviarMensaje`, retorna JSON fijo `{"success":"Carta registrado exitosamente"}`), pero ninguna pantalla, servicio ni utilidad del móvil lo invoca. Confirma la sospecha registrada en `integracion-movil.md` sección 7: es un endpoint sin consumidor conocido — posible funcionalidad no implementada en el cliente o ya retirada.

### 9. `POST /eliminar-carta`

- **Archivo:** `src/view/cartas/DescargarEliminar.js:84`
- **Request:** `{ "id": <id de la carta> }` (JSON simple, sin `userId`/`ninio_id` — consistente con que el backend ya verifica pertenencia vía `$request->user()`, SEG-005).
- **Flujo UX:** requiere confirmación (`Alert.alert`) antes de llamar.
- **Response esperada:** `data.errors` / `data.success` → navega a `Inicio` con recarga.
- **Pantalla:** `DescargarEliminar`.

### 10. `GET /dcpdf/{id}` (descarga de carta ya respondida)

- **Archivo:** `src/view/cartas/DescargarEliminar.js:37-45` (campo `carta` del objeto recibido de `listar-mis-cartas`).
- **Autenticación:** ninguna en la URL en sí — es un enlace directo firmado con `Crypt::encryptString` (SEG-003, resuelto). El cliente no construye el ID, solo sigue el URL devuelto por el backend.
- **Cómo se consume:** `RNFetchBlob.config({fileCache:true, path: DownloadDir + '/cartas-cactu/' + id + '.pdf'}).fetch('GET', fileUrl)`, luego se muestra con `<Pdf source={{uri: urlCarta}} />`.
- **Manejo de 403/404:** solo `.catch()` genérico → toast `"Error al descargar el archivo"`, sin distinguir el código HTTP real.

### 11. `GET /ver-archivo-pdf-por-ninio/{id}` (carta original del patrocinador)

- **Consumido indirectamente:** el campo `archivo` de `listar-mis-cartas` apunta a esta ruta (ID ya cifrado por el backend). Se usa como `<Pdf source={{uri: archivo}}>` en `Contestacion.js` (tras descarga previa con `RNFetchBlob`) y como `<Link href={archivo}>` en `Agradecimiento.js`/`Iniciada.js`.

### 12. `GET /user`

- **No se encontró ninguna llamada explícita a `GET /user` en `CactuApp`.** El endpoint estándar de Sanctum existe en el backend, pero la app obtiene los datos del usuario únicamente de la respuesta de `/login`, persistidos en `AsyncStorage`/`AuthContext` — nunca se re-consulta al servidor durante la sesión. Esto significa que si el nombre del niño cambiara en el backend, la app seguiría mostrando el valor obtenido en el login hasta el próximo logout/login.

## Resumen de endpoints backend sin consumidor móvil confirmado

| Endpoint | Estado |
|---|---|
| `POST /enviar-mensaje` | Backend lo expone, **ningún archivo de `CactuApp` lo invoca** |
| `GET /user` | Backend lo expone, **ningún archivo de `CactuApp` lo invoca** (el móvil reusa los datos del login) |

Ver matriz completa con estado OK/NO CONSUMIDO/etc. en `matriz-api-movil.md`.
