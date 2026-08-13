# Auditoría de rutas — `routes/web.php`

> Todas las rutas usan el middleware group `web` (sesión, CSRF, cookies) por defecto de Laravel. Se indica middleware adicional cuando aplica.

> **Nota de actualización (auditoría cruzada de documentación, 2026-08-08):** este documento es la fotografía original de la Fase 0 y **no se reescribió** para no perder el registro histórico. Los "Problemas detectados" de `usuarios`, `ninios`, `ninios-importar`, `ninios-subir-importar` y `cartas` (`show`/`edit`/`update`) listados abajo **ya fueron corregidos** (`SEG-001`, `SEG-002`, `SEG-007` — ver `docs/auditoria/problemas-detectados.md` y `docs/auditoria/seguridad.md`). El import muerto de `GestorController` (sección de abajo) **ya no existe** (`ARQ-003`, resuelto).

## Rutas públicas (sin autenticación)

| Método | URI | Controller@método | Middleware | Propósito | Usado por React Native | Problemas detectados |
|---|---|---|---|---|---|---|
| GET | `/` | closure → `view('welcome')` | — | Landing | No | Código comentado de Artisan (`cache:clear`, `migrate:fresh --seed`) dejado en el closure — riesgo si se descomenta accidentalmente en producción |
| GET | `privacidad` | `WelcomeController@privacidad` | — | Política de privacidad | No | — |
| `Auth::routes(['register' => false])` | login, logout, password.* , verification.* | `Auth\*Controller` | — | Autenticación web estándar (registro deshabilitado) | No | — |
| GET | `cartas-ninio/{numero_child}` | `ResponderCartaController@index` | — | Niño ve sus cartas pendientes (identificado solo por `numero_child`, sin cifrar) | No | `numero_child` viaja en texto plano en la URL (enumerable), a diferencia de otras rutas del mismo controlador que sí cifran el ID |
| GET | `carta-ninio-ver/{idcarta}/{numero_child}` | `ResponderCartaController@ver` | — | Ver detalle de una carta respondida | No | IDs cifrados con `Crypt::encryptString` — correcto |
| GET | `carta-ninio-archivo/{id}/{tipo}` | `ResponderCartaController@verArchivo` | — | Servir imagen/PDF de una carta | No | ID cifrado — correcto |
| POST | `carta-ninio-guardar-contestacion` | `ResponderCartaController@guardarContestacion` | — | Niño responde carta tipo Contestación | No | Verifica policy `responderCartaXninio` (débil, ver seguridad) |
| POST | `carta-ninio-guardar-agradecimiento` | `ResponderCartaController@guardarAgradecimiento` | — | Ídem tipo Agradecimiento | No | Ídem |
| POST | `carta-ninio-guardar-iniciada` | `ResponderCartaController@guardarIniciada` | — | Ídem tipo Iniciada | No | Ídem |
| POST | `cartas-ninio.guardar-presentacion-mayor` | `ResponderCartaController@guardarPresentacionMayor` | — | Ídem tipo Presentación (niño mayor) | No | Ídem |
| POST | `cartas-ninio.guardar-presentacion-menor` | `ResponderCartaController@guardarPresentacionMenor` | — | Ídem tipo Presentación (niño menor) | No | Ídem |

## Rutas protegidas — `middleware(['auth','verified'])`

| Método | URI | Controller@método | Rol requerido (real) | Propósito | Problemas detectados |
|---|---|---|---|---|---|
| GET | `perfil` | `PerfilController@perfil` | Cualquier autenticado | Ver perfil propio | — |
| POST | `perfil-guardar` | `PerfilController@guardarPerfil` | Cualquier autenticado | Editar perfil propio | — |
| POST | `perfil-cambiar-password` | `PerfilController@cambiarPassword` | Cualquier autenticado | Cambiar contraseña propia | — |
| POST | `perfil-menu-responsivo` | `PerfilController@menuResponsivo` | Cualquier autenticado | Preferencia UI | — |
| GET | `manual-de-usuario` | `WelcomeController@manual` | Cualquier autenticado | Manual de usuario | — |
| GET | `home` | `HomeController@index` | Cualquier autenticado | Dashboard | — |
| POST | `validarec` | `HomeController@validarec` | Cualquier autenticado | Validar cédula/RUC ecuatoriano | — |
| resource | `usuarios` (7 rutas: index/create/store/show/edit/update/destroy) | `UserController` | **Debería ser solo ADMINISTRADOR** | ABM de usuarios y roles | **Ninguna verificación de rol en backend** — cualquier autenticado puede gestionar usuarios y asignar roles. Ver `seguridad.md` SEG-001 |
| resource | `comunidad` | `ComunidadController` | Debería ser solo ADMINISTRADOR | ABM de comunidades | Sin verificación de rol en backend (impacto menor, no expone PII directamente) |
| resource | `ninios` | `NinioController` | Debería ser solo ADMINISTRADOR | ABM global de niños | **Sin verificación de rol ni policy** — expone y permite modificar niños de cualquier comunidad. Ver SEG-002 |
| GET | `ninios-importar` | `NinioController@importar` | Debería ser solo ADMINISTRADOR | Formulario de importación Excel | Mismo problema |
| POST | `ninios-subir-importar` | `NinioController@subirImportacion` | Debería ser solo ADMINISTRADOR | Procesar importación | Mismo problema; además sin límites de validación de filas |
| resource | `mis-ninios` | `MisNiniosController` | GESTOR (o ADMIN) | ABM de niños del propio gestor | Correctamente acotado con `Rule::in` + `$this->authorize()` |
| resource | `cartas` | `CartaController` | GESTOR (propias) / ADMIN (todas) | ABM de cartas | `index` filtra correctamente en el DataTable; `show`/`edit`/`update` **no llaman `$this->authorize()`** — solo `destroy` lo hace. Ver SEG-007 |
| GET | `cartas-ver-carta-pdf/{id}/{version?}` | `CartaController@verPDF` | Cualquier autenticado | Ver PDF inline | Sin `$this->authorize()`: cualquier gestor autenticado puede ver el PDF de una carta ajena conociendo/adivinando el ID numérico |
| GET | `cartas-ver-archivo/{id}/{tipo}` | `CartaController@verArchivo` | Cualquier autenticado | Servir archivo de la carta | Igual, sin autorización por pertenencia |
| GET | `cartas-documentos/{id}` | `CartaController@documentos` | Cualquier autenticado | Ver documentos anexos | Igual |
| GET | `cartas-descargar-pdf/{id}` | `CartaController@descargarPdf` | Cualquier autenticado | Descargar PDF | Igual |
| GET | `ver-boleta-archivo-imagen/{id}` | `CartaController@verBoletaArchivoImagen` | Cualquier autenticado | Ver imagen de boleta de depósito | Igual, sin verificar que la boleta pertenezca a una carta del gestor |

## Imports muertos

~~`routes/web.php` importa `App\Http\Controllers\GestorController`, que **no existe en el repositorio** y no se usa en ninguna ruta.~~ **Resuelto (`ARQ-003`, 2026-08-08).**
