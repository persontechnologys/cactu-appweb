# CactuApp — Guía para el desarrollador móvil

> Punto de entrada de la documentación móvil de CACTU. Escrito para que un desarrollador React Native pueda incorporarse sin tener que preguntar constantemente cómo funciona el backend. Generado en la auditoría cruzada Fase 0.1 (2026-08-08), a partir del código real de `/home/persontech/workspace/CactuApp` (rama `main`, commit `d98cfd3`) contrastado con `/home/persontech/workspace/cactu-appweb` (rama `security/fase-2-hallazgos-criticos`).

## ¿Qué es CactuApp?

Es el cliente React Native (CLI puro, sin Expo) de CACTU, usado por **niños patrocinados** — no por gestores/administradores, que usan la web. El niño se identifica con un `numero_child` (sin contraseña), ve sus cartas pendientes/respondidas, responde las pendientes con texto y fotos, y descarga o elimina las ya respondidas.

## Cómo se conecta al backend

- Base URL: `API_URL` en `.env` (`https://cactu.ec/api/` en producción; en desarrollo local se apunta a la IP LAN de la máquina que sirve el backend, ver sección "Cómo ejecutar localmente"). Ver `.env.example` del propio repo móvil para las claves esperadas.
- No hay cliente HTTP centralizado: cada pantalla llama `fetch` (JSON) o `ReactNativeBlobUtil.fetch`/`RNFetchBlob` (multipart y descargas) directamente. Detalle completo en [`api.md`](./api.md).
- Autenticación: token Sanctum obtenido en `POST /login`, guardado en `AsyncStorage`, enviado manualmente como `Authorization: Bearer <token>` en cada pantalla que lo requiere. Detalle en [`autenticacion.md`](./autenticacion.md).

## Cómo consume la API

Ver [`api.md`](./api.md) para el contrato endpoint por endpoint, y `docs/auditoria/matriz-api-movil.md` (en el repositorio backend) para la comparación completa contra `routes/api.php`.

## Cómo maneja errores

No hay manejo uniforme de códigos HTTP (401/403/404/422/500) — cada pantalla interpreta la forma del JSON de respuesta. Ver [`manejo-errores.md`](./manejo-errores.md) antes de tocar cualquier pantalla que llame a la API, para no romper un flujo de error ya "acoplado" a la forma actual de la respuesta.

## Dónde recibe notificaciones (Firebase)

Solo Android está configurado (no hay `GoogleService-Info.plist` para iOS). El token FCM se obtiene una sola vez y se reenvía en cada login; no hay refresh automático ni pantalla de gestión de "mis dispositivos" del lado de la app. El backend sí soporta múltiples dispositivos por niño desde 2026-08-08 (`FIREBASE-002`) sin requerir ningún cambio del lado móvil. Ver [`firebase.md`](./firebase.md) y [`notificaciones.md`](./notificaciones.md).

## Dónde están los servicios y las pantallas

```text
src/
├── service/AuthContext.js   # Contexto de sesión (el estado real vive en App.tsx)
├── utils/                   # Firebase, permisos Android
└── view/
    ├── auth/                # Login, Perfil (logout)
    ├── inicio/Index.js      # Listado de cartas
    ├── cartas/               # 5 pantallas de "responder carta" + descarga/eliminación
    ├── acerca/               # Pantalla informativa estática
    └── stack/, TabHome.js    # Navegación
```

No existe una carpeta `services/api/`, `hooks/` ni `components/` reutilizables — cada pantalla es bastante autónoma. Ver [`arquitectura-actual.md`](./arquitectura-actual.md) para el detalle completo, y `docs/auditoria/movil-arquitectura-actual.md` (backend repo) para la versión extendida con hallazgos.

## Cómo ejecutar localmente

El repo móvil trae su propio `README.md` (boilerplate estándar de React Native CLI: `npm start`, luego `npm run android`/`npm run ios`). Requiere Android SDK/Xcode configurados según la [guía oficial de React Native](https://reactnative.dev/docs/environment-setup). No se auditó ni modificó la configuración nativa (`android/`, `ios/`) más allá de confirmar que Firebase solo está cableado para Android.

### Backend local para pruebas desde el dispositivo/emulador

El dispositivo o emulador donde corre `CactuApp` no puede alcanzar `127.0.0.1`/`localhost` de la máquina de desarrollo — necesita la IP de la máquina en la red local (LAN). Flujo usado actualmente en este proyecto:

1. En `cactu-appweb`, levantar el backend escuchando en todas las interfaces, no solo loopback:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
2. En `CactuApp/.env` (no trackeado por git, cada desarrollador lo edita localmente), apuntar `API_HOST`/`API_URL` a la IP LAN de esa máquina, por ejemplo:
   ```env
   API_HOST=http://192.168.1.58:8000/
   API_URL=http://192.168.1.58:8000/api/
   ```
   Esta IP cambia según la red/máquina de cada desarrollador — no asumir que `192.168.1.58` es fija ni copiarla a `.env.example` (que debe seguir documentando el valor de producción como referencia).
3. Dispositivo/emulador y la máquina que sirve el backend deben estar en la **misma red LAN** para que la conexión funcione.

Con el backend local activo, cualquier cambio de contrato (nuevo campo, endpoint, validación) puede probarse de inmediato desde la app real antes de coordinar un despliegue — ver `docs/movil/api.md` como referencia del contrato vigente a mantener sincronizada cuando se trabaje en ambos repos a la par.

## Qué cambios están pendientes

Ver [`cambios-requeridos.md`](./cambios-requeridos.md) — catálogo priorizado de brechas encontradas (seguridad, API, autenticación, Firebase, UX, arquitectura). Ninguno fue corregido durante esta auditoría; están pendientes de autorización explícita antes de implementarse, conforme a `CLAUDE.md` sección 48.

## Documentos relacionados en el repositorio backend

- `docs/auditoria/movil-arquitectura-actual.md` — arquitectura completa con hallazgos línea por línea.
- `docs/auditoria/api-consumida-por-movil.md` — inventario exhaustivo de cada llamada HTTP.
- `docs/auditoria/matriz-api-movil.md` — comparación endpoint por endpoint contra `routes/api.php`.
- `docs/auditoria/firebase-movil-actual.md` — flujo de push notifications con hallazgos.
- `docs/auditoria/funcionalidades-compartidas.md` — dónde se duplica lógica entre Web/API/Móvil.
- `docs/auditoria/integracion-movil.md` y `docs/auditoria/seguridad.md` — auditoría original Fase 0 (backend), con nota de actualización de estado agregada tras esta auditoría cruzada.
