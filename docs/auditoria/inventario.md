# Inventario general de CACTU

> Generado en la Fase 0 (Auditoría). Refleja el estado real del código en la rama `main`, commit `a93f366` ("cartas v2"), al 2026-08-08. No se realizó ninguna modificación funcional para producir este documento.

> **Nota de actualización (auditoría cruzada de documentación, 2026-08-08):** este inventario es la fotografía original de la Fase 0 y **no se reescribió** para no perder el registro histórico. Desde entonces se ejecutaron las Fases 1, 2, 4, 5, 6 y 7 — las secciones de paquetes, Actions/Services, rutas, imports muertos y Firebase de más abajo quedaron desactualizadas y se marcan inline. Fuente de verdad vigente: `docs/auditoria/problemas-detectados.md`.

## 1. Estado inicial registrado

| Ítem | Valor |
|---|---|
| Rama actual | `main` |
| Commit actual | `a93f366` — "cartas v2" |
| Estado del working tree | `.env.example` modificado (sin commitear), `CLAUDE.md` sin trackear |
| PHP | 8.4.1 (cli) |
| Laravel | 10.48.29 (`laravel/framework: ^10.10`) |
| Node | v22.19.0 |
| NPM | 10.9.3 |
| Base de datos | MySQL (`DB_CONNECTION=mysql`) |
| Cola (`QUEUE_CONNECTION`) | `sync` (sin cola real) |
| Sesión | `file` |
| Caché | `file` |
| Broadcasting | `log` (no usado activamente) |
| Mail | `smtp` (mailpit en local) |
| Tests | 2 archivos boilerplate de Laravel (sin pruebas reales del dominio) |

## 2. Dependencias principales (`composer.json`)

| Paquete | Versión | Uso detectado |
|---|---|---|
| laravel/framework | ^10.10 | Framework base |
| laravel/sanctum | ^3.2 | Autenticación API (usuarios web y niños móviles) |
| laravel/ui | ^4.2 | Scaffolding de autenticación (login/registro clásico Bootstrap) |
| spatie/laravel-permission | ^5.10 | Roles y permisos (`ADMINISTRADOR`, `GESTOR`) — **sin middleware de rutas registrado** |
| yajra/laravel-datatables | ^10.0 | Listados server-side (Cartas, Niños, Usuarios, Comunidades) |
| barryvdh/laravel-snappy + knplabs/knp-snappy | ^1.0 / ^1.5 | Generación de PDF vía wkhtmltopdf |
| h4cc/wkhtmltopdf-amd64, h4cc/wkhtmltoimage-amd64 | 0.12.x | Binarios wkhtmltopdf/image para Linux (vendorizados, pero no referenciados en `config/snappy.php`) |
| ~~kutia-software-company/larafirebase~~ `kreait/laravel-firebase` | ~~^1.3~~ ^5.10 | Envío de notificaciones push (FCM HTTP v1) — **paquete reemplazado en Fase 7** (`FIREBASE-001`); el original usaba la API legacy de FCM, decomisionada por Google desde 2024 |
| diglactic/laravel-breadcrumbs | ^8.1 | Migas de pan |
| rap2hpoutre/fast-excel | ^5.2 | Importación masiva de niños desde Excel |
| maennchen/zipstream-php | ^3.0 | Sin uso detectado en `app/` (dependencia transitiva o no utilizada) |
| laraveles/spanish | ^1.5 | Traducciones/validaciones en español (usado por `ValidadorEc`) |

~~No existen `Actions`, `Services`, `Repositories`, `Events`, `Listeners` ni `Jobs` en `app/` — **no implementado actualmente**.~~ **Actualizado:** existen 9 `Actions` (`app/Actions/`) y 1 `Service` (`app/Services/FirebaseService.php`). El resto sigue sin implementarse.

## 3. Estructura de `app/`

```text
app/
├── Console/Kernel.php              (scheduler vacío)
├── DataTables/                     (5 clases Yajra DataTable)
├── Exceptions/Handler.php
├── Http/
│   ├── Clases/                     (ValidadorEc, LaravelValidatorEc — validación de cédula/RUC ecuatoriano)
│   ├── Controllers/                (17 controladores, ver detalle abajo)
│   ├── Middleware/                 (stack estándar de Laravel, sin middleware de roles)
│   └── Requests/                   (4 Form Requests)
├── Models/                         (5 modelos)
├── Notifications/                  (2 notificaciones)
├── Policies/                       (2 policies)
└── Providers/                      (5 providers estándar)
```

No existe `app/Modulos/`, `app/Actions/`, `app/Services/`, `app/Jobs/`, `app/Events/`. **No implementado actualmente.**

## 4. Módulos funcionales identificados

| Módulo | Objetivo | Controllers | Models | Views |
|---|---|---|---|---|
| **Autenticación** | Login/registro/recuperación de contraseña (scaffolding `laravel/ui`) + tokens Sanctum para niños | `Auth/*`, ruta API `/login` (closure) | `User`, `Ninio` | `auth/*` |
| **Usuarios (Gestores/Admin)** | ABM de usuarios internos (gestores/administradores) y asignación de roles | `UserController` | `User` | `usuarios/*` |
| **Comunidades** | ABM de comunidades, cada una asociada a un gestor (`user_id`) | `ComunidadController` | `Comunidad` | `comunidad/*` |
| **Niños (admin)** | ABM global de niños patrocinados, importación masiva vía Excel | `NinioController` | `Ninio`, `Comunidad` | `ninios/*` |
| **Mis Niños (gestor)** | ABM de niños acotado a las comunidades del gestor autenticado | `MisNiniosController` | `Ninio` | `mis-ninios/*` |
| **Cartas** | Núcleo del negocio: el gestor envía cartas (Contestación/Agradecimiento/Presentación/Iniciada) al niño; genera PDF; adjunta boletas de depósito | `CartaController` | `Carta`, `Boleta` | `cartas/*`, `cartas/v2/*` |
| **Responder Carta (Web, sin login)** | El niño (vía enlace por email, sin cuenta web) visualiza y responde su carta desde un formulario público protegido por IDs cifrados (`Crypt`) | `ResponderCartaController` | `Carta`, `Ninio` | `responder-cartas/*` |
| **Responder Carta (API móvil)** | Igual que el anterior pero consumido por la app React Native del niño, autenticado con Sanctum | `Api\ResponderCarta` | `Carta`, `Ninio` | N/A (JSON) |
| **Perfil** | El usuario autenticado edita su nombre/email/contraseña y preferencia de menú responsivo | `PerfilController` | `User` | `perfil/*` |
| **Home / Institucional** | Dashboard, manual de usuario, política de privacidad, validador de cédula ecuatoriana | `HomeController`, `WelcomeController` | — | `home`, `manual`, `privacidad` |

## 5. Controllers (17)

| Controller | Responsabilidad | Observaciones |
|---|---|---|
| `CartaController` | CRUD de cartas, generación/descarga de PDF, gestión de boletas | Mezcla validación, transacción DB, subida de archivos, notificación y generación de PDF — candidato a extraer a Actions/Services (ver deuda técnica) |
| `ResponderCartaController` | Flujo de respuesta del niño (web, sin login) | 4 métodos casi idénticos que arman un `$detalle` HTML por concatenación de strings — fuertemente duplicado con `Api\ResponderCarta` |
| `Api\ResponderCarta` | Igual que el anterior, para app móvil | Duplica ~90% de la lógica de `ResponderCartaController` con detalles ligeramente distintos y **sin las mismas verificaciones de autorización** |
| `NinioController` | ABM global de niños (pensado para ADMINISTRADOR) | **Sin autorización de backend** — ver `docs/auditoria/seguridad.md` SEG-002 |
| `MisNiniosController` | ABM de niños del gestor | Correctamente acotado por comunidad y con `$this->authorize()` |
| `UserController` | ABM de usuarios/roles | **Sin autorización de backend**, permite escalación de privilegios — ver SEG-001 |
| `ComunidadController` | ABM de comunidades | Sin autorización de backend (mismo patrón, impacto menor porque no expone PII de niños) |
| `PerfilController` | Autogestión de perfil | Correcto, limitado a `Auth::user()` |
| `HomeController` | Dashboard + validador de cédula | — |
| `WelcomeController` | Descarga pública de PDF, manual, privacidad | Ruta `descargarCartaPdf` sin autenticación ni cifrado de ID — ver SEG-003 |
| `Auth/*` (6 controllers) | Scaffolding estándar `laravel/ui` | `LoginController` añade regla de negocio: usuario con `estado != ACTIVO` es deslogueado tras autenticar |

## 6. Models (5)

| Model | Tabla | Relaciones | Notas |
|---|---|---|---|
| `User` | `users` | `hasManyThrough Ninio` (vía Comunidad), `hasMany Comunidad`, `hasMany Carta` | Usa `HasRoles` (Spatie) y `HasApiTokens` (Sanctum, no usado activamente para usuarios web) |
| `Ninio` | `ninios` | `belongsTo Comunidad`, `hasMany Carta` | Extiende `Authenticatable` + `HasApiTokens` — es el "usuario" autenticado de la app móvil |
| `Comunidad` | `comunidads` | `belongsTo User` (el gestor dueño) | — |
| `Carta` | `cartas` | `belongsTo Ninio/User(gestor)/Comunidad`, `hasMany Boleta` | Boot hook asigna `user_id` automáticamente en `creating`; varios accessors (`color`, `icono`, `estado_color`, links de archivos) |
| `Boleta` | `boletas` | `belongsTo Carta` (implícito) | Sin `$fillable` (no relevante: se asigna por propiedades, no por `create()`) |

## 7. Form Requests (4)

| Request | Usado en | Autoriza |
|---|---|---|
| `User\StoreRq` | `UserController::store` | `authorize()` retorna `true` siempre (la autorización real debería estar aquí y no está) |
| `User\UpdateRq` | `UserController::update` | Igual que arriba |
| `UpdatePerfilRequest` | `PerfilController::guardarPerfil` | `true` (aceptable, es autogestión) |
| `UpdatePasswordRequest` | `PerfilController::cambiarPassword` | `true` + validación de contraseña actual vía `withValidator` |

`UserController` importa además `App\Http\Requests\User\UserRqStore`, clase que **no existe** en el repositorio (import muerto, no causa error porque nunca se instancia).

## 8. Policies (2)

| Policy | Modelo | Métodos | Registrada en `AuthServiceProvider` |
|---|---|---|---|
| `CartaPolicy` | `Carta` | `ver`, `eliminar`, `responderCartaXninio` | Sí |
| `NinioPolicy` | `Ninio` | `update`, `delete` | Sí |

Ningún método usa los nombres convencionales de Laravel (`view`, `create`) salvo `update`/`delete`, por lo que la autorización automática de `Route::resource` (`authorizeResource`) no aplica — cada controlador debe invocar `$this->authorize()` explícitamente, y no todos lo hacen (ver seguridad).

## 9. Middleware

Stack estándar de Laravel (`Kernel.php`): `auth`, `guest`, `verified`, `signed`, `throttle`, `can`, etc. **No existe middleware ni alias `role`/`permission`** de `spatie/laravel-permission`, pese a que el paquete está instalado y en uso (`HasRoles`, `@role` en Blade). Esto es la causa raíz de varios hallazgos críticos de seguridad.

## 10. DataTables (5)

| DataTable | Alcance de datos | Correcto para el rol no-admin |
|---|---|---|
| `CartaDataTable` | Filtra por `user_id` si no es ADMINISTRADOR | ✅ |
| `DataTables\Carta\NinioDataTable` (selector de niño al crear carta) | Filtra por `comunidad_id` del gestor si no es ADMINISTRADOR | ✅ |
| `DataTables\NinioDataTable` (listado `/ninios`) | `newQuery()` sin filtrar | ❌ — ver SEG-002 |
| `UserDataTable` (listado `/usuarios`) | `newQuery()` sin filtrar | ❌ — ver SEG-001 |
| `ComunidadDataTable` | No se auditó su filtro interno en detalle; ruta también carece de autorización de backend | ⚠️ |

## 11. Notifications (2)

| Notification | Canales | Cola |
|---|---|---|
| `EnviarNotificacionCartaNueva` | `mail` si el niño tiene email, `firebase` si ~~tiene `fcm_token`~~ tiene al menos un dispositivo registrado (`$ninio->dispositivos()->exists()`, desde `FIREBASE-002`) | ~~Importa `Queueable` y `ShouldQueue` pero **no implementa `ShouldQueue`** → se envía de forma síncrona~~ **Resuelto (`PERF-001`):** implementa `ShouldQueue` de verdad, con `$tries=3`/`$backoff=30` (`FIREBASE-001`) |
| `EnviarCartaRespondidaGestor` | `mail` | ~~Igual, síncrona~~ **Resuelto (`PERF-001`):** también encolada |

## 12. Jobs / Events / Listeners / Actions / Services

~~**No implementado actualmente.**~~ **Actualizado:** existen 9 `Actions` (`app/Actions/`) y 1 `Service` (`app/Services/FirebaseService.php`). `Jobs`, `Events` y `Listeners` dedicados siguen sin implementarse.

## 13. Rutas

Ver `docs/auditoria/rutas-web.md` y `docs/auditoria/rutas-api.md` para el detalle completo (ambos con su propia nota de actualización).

- `routes/web.php`: 98 líneas, ~25 rutas efectivas.
- ~~`routes/api.php`: 63 líneas, ~10 rutas efectivas.~~ Hoy 14 rutas efectivas (2 nuevas de Fase 7) más el alias `/api/v1/`.
- ~~Ambos archivos importan controladores inexistentes (`GestorController`, `SensorDataController`)...~~ **Resuelto (`ARQ-003`).**

## 14. Vistas (Blade)

~60 vistas propias (fuera de `vendor/*.blade.php` publicadas por paquetes). Estructura por módulo (`cartas/`, `ninios/`, `usuarios/`, `comunidad/`, `mis-ninios/`, `responder-cartas/`) con subcarpeta `v2/` para una segunda versión de las plantillas de PDF de cartas (introducida en el commit `a93f366 "cartas v2"`, conviviendo con las plantillas v1 sin eliminarlas). Componentes reutilizables parciales en `resources/views/sections/` (menú, footer, alertas, notificaciones).

## 15. Integraciones externas

| Integración | Estado |
|---|---|
| Firebase (FCM) | ~~Configurada vía `larafirebase` (`config/larafirebase.php`, env `FIREBASE_SERVER_KEY`); usada solo desde `EnviarNotificacionCartaNueva::toFirebase()`~~ **Reescrito en Fase 7:** `kreait/laravel-firebase` (`config/firebase.php`, env `FIREBASE_CREDENTIALS`), vía `app/Services/FirebaseService.php` y `app/Notifications/Channels/FirebaseChannel.php`. Soporta múltiples dispositivos por niño (`FIREBASE-002`) |
| Email (SMTP) | Configurado, mailables vía `Notification::toMail`, ~~sin cola~~ ahora encolado (`PERF-001`) |
| PDF (wkhtmltopdf vía Snappy) | ~~`config/snappy.php` apunta a una ruta binaria de **Windows**...~~ **Resuelto (`PDF-001`, Fase 6).** |
| Excel (FastExcel) | Usado solo en `NinioController::subirImportacion` |

## 16. Tests

`tests/Feature/ExampleTest.php` y `tests/Unit/ExampleTest.php` son el boilerplate por defecto de Laravel. **No existen pruebas del dominio** (cartas, autorización, roles, API móvil).
