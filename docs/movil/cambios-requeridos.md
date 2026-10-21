# Cambios requeridos — móvil (CactuApp)

> Catálogo de brechas encontradas durante la auditoría cruzada Fase 0.1. **Ninguno de estos cambios se implementó.** Requieren autorización explícita y, en varios casos, coordinación entre el equipo backend y el equipo React Native antes de tocarse (`CLAUDE.md` secciones 22 y 48).

## SEGURIDAD

| # | Problema | Backend afectado | Móvil afectado | Acción requerida | Prioridad | Dependencias |
|---|---|---|---|---|---|---|
| 1 | Token Sanctum sin cifrar en `AsyncStorage`, sin expiración (`config/sanctum.php: expiration => null`), sin endpoint de revocación remota | `config/sanctum.php`, falta ruta `/logout` en `routes/api.php` | `App.tsx` (storage), `Login.js`/`Perfil.js` | Evaluar `expiration` en Sanctum + agregar `POST /api/logout` que revoque el token actual (`$request->user()->currentAccessToken()->delete()`) + llamarlo desde `signOut()`; evaluar migrar el token a almacenamiento cifrado (`react-native-keychain` o similar) | MEDIO | Ninguna — no rompe contrato si se hace bien (logout sigue funcionando aunque el endpoint no exista todavía, solo deja de invalidar server-side) |
| 2 | PDFs descargados quedan en la carpeta pública de descargas del dispositivo (`DownloadDir/cartas-cactu/`), con fotos y datos personales de menores | No aplica | `Contestacion.js`, `DescargarEliminar.js` | Evaluar con el responsable de datos/privacidad si debe usarse almacenamiento privado de la app en vez de la carpeta pública de descargas | BAJO/MEDIO (privacidad) | Ninguna |

## API (limpieza, no bloqueante)

| # | Problema | Acción requerida | Prioridad | Dependencias |
|---|---|---|---|---|
| 3 | 6 de 8 llamadas POST protegidas envían `userId`/`ninio_id` que el backend ya ignora (post SEG-004/SEG-005) | Quitar esos campos del body en un futuro release móvil (cosmético, no urgente — el backend ya no los lee) | BAJO | Ninguna |
| 4 | Inconsistencia de nombre: `ninio_id` en 3 pantallas vs. `userId` en 2 pantallas, ambos sin uso real | Unificar si se decide mantener el campo por claridad de logs/debug | BAJO | Depende de #3 |
| 5 | `POST /enviar-mensaje` existe en el backend (placeholder) sin consumidor móvil confirmado | Confirmar con negocio si "mensajería" es una función planeada; si no, evaluar retirar el endpoint del backend | BAJO | Decisión de producto, no técnica |
| 6 | `GET /user` sin consumidor — la app nunca refresca los datos del niño tras el login | Evaluar si vale la pena refrescar periódicamente o al reabrir la app (hoy los datos quedan obsoletos hasta el próximo login) | BAJO | Ninguna |

## AUTENTICACIÓN

| # | Problema | Acción requerida | Prioridad | Dependencias |
|---|---|---|---|---|
| 7 | Bug: `App.tsx:137` guarda `userNombres` bajo la clave `userNumeroChild` en `AsyncStorage` | Corregir la línea (`AsyncStorage.setItem('userNumeroChild', userNumeroChild)`) — bajo riesgo, el campo no se usa hoy en ninguna pantalla, pero conviene confirmar antes de tocarlo | BAJO | Ninguna |
| 8 | Sin logout automático en `401` — sesión "zombie" si el token se invalida | Definir comportamiento (interceptor o chequeo `res.status` en cada llamada) y aplicarlo de forma consistente en las 8 llamadas protegidas, no parche puntual | MEDIO | Depende de decidir si primero se centraliza el cliente HTTP (ver ARQUITECTURA #12) |
| 9 | Login sin contraseña/PIN (SEG-012, informativo en la auditoría original) | Evaluar agregar segundo factor simple (p. ej. fecha de nacimiento); implica rediseñar `Login.js` (hoy es un teclado numérico de un solo campo) | BAJO (riesgo aceptado documentado) — evaluar con negocio | Cambio de UX + backend coordinado |

## FIREBASE

| # | Problema | Acción requerida | Prioridad | Dependencias |
|---|---|---|---|---|
| 10 | Sin `messaging().onTokenRefresh()` — token FCM rotado en background se pierde silenciosamente | Agregar listener que reenvíe el nuevo token al backend (el endpoint dedicado ya existe, ver #11) | MEDIO | Depende de #11 (backend, resuelto) |
| 11 | Sin endpoint para actualizar/eliminar el token FCM fuera del login | **Backend: resuelto** (2026-08-08, Fase 7 paso 1) — `POST /api/actualizar-token-fcm` y `POST /api/eliminar-token-fcm`, `auth:sanctum`, ver `docs/api/autenticacion.md`. Desde `FIREBASE-002` (2026-08-08), `eliminar-token-fcm` exige `fcm_token` en el body (identifica qué dispositivo puntual borrar). **Móvil: pendiente** — llamarlos desde `onTokenRefresh` (#10) y opcionalmente en `signOut`, mandando el valor cacheado en `AsyncStorage['fcmtoken']` | MEDIO (queda del lado móvil) | Ninguna — backend ya no bloquea |
| 12 | ~~Un solo dispositivo por niño (`Ninio.fcm_token` como columna simple).~~ | **Backend: resuelto** (2026-08-08, `FIREBASE-002`) — tabla `dispositivos`, sin requerir ningún cambio del lado móvil (la app sigue mandando `fcm_token` igual que siempre; el backend decide internamente a qué dispositivo corresponde). **Móvil: sin acción requerida.** | Cerrado | Ninguna |
| 13 | Sin reacción a notificaciones (foreground silencioso, sin navegación al tocar una notificación en background) | Definir payload (`data`) que debe enviar `EnviarNotificacionCartaNueva` y agregar navegación/banner en el móvil | BAJO/MEDIO (UX) | Requiere decidir el payload del lado backend primero |
| 14 | Sin configuración de Firebase para iOS (`GoogleService-Info.plist` ausente) | Confirmar si la app se distribuye para iOS; si sí, agregar configuración nativa | Depende de si hay distribución iOS activa | Fuera del alcance de este repositorio (config nativa) |

## ARQUITECTURA

| # | Problema | Acción requerida | Prioridad | Dependencias |
|---|---|---|---|---|
| 15 | Sin cliente HTTP centralizado — cada pantalla repite `fetch`/`ReactNativeBlobUtil` con URL y headers manuales | Introducir un módulo `services/api.js` (o similar) con base URL, headers y manejo de errores comunes, antes de seguir agregando pantallas | MEDIO | Ninguna — es la base para resolver #8, #10, #11 de forma consistente |
| 16 | Duplicación de la lista de campos obligatorios por tipo de carta (Blade backend / validación API backend / `validar()` móvil, ver `docs/auditoria/funcionalidades-compartidas.md`) | Alinear con la Fase 4 del roadmap backend (Actions únicas por tipo de carta) — el móvil debería consumir la misma fuente de verdad de validación cuando esa fase se ejecute | MEDIO | Depende de que el backend ejecute Fase 4 primero |
| 17 | Dos librerías distintas para HTTP con archivos (`react-native-blob-util` y `rn-fetch-blob`, este último sin mantenimiento activo) | Unificar en una sola librería | BAJO | Ninguna, pero requiere pruebas en descargas y envíos multipart antes de retirar la que se descarte |
| 18 | `PresentacionMayores.js`, `PresentacionMenores.js` y `DescargarEliminar.js` (`eliminar-carta`) nunca revisan `result.error` — un `403 "No autorizado"` (p. ej. intentar responder/eliminar la carta de otro niño) falla en silencio: el spinner se detiene, sin toast ni navegación, igual que un `401`. Detectado al verificar Fase 5 (ARQ-006 Paso B, backend) contra el código real de las 6 pantallas que usan el patrón `errors/error/info/success` — ver `manejo-errores.md`. No es un problema nuevo introducido por el backend, ya existía. | Agregar `if (result.error) { toast(...) }` en las 3 pantallas, igual que ya hacen `Contestacion.js`/`Agradecimiento.js`/`Iniciada.js` | BAJO/MEDIO (el escenario que lo dispara — 403 en estos 3 endpoints — es infrecuente en uso normal, pero cuando ocurre el usuario no tiene ninguna pista de qué pasó) | Ninguna — cambio contenido en las 3 pantallas, no requiere tocar el backend |

## FOTOS / CALIDAD DE IMAGEN

| # | Problema | Acción requerida | Prioridad | Dependencias |
|---|---|---|---|---|
| 19 | **Fotos del niño y de la familia en solo 300×400 px** (`MOVIL-001`). Las 5 pantallas de respuesta (`Iniciada.js`, `Agradecimiento.js`, `Contestacion.js`, `PresentacionMayores.js`, `PresentacionMenores.js`) llaman a `ImagePicker.openCamera({ width: 300, height: 400, cropping: true, includeBase64: true })`. Con `react-native-image-crop-picker`, eso recorta **y reduce** la foto a 300×400 px antes de enviarla. Se confirmó con 3 fotos reales descargadas de producción (las 3 miden exactamente 300×400). En el PDF se ven borrosas: para su tamaño en el PDF (unos 8×10,5 cm) hacen falta unos 900×1200 px. La foto de familia (normalmente horizontal) además se recorta en vertical. | Subir el tamaño de salida a, por ejemplo, `width: 900, height: 1200` y agregar `compressImageQuality: 0.8` para que el archivo no crezca demasiado. Para la foto de familia, evaluar una proporción horizontal o `freeStyleCropEnabled: true`. De paso, enviar el archivo como `personal.jpg` en lugar de `personal.png`, porque el contenido es JPEG. | ALTO (calidad visible del producto final que recibe el patrocinador) | Requiere **nueva versión en Google Play** y autorización del cliente. Las fotos ya guardadas (300×400) no se pueden recuperar. El backend no necesita cambios: guarda y pone en el PDF la resolución que reciba (verificado con 3000×4000 px), y desde 2026-10-21 corrige la extensión según el contenido. |

## Resumen de prioridad

```text
ALTO (1):   #19 fotos de 300×400 px (MOVIL-001, pendiente de autorización del cliente)
MEDIO (6):  #1 token sin expirar/revocar, #8 sin logout en 401, #10 sin refresh de token FCM,
            #11 sin endpoint de actualización FCM, #15 sin cliente HTTP centralizado,
            #16 validación triplicada (depende de Fase 4 backend)
BAJO (10):  #2, #3, #4, #5, #6, #7, #9, #13, #17, #18 (límite inferior; #18 puede ser MEDIO según cuán seguido ocurra en producción)
DEPENDE DE CONTEXTO (1): #14 (solo si hay distribución iOS)
CERRADO (1): #12 un solo dispositivo por niño (backend resuelto 2026-08-08, `FIREBASE-002`, sin acción del lado móvil)
```

Ningún hallazgo de esta lista alcanza severidad CRÍTICA, y el único ALTO es #19 (calidad de fotos, agregado 2026-10-21) — los hallazgos críticos originales de la auditoría Fase 0 (SEG-001 a SEG-005) que involucraban al móvil (SEG-003, SEG-004, SEG-005) **ya fueron cerrados en el backend antes de esta auditoría cruzada** (ver `docs/cambios/changelog.md`) y no requirieron cambios en el cliente móvil, porque este siempre trató los IDs de PDF como opacos y el backend ahora ignora los identificadores que el cliente enviaba de más.
