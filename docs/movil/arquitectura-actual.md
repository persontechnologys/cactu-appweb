# Arquitectura actual de CactuApp (resumen para desarrollo)

> Versión resumida orientada a desarrollo. Para el detalle completo con hallazgos y líneas de código, ver `docs/auditoria/movil-arquitectura-actual.md` en el repositorio backend.

## Stack

- React Native `0.74.3` (CLI puro, no Expo), React `18.2.0`, TypeScript configurado pero la mayoría del código está en `.js`.
- Navegación: `@react-navigation/native` (native-stack + bottom-tabs).
- UI: `native-base` `^3.4.28` en todas las pantallas.
- HTTP: `fetch` nativo (JSON) + `react-native-blob-util`/`rn-fetch-blob` (multipart y descargas de PDF).
- Push: `@react-native-firebase/messaging` (solo Android configurado).
- Storage local: `@react-native-async-storage/async-storage` (sin cifrar).
- Variables de entorno: `react-native-dotenv`, expuestas como módulo `@env` (`API_NAME`, `API_HOST`, `API_URL`).

## Estado global

Un único `AuthContext` (`src/service/AuthContext.js`) alimentado por un `useReducer` en `App.tsx` con `{ userToken, userNombres, userId, userNumeroChild, userRolesPermisos, isLoading, isSignout }`. No hay contexto ni store para los datos de negocio (cartas) — cada pantalla que los necesita vuelve a pedirlos.

## Navegación

```text
Splash (mientras se restaura el token de AsyncStorage)
  │
  ├── sin token → IntroSlider → Login
  └── con token → TabHome
                    ├── Tab Inicio → Index (listado) → Perfil / Presentacion / Contestacion / Agradecimiento / Iniciada / DescargarEliminar
                    └── Tab Acerca → Inicio (estático)
```

## Convenciones a respetar al agregar código nuevo

- **No existe una capa de API centralizada.** Antes de crear un nuevo servicio HTTP, decidir si conviene introducir uno (ver `cambios-requeridos.md`, categoría ARQUITECTURA) en vez de replicar el patrón `fetch` inline una vez más.
- **Los formularios de carta guardan borrador en `AsyncStorage`** bajo una clave por pantalla (`formData<Tipo>`) y la limpian solo al recibir `result.success`. Si se agrega un tipo de carta nuevo, seguir el mismo patrón para consistencia, o evaluar centralizarlo.
- **El campo `userId`/`ninio_id` que se envía en varios formularios ya no lo usa el backend** (ver `docs/movil/api.md` y SEG-004/SEG-005 en el backend) — no es necesario seguir replicándolo en pantallas nuevas.

## Lo que NO existe todavía (no asumir)

- Sin `SecureStore`/almacenamiento cifrado para el token.
- Sin interceptor HTTP global (headers, manejo de 401, reintentos).
- Sin refresh de token FCM (`onTokenRefresh`).
- Sin pantalla de gestión de "mis dispositivos".
- Sin tests (no se encontró contenido relevante más allá del boilerplate en `__tests__/`).
