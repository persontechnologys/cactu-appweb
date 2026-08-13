# Arquitectura actual de CactuApp (React Native)

> Auditoría cruzada Fase 0.1. Repositorio analizado: `/home/persontech/workspace/CactuApp`, rama `main`, commit `d98cfd3` (2024-10-02), con `package.json`/`package-lock.json` modificados sin commitear en el working tree al momento de esta auditoría. No se modificó ningún archivo de este repositorio.

## 1. Identidad del proyecto

- **Nombre interno:** `CactuApp` (`package.json`), `displayName: "CactuApp"` (`app.json`).
- **applicationId Android:** `com.cactuapp` (`android/app/build.gradle`).
- **Bootstrap:** React Native CLI estándar (no Expo) — hay carpetas nativas `android/` e `ios/`, `Gemfile` (CocoaPods vía Ruby), `metro.config.js`, `.watchmanconfig`.
- **React Native:** `0.74.3`. **React:** `18.2.0`. **Node mínimo:** `>=18` (`engines`).
- No hay `docs/` propio dentro de `CactuApp`; el único documento es el `README.md` genérico del boilerplate de `@react-native-community/cli` (sin contenido específico del proyecto).

## 2. Estructura real de `src/`

```text
src/
├── public/img/          # Logo y 3 imágenes del onboarding (IntroSlider)
├── service/
│   └── AuthContext.js   # React.createContext() vacío — el estado vive en App.tsx
├── utils/
│   ├── pushnotificaction_helper.js       # Firebase Messaging: permisos, token FCM, listeners
│   ├── requestNotificationPermission.js  # Permiso Android POST_NOTIFICATIONS
│   └── WRITE_EXTERNAL_STORAGE_PERMISSION.js # Permiso Android WRITE_EXTERNAL_STORAGE
└── view/
    ├── acerca/Inicio.js         # Pantalla "Acerca de" (estática)
    ├── auth/
    │   ├── Login.js             # Teclado numérico para numero_child + POST /login
    │   └── Perfil.js            # Solo botón "Salir de la aplicación" (signOut)
    ├── CargandoSpinner.js       # Pantalla de splash mientras se restaura el token
    ├── cartas/
    │   ├── Agradecimiento.js
    │   ├── Contestacion.js
    │   ├── DescargarEliminar.js # Ver/descargar/eliminar carta ya respondida
    │   ├── Iniciada.js
    │   ├── Presentacion.js      # Enruta a Mayores/Menores según `edad > 5`
    │   ├── PresentacionMayores.js
    │   └── PresentacionMenores.js
    ├── inicio/Index.js          # Listado principal de cartas (POST /listar-mis-cartas)
    ├── IntroSlider.js           # Onboarding (3 slides), solo se ve sin sesión
    ├── stack/
    │   ├── AcercaStack.js
    │   └── InicioStack.js
    └── TabHome.js                # Bottom tabs: Inicio / Acerca
```

No existen carpetas `screens/`, `components/`, `hooks/`, `contexts/` (plural), ni `services/api/` como capa de cliente HTTP dedicada. **No implementado actualmente**: capa de servicios/API reutilizable — cada pantalla arma su propia llamada `fetch`/`ReactNativeBlobUtil.fetch` inline con la URL completa.

## 3. Punto de entrada y gestión de estado global

`index.js` registra `App` (de `App.tsx`) vía `AppRegistry`. Toda la lógica de sesión vive directamente en `App.tsx`:

- Usa `React.useReducer` con acciones `RESTORE_TOKEN`, `SIGN_IN`, `SIGN_OUT` para mantener `{ userToken, userNombres, userId, userNumeroChild, userRolesPermisos, isLoading, isSignout }`.
- Este estado se expone a toda la app a través de `AuthContext.Provider` (`src/service/AuthContext.js`, un `React.createContext()` sin valor por defecto).
- No hay Redux, Zustand, MobX ni Context API modular por dominio — **Implementación parcial**: un único contexto de autenticación, sin contexto de datos de cartas (cada pantalla vuelve a pedir el listado con `fetch` propio).

## 4. Navegación

Basada en `@react-navigation/native` + `native-stack` + `bottom-tabs`:

```text
NavigationContainer
└── (según estado de auth)
    ├── isLoading=true          → Stack: Splash (CargandoSpinner)
    ├── userToken == null       → Stack: IntroSlider → Login
    └── userToken presente      → TabHome
                                    ├── Tab "Inicio" → InicioStack
                                    │     Inicio → Perfil
                                    │            → Presentacion (→ Mayores/Menores)
                                    │            → Contestacion
                                    │            → Agradecimiento
                                    │            → Iniciada
                                    │            → DescargarEliminar
                                    └── Tab "Acerca" → AcercaStack → InicioAcerca
```

No hay Drawer ni deep links configurados. La navegación "protegida" es implícita: React Navigation decide qué stack montar según `state.userToken`, no hay guardas por pantalla individual dentro de `TabHome` — cualquier pantalla del stack autenticado es alcanzable si se tiene token, sin distinción de roles/permisos (coherente con que el niño no tiene roles).

## 5. Gestión de estado de formularios y borradores

Cada pantalla de "responder carta" (Contestacion, Agradecimiento, Iniciada, PresentacionMayores, PresentacionMenores) guarda un borrador en `AsyncStorage` bajo una clave propia (`formDataContestacion`, `formDataAgradecimiento`, etc.) en cada `onChangeText`, y lo borra (`AsyncStorage.removeItem`) solo cuando el envío al backend responde `success`. Esto es persistencia de UX (recuperar el formulario si la app se cierra), no seguridad ni regla de negocio.

## 6. Almacenamiento local — inventario completo

Todo vía `@react-native-async-storage/async-storage` (no cifrado, no `SecureStore`, no `MMKV`):

| Clave | Contenido | Dónde se escribe | Dónde se lee |
|---|---|---|---|
| `userToken` | Token Sanctum en texto plano | `App.tsx` (`signIn`) | `App.tsx` (`bootstrapAsync`), enviado como header `Authorization` en cada pantalla |
| `userNombres` | Nombre completo del niño | `App.tsx` (`signIn`) | `App.tsx`, `inicio/Index.js` (saludo) |
| `userNumeroChild` | **Bug:** se guarda el valor de `userNombres`, no el `numero_child` real (ver `App.tsx:137`) | `App.tsx` (`signIn`) | `App.tsx` (se restaura pero no se usa en ninguna pantalla) |
| `userId` | ID numérico del niño, como string | `App.tsx` (`signIn`, `String(data.id)`) | Enviado como `userId`/`ninio_id` en varios formularios (ver `api-consumida-por-movil.md`) |
| `userRolesPermisos` | `JSON.stringify([])` — siempre vacío | `App.tsx` (`signIn`) | Restaurado pero no consumido en ninguna vista |
| `fcmtoken` | Token de push de Firebase | `pushnotificaction_helper.js` (`getFCMToken`) | `Login.js` (se adjunta al `POST /login`) |
| `formData*` (5 claves) | Borradores de formularios de carta | Pantallas `cartas/*` | Mismas pantallas |

**Riesgo de seguridad no cubierto por SEG-001..012 original:** el token Sanctum se guarda sin cifrar en `AsyncStorage`, legible por cualquier proceso con acceso al almacenamiento de la app en un dispositivo comprometido/rooteado. Es una práctica común en apps React Native sencillas, pero se confirma en `config/sanctum.php:49` que `'expiration' => null` — el token **nunca expira** del lado backend, y no existe endpoint de revocación remota (logout solo borra el token localmente en el dispositivo, no invalida el `PersonalAccessToken` en el servidor). Se registra como hallazgo nuevo en `docs/movil/cambios-requeridos.md`.

## 7. Dependencias principales relevantes al contrato con el backend

| Paquete | Versión | Uso |
|---|---|---|
| `react-native-dotenv` | `^3.4.11` | Expone `.env` como módulo `@env` (`API_NAME`, `API_HOST`, `API_URL`, `API_TOKEN`) |
| `@react-native-firebase/app` / `messaging` | `^20.1.0` | Push notifications (Android configurado vía `google-services.json`; no se encontró `GoogleService-Info.plist` para iOS) |
| `@react-native-async-storage/async-storage` | `^1.23.1` | Todo el almacenamiento local (ver tabla anterior) |
| `react-native-blob-util` / `rn-fetch-blob` | `^0.19.9` / `^0.12.0` | Envío `multipart/form-data` (fotos) y descarga de PDFs a `DownloadDir` |
| `react-native-pdf` | `^6.7.5` | Visor de PDF embebido (carta del patrocinador y carta ya respondida) |
| `react-native-image-crop-picker` | `^0.41.2` | Captura/recorte de fotos (cámara) para adjuntar en las cartas |
| `native-base` | `^3.4.28` | Librería de componentes UI (usada en todas las pantallas) |

**Nota:** el proyecto usa dos librerías distintas para HTTP con archivos (`react-native-blob-util` en `Contestacion.js`, `rn-fetch-blob` — su predecesor, ya sin mantenimiento activo — en otras pantallas para descargas). No es duplicación de regla de negocio, pero sí deuda técnica de dependencias a documentar en `cambios-requeridos.md`.

## 8. Variables de entorno (`.env`, sin credenciales sensibles expuestas aquí)

```text
API_NAME="CACTU"
API_HOST=https://cactu.ec/
API_URL=https://cactu.ec/api/
API_TOKEN=12345678
```

`API_TOKEN` no se usa en ningún archivo `src/*` (grep sin resultados fuera de `.env`) — parece vestigial o reservado para un uso no implementado. `API_URL` es la única variable realmente consumida (concatenada con el nombre de cada endpoint, sin cliente HTTP centralizado — ver sección 4 de `api-consumida-por-movil.md`).
