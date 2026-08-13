# Firebase / notificaciones push — flujo real móvil ↔ backend

> Auditoría cruzada Fase 0.1. Fuentes: `CactuApp/src/utils/pushnotificaction_helper.js`, `CactuApp/src/view/auth/Login.js`, `CactuApp/App.tsx`, `CactuApp/android/app/build.gradle`, `CactuApp/android/app/google-services.json` (existencia verificada, contenido no citado por ser credencial), backend `routes/api.php`, `app/Notifications/EnviarNotificacionCartaNueva.php`.

> **Nota de actualización (Fase 7, 2026-08-08):** todo lo descrito del lado móvil (secciones 1-3, 6) sigue vigente sin cambios — no se tocó `CactuApp`. Del lado backend, `FIREBASE-001` (migración a FCM HTTP v1) y `FIREBASE-002` (tabla de dispositivos, cierra la limitación de la sección 4) ya están resueltos — correcciones puntuales marcadas inline. Ver `docs/auditoria/problemas-detectados.md`.

## 1. Configuración

- **Plataforma configurada:** solo **Android**. Existe `android/app/google-services.json` y el plugin Gradle `com.google.gms.google-services` está aplicado en `android/app/build.gradle`, junto con `firebase-bom:32.1.1` y `firebase-analytics`.
- **iOS:** no se encontró `GoogleService-Info.plist` en `ios/` — Firebase Messaging **no está configurado para iOS** en este checkout. Si la app se distribuye también para iOS, las notificaciones push no funcionarán hasta agregar esa configuración (fuera del alcance de esta auditoría, solo se documenta el hallazgo).
- **SDK:** `@react-native-firebase/app` y `@react-native-firebase/messaging`, versión `^20.1.0`.

## 2. Flujo real de obtención y envío del token FCM

```text
App.tsx (useEffect, al montar, siempre — con o sin sesión)
   │
   ├── requestUserPermission()  [pushnotificaction_helper.js]
   │     │
   │     └── messaging().requestPermission()
   │           └── si AUTHORIZED/PROVISIONAL → getFCMToken()
   │                 │
   │                 ├── lee AsyncStorage['fcmtoken']
   │                 │     ├── si YA existe → no hace nada más (no vuelve a pedir uno nuevo)
   │                 │     └── si NO existe → messaging().getToken() → AsyncStorage.setItem('fcmtoken', value)
   │
   ├── NotificacionListener()  [pushnotificaction_helper.js]
   │     ├── messaging().onNotificationOpenedApp(...)   → solo console.log
   │     ├── messaging().getInitialNotification()       → solo console.log
   │     └── messaging().onMessage(...)                 → solo console.log (sin mostrar UI local para foreground)
   │
   └── requestNotificationPermission()  [permiso Android POST_NOTIFICATIONS, independiente de Firebase]

Login.js (al presionar "INGRESAR")
   │
   ├── lee AsyncStorage['fcmtoken'] (el mismo guardado arriba, si existe)
   └── POST /api/login  { numero_child, fcm_token: <valor leído> }
         │
         └── Backend: closure en routes/api.php (actualizado Fase 7, FIREBASE-002)
               if ($request->filled('fcm_token')) {
                   (new RegistrarDispositivoAction())->registrar($ninio, $request->fcm_token);
               }
               // upsert en la tabla `dispositivos` por fcm_token: crea el dispositivo si es
               // nuevo, o lo reasigna a este $ninio si el mismo token ya pertenecía a otro
               // (p. ej. hermanos que comparten un teléfono). Ya no sobrescribe una columna
               // simple ni se ejecuta si el body no trae el campo (SEG-014).
```

## 3. Qué pasa en cada evento del ciclo de vida

| Evento | Comportamiento real observado |
|---|---|
| Primera instalación / primer login | Se pide permiso, se obtiene token FCM nuevo, se guarda en `AsyncStorage`, se envía en el siguiente `POST /login`. |
| Login posterior (mismo dispositivo) | Reutiliza el token ya cacheado en `AsyncStorage['fcmtoken']` — se reenvía en cada `POST /login`. Desde Fase 7 (`FIREBASE-002`), el backend hace upsert del dispositivo por token en vez de sobrescribir una columna; sin diferencia observable para la app. |
| **Rotación del token FCM** (Firebase puede rotarlo en cualquier momento — reinstalación, restauración de backup, límites internos de FCM) | **No implementado actualmente.** No hay listener `messaging().onTokenRefresh()`. Si el token cambia en segundo plano, la app sigue usando el valor viejo cacheado hasta que el usuario cierre sesión y vuelva a loguearse (lo cual tampoco fuerza un nuevo `getToken()`, porque `getFCMToken()` solo pide uno nuevo si `AsyncStorage['fcmtoken']` está vacío). **Riesgo:** notificaciones push dejan de llegar silenciosamente para niños cuyo token rotó, sin ningún mecanismo de recuperación automática. |
| Logout (`Perfil.js` → `signOut`) | `App.tsx` borra `userToken`, `userNumeroChild`, `userNombres`, `userId`, `userRolesPermisos` de `AsyncStorage`. **No borra `fcmtoken`** ni notifica al backend para desvincular el dispositivo — no hay endpoint para eso (ver punto 5). El siguiente niño que use el mismo dispositivo físico heredará el mismo `fcmtoken` cacheado, lo cual es correcto (el token es del dispositivo, no del niño), pero implica que el backend nunca se entera de que el niño anterior cerró sesión: su registro `Ninio.fcm_token` sigue apuntando al dispositivo hasta que ese niño specific vuelva a loguearse en otro dispositivo o alguien más haga login en este mismo dispositivo. |
| Desinstalación / reinstalación | Se pierde `AsyncStorage` completo (incluido `fcmtoken`), Firebase generará un token nuevo en el próximo `requestUserPermission()`. El backend seguirá teniendo el `fcm_token` viejo en `Ninio` hasta el próximo login exitoso de ese niño — un envío a Firebase con un token de una instalación borrada simplemente fallará silenciosamente del lado del backend (no se auditó el manejo de tokens inválidos en `EnviarNotificacionCartaNueva`, ver `seguridad.md` SEG-011 y `deuda-tecnica.md` para el estado de las Notifications). |

## 4. Modelo de datos: ~~limitación de "un solo dispositivo por niño"~~ resuelto del lado backend (`FIREBASE-002`)

Confirmado desde el móvil: no existe ninguna pantalla ni flujo para gestionar "mis dispositivos" — la app sigue sin necesitar una, porque **no requiere ningún cambio** para beneficiarse del backend nuevo. Desde Fase 7 (2026-08-08), `Ninio.fcm_token` dejó de ser una columna simple: existe una tabla `dispositivos` (relación uno-a-muchos), conforme al modelo objetivo de `CLAUDE.md` sección 25. Si un niño usa dos tablets (p. ej. en la casa y en el centro comunitario), ambos dispositivos quedan registrados y reciben notificaciones — el problema real que motivó el cambio no era hipotético: se confirmaron 144 casos de tokens compartidos entre hermanos en la base de datos real. Ver `docs/auditoria/problemas-detectados.md` (`FIREBASE-002`).

## 5. Endpoints relacionados con Firebase

| Necesidad | Endpoint existente | Estado |
|---|---|---|
| Registrar/actualizar token FCM | `POST /login` (implícito) y `POST /actualizar-token-fcm` (explícito, Fase 7) | Implementado en ambos casos |
| Actualizar token FCM sin re-login (p.ej. tras `onTokenRefresh`) | `POST /actualizar-token-fcm` | **Backend: implementado (Fase 7). Móvil: sin consumidor todavía** — la app no implementa `onTokenRefresh` (ver sección 3) |
| Eliminar/desvincular token al hacer logout | `POST /eliminar-token-fcm` (exige `fcm_token` en el body desde `FIREBASE-002`) | **Backend: implementado (Fase 7). Móvil: sin consumidor todavía** — `Perfil.js` → `signOut` no lo llama |
| Notificar al niño de una carta nueva | `App\Notifications\EnviarNotificacionCartaNueva` (backend, disparada al crear una carta) | Implementado, encolada de verdad (`PERF-001`), con reintentos y limpieza de tokens inválidos (`FIREBASE-001`) |

## 6. Manejo de notificaciones en la UI

`NotificacionListener()` registra los tres listeners estándar de `@react-native-firebase/messaging` (`onNotificationOpenedApp`, `getInitialNotification`, `onMessage`), pero **los tres solo hacen `console.log`** — no navegan a ninguna pantalla específica, no muestran un banner/alerta local cuando la app está en foreground, y no incrementan ningún contador de "no leídas". Recibir una notificación mientras la app está abierta actualmente no tiene ningún efecto visible para el niño más allá de lo que el sistema operativo/Firebase muestre por defecto en background. **Implementación parcial** — la infraestructura de recepción existe, la de reacción/UX no.

## 7. Resumen para `docs/movil/cambios-requeridos.md`

~~Los gaps aquí documentados (sin token refresh, sin logout de dispositivo, sin reacción a notificaciones en foreground, un solo dispositivo por niño) se trasladan como ítems de la categoría **FIREBASE**...~~ **Actualizado:** de los 4 gaps originales, "un solo dispositivo por niño" ya se cerró del lado backend sin requerir nada del móvil (`FIREBASE-002`, ítem #12 en `cambios-requeridos.md`, marcado `CERRADO`). Los otros 3 (sin `onTokenRefresh`, sin logout que desvincule el token, sin reacción a notificaciones en foreground) siguen abiertos y son trabajo pendiente del repositorio móvil — ver `docs/movil/cambios-requeridos.md`, categoría FIREBASE.
