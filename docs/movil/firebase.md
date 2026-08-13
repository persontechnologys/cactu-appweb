# Firebase en CactuApp

> Resumen orientado a desarrollo. Para el flujo completo con hallazgos, ver `docs/auditoria/firebase-movil-actual.md` en el repositorio backend.

## Configuración

- Solo **Android** está configurado (`google-services.json` presente, plugin Gradle aplicado). **No hay `GoogleService-Info.plist`** para iOS en este checkout — si se necesita push en iOS, es trabajo pendiente, no un bug de esta app.
- SDK: `@react-native-firebase/messaging` `^20.1.0`.

## Flujo de token

1. Al montar `App.tsx` (siempre, con o sin sesión): se pide permiso de notificaciones y, si se concede, se busca un token FCM en `AsyncStorage['fcmtoken']`; si no existe, se pide uno nuevo con `messaging().getToken()` y se cachea.
2. En cada `POST /login`, el valor cacheado (pueda ser el mismo de logins anteriores) se reenvía y el backend lo registra como un dispositivo del niño (desde 2026-08-08, tabla `dispositivos` — ver más abajo, `FIREBASE-002`).
3. **No hay `onTokenRefresh()` implementado** — si Firebase rota el token en background, la app sigue usando el valor viejo hasta que se borre manualmente `AsyncStorage` (reinstalación).
4. **Logout no borra `fcmtoken`** ni notifica al backend — es correcto que el token de dispositivo persista, pero significa que el backend nunca sabe que un niño cerró sesión en ese dispositivo.

## Flujo de token (actualización 2026-08-08, backend Fase 7)

- **Backend ya no borra el token en un login sin `fcm_token` en el body** (antes `Ninio.fcm_token` quedaba en `null` si el request no lo incluía; ver `docs/api/autenticacion.md`, `SEG-014`). No requiere cambio del lado móvil, pero elimina un riesgo si alguna pantalla llegaba a hacer login sin reenviar el valor cacheado.
- **Nuevos endpoints disponibles, sin consumidor móvil todavía:** `POST /api/actualizar-token-fcm` y `POST /api/eliminar-token-fcm` (`auth:sanctum`, ver `docs/api/autenticacion.md`). Pensados para conectar `onTokenRefresh()` (punto 3 más abajo) y para llamarse antes de un futuro logout. Integrarlos es trabajo pendiente del lado de `CactuApp`, no de este repositorio.
- **`POST /eliminar-token-fcm` cambió de contrato (2026-08-08, `FIREBASE-002`):** ahora exige `fcm_token` en el body (antes no requería body). Cuando el móvil implemente el logout, debe mandar el valor cacheado en `AsyncStorage['fcmtoken']` para que el backend sepa qué dispositivo puntual borrar.

## Backend: multi-dispositivo por niño (`FIREBASE-002`, 2026-08-08)

El backend migró de una columna simple `Ninio.fcm_token` a una tabla `dispositivos` (un niño puede tener varios, cada uno identificado por su propio token). Esto no requiere ningún cambio inmediato del lado móvil: la app sigue mandando `fcm_token` en el body de `/login` y de los endpoints de token exactamente igual que antes — el backend internamente decide a qué dispositivo corresponde ese token (lo crea si es nuevo, lo reasigna si el mismo teléfono ya estaba asociado a otro `numero_child`, p. ej. hermanos compartiendo un celular). Detalle completo en `docs/auditoria/problemas-detectados.md` (`FIREBASE-002`, repositorio backend).

## Envío desde el backend: migrado a FCM HTTP v1 (`FIREBASE-001`, 2026-08-08)

**Hallazgo cerrado, relevante para entender por qué las notificaciones push pueden no haber estado llegando:** el backend usaba hasta ahora la API legacy de FCM (`fcm.googleapis.com/fcm/send`, paquete `kutia-software-company/larafirebase`), que Google decomisionó en 2024 — muy probablemente las notificaciones de "Nueva carta" llevaban ~2 años sin llegarle a ningún niño, sin ningún error visible en logs. Se migró a `kreait/laravel-firebase` (FCM HTTP v1, credenciales de cuenta de servicio). Detalle completo en `docs/auditoria/problemas-detectados.md` (`FIREBASE-001`, repositorio backend).

Esto no cambia nada del contrato con el móvil (el SDK `@react-native-firebase/messaging` sigue generando tokens FCM normales, compatibles con HTTP v1) — es puramente un cambio de cómo el backend envía, no de qué recibe el dispositivo.

## Limitaciones a tener en cuenta antes de construir algo nuevo

- ~~Un solo dispositivo por niño.~~ **Resuelto del lado backend (2026-08-08, `FIREBASE-002`):** ahora soporta múltiples dispositivos por niño sin requerir ningún cambio de la app (ver arriba). Con esto, Fase 7 del roadmap backend queda cerrada.
- **Sin reacción en foreground:** `onMessage`, `onNotificationOpenedApp` y `getInitialNotification` solo hacen `console.log` — no hay navegación automática a la carta relacionada ni banner in-app. Si se pide agregar esto, es una función nueva, no una corrección de bug.
- ~~Sin manejo de tokens inválidos/expirados ni reintentos en el envío desde el backend.~~ **Resuelto (2026-08-08):** `FirebaseService` limpia el token cuando Firebase responde que ya no está registrado, y los errores transitorios reintentan automáticamente (`$tries`/`$backoff` en `EnviarNotificacionCartaNueva`).

Los puntos aún abiertos siguen listados en `cambios-requeridos.md`, categoría FIREBASE.
