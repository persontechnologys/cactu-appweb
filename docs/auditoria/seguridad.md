# Auditoría de seguridad

> Metodología: análisis estático de código (rutas, controllers, policies, middleware, DataTables, configuración). No se realizaron pruebas de intrusión activas ni se modificó código. Todos los hallazgos deben tratarse como potenciales hasta ser confirmados en un entorno de pruebas antes de exponerse en producción.

> **Nota de actualización (auditoría cruzada Fase 0.1, 2026-08-08):** este documento es la fotografía original de la Fase 0 y **no se reescribió** para no perder el registro histórico del hallazgo. Desde entonces, **SEG-001, SEG-002, SEG-003, SEG-004, SEG-005, SEG-007 y SEG-008 ya fueron corregidos** en la rama `security/fase-2-hallazgos-criticos` (ver detalle y commits en `docs/cambios/changelog.md`, y el estado formal por hallazgo en `docs/auditoria/problemas-detectados.md`). SEG-006 fue analizado y diferido deliberadamente (ver changelog). SEG-009 a SEG-012 no han sido revisados en esta nota. El impacto de estos cierres sobre el cliente React Native se documentó en `docs/auditoria/api-consumida-por-movil.md`, `matriz-api-movil.md` y `docs/movil/cambios-requeridos.md`: ninguno requirió cambios en el móvil.

## Resumen de severidad

| ID | Severidad | Título |
|---|---|---|
| SEG-001 | **CRÍTICO** | Escalación de privilegios: gestión de usuarios/roles sin autorización de backend |
| SEG-002 | **CRÍTICO** | Exposición y modificación de niños de cualquier comunidad por cualquier usuario autenticado |
| SEG-003 | **CRÍTICO** | Descarga pública y anónima de PDFs de cartas mediante ID secuencial (`/api/dcpdf/{id}`) |
| SEG-004 | **CRÍTICO** | IDOR en `listar-mis-cartas`: el backend confía en un `userId` enviado por el cliente |
| SEG-005 | **ALTO** | Endpoints de respuesta/eliminación de carta (API móvil) no verifican pertenencia al niño autenticado |
| SEG-006 | **MEDIO** | La policy `responderCartaXninio` no valida al dueño real de la carta |
| SEG-007 | **MEDIO** | `CartaController` no autoriza `show`/`edit`/`update`, solo `destroy` |
| SEG-008 | **MEDIO** | Mensajes de excepción (`$th->getMessage()`) expuestos al usuario final |
| SEG-009 | **BAJO** | Imports de controllers inexistentes en rutas (código muerto/fantasma) |
| SEG-010 | **BAJO** | Importación Excel de niños sin validación robusta de filas |
| SEG-011 | **BAJO** | Notificaciones marcadas para cola pero enviadas de forma síncrona |
| SEG-012 | **INFORMATIVO** | Autenticación de niño en API basada únicamente en `numero_child` (sin secreto adicional) |

---

## SEG-001 — CRÍTICO: Escalación de privilegios en gestión de usuarios

**Ubicación:** `app/Http/Controllers/UserController.php`, `routes/web.php:63`, `app/Http/Kernel.php`, `app/DataTables/UserDataTable.php:34-37`.

**Comportamiento actual:**
- Las rutas `Route::resource('usuarios', UserController::class)` solo están protegidas por `middleware(['auth','verified'])`. No existe middleware `role:` ni `can:` en la ruta, ni `$this->authorize()` dentro de `UserController`.
- `app/Http/Kernel.php` no registra ningún alias de middleware de `spatie/laravel-permission` (`role`, `permission`, `role_or_permission`), pese a que el paquete está instalado y en uso (`HasRoles` en `User`, directiva `@role` en Blade).
- El único control existente es visual: `resources/views/sections/menu.blade.php:41-57` oculta los enlaces del menú con `@role('ADMINISTRADOR')`. Esto **no protege el backend** (regla de oro violada, ver `CLAUDE.md` sección 20).
- `UserDataTable::query()` retorna `User::newQuery()` sin ningún filtro — cualquier usuario autenticado que visite `/usuarios` ve el listado completo de usuarios del sistema.
- `UserController::store()` llama `$user->assignRole($request->roles)` sin restringir qué roles puede asignar el solicitante.
- `UserController::update()` permite `syncRoles($request->roles)` sobre cualquier usuario que actualmente **no** tenga el rol ADMINISTRADOR — incluido el propio usuario autenticado.

**Escenario de explotación concreto:** un usuario con rol `GESTOR` inicia sesión legítimamente, navega directamente a `https://cactu.ec/usuarios/{su_propio_id}/edit` (o hace `PUT /usuarios/{id}` con herramientas como Postman) y envía `roles[]` con el ID del rol `ADMINISTRADOR`. El backend lo acepta y el gestor pasa a tener privilegios de administrador total sobre el sistema.

**Comportamiento esperado:** middleware `role:ADMINISTRADOR` (o equivalente) en las rutas de `usuarios`, `comunidad` y `ninios`, más una whitelist explícita de qué roles puede asignar cada actor.

**Impacto:** Web — total (control de acceso roto). API — no aplica directamente (rutas web). React Native — no aplica.

---

## SEG-002 — CRÍTICO: Exposición y modificación de niños de cualquier comunidad

**Ubicación:** `app/Http/Controllers/NinioController.php`, `routes/web.php:67-69`, `app/DataTables/NinioDataTable.php:42-45`.

**Comportamiento actual:** mismo patrón que SEG-001. `NinioController` (distinto de `MisNiniosController`) no tiene ninguna verificación de rol ni de comunidad. `NinioDataTable::query()` retorna `Ninio::newQuery()` sin filtrar. Contrasta con `app/DataTables/Carta/NinioDataTable.php` (el selector de niño al crear una carta), que sí filtra correctamente por `comunidad_id` del gestor cuando no es ADMINISTRADOR — lo que confirma que el patrón correcto existe en el proyecto pero no se aplicó aquí.

**Escenario de explotación:** un `GESTOR` autenticado visita `/ninios` directamente (aunque el enlace del menú esté oculto) y ve/edita/elimina niños de comunidades que no le pertenecen, incluyendo datos personales (nombre completo, fecha de nacimiento, email, celular) de menores de edad.

**Impacto:** Web — alto (PII de menores). API — no aplica. React Native — no aplica directamente, pero los datos expuestos son los mismos que consume la app.

---

## SEG-003 — CRÍTICO: Descarga pública y anónima de PDFs vía ID secuencial

**Ubicación:** `routes/api.php:45`, `app/Http/Controllers/WelcomeController.php:22-32`.

**Comportamiento actual:** `GET /api/dcpdf/{id}` no está dentro del grupo `auth:sanctum` y usa `Carta::findOrFail($id)` con el ID autoincremental de la tabla `cartas`, sin cifrar. La ruta hermana `ver-archivo-pdf-por-ninio/{id}` sí cifra el ID con `Crypt::encryptString`, lo que demuestra que el patrón correcto se conoce y se usó en otro lado.

**Escenario de explotación:** cualquier persona, sin autenticarse, puede iterar `GET /api/dcpdf/1`, `/api/dcpdf/2`, `/api/dcpdf/3`... y descargar el PDF de la carta de cualquier niño (contiene foto, nombre y relato personal del menor).

**Comportamiento esperado:** cifrar el ID igual que en las rutas hermanas, o exigir `auth:sanctum` + verificación de pertenencia.

**Impacto:** Web — no aplica (ruta API). API/React Native — crítico (exposición de datos de menores sin control de acceso). Riesgo reputacional y de protección de datos de menores especialmente alto.

---

## SEG-004 — CRÍTICO: IDOR en `listar-mis-cartas` (confía en `userId` del cliente)

**Ubicación:** `app/Http/Controllers/Api/ResponderCarta.php:18-46`.

**Comportamiento actual:**
```php
public function listaCartas (Request $request) {
    $ninio = Ninio::find($request->userId); // valor enviado por el cliente
    ...
}
```
La ruta está protegida por `auth:sanctum`, por lo que existe un niño autenticado disponible en `$request->user()`, pero el código ignora esa identidad y usa el `userId` que el propio cliente envía en el body.

**Escenario de explotación:** la app móvil de un niño A envía `POST /api/listar-mis-cartas` con `userId` correspondiente al niño B. El servidor retorna el listado completo de cartas de B (asunto, tipo, estado, enlaces de descarga de PDF/foto) sin verificar que el token pertenezca a B.

**Comportamiento esperado:** usar siempre `$request->user()` (el modelo `Ninio` resuelto por el token Sanctum) en lugar de un identificador enviado por el cliente.

**Impacto:** API/React Native — alto (fuga de datos entre niños).

---

## SEG-005 — ALTO: `guardarCarta`/`eliminarCarta` (API) no verifican pertenencia del niño autenticado

**Ubicación:** `app/Http/Controllers/Api/ResponderCarta.php:173-218` (`guardarCarta`) y `:279-288` (`eliminarCarta`).

**Comportamiento actual:** ambos métodos hacen `Carta::findOrFail($request->id)` / `Carta::find($request->id)` sin comprobar que `$carta->ninio_id` corresponda al niño autenticado (`$request->user()->id`). El equivalente web (`ResponderCartaController::guardarArchivo`) al menos invoca `$this->authorize('responderCartaXninio', $carta)`, aunque esa policy también es débil (ver SEG-006).

**Escenario de explotación:** un niño autenticado puede responder o marcar como eliminada (`eliminado=true`) la carta de otro niño enviando su ID en el body de la petición.

**Nota adicional:** `eliminarCarta` no comprueba que `Carta::find()` haya devuelto un resultado antes de hacer `$carta->eliminado=true`, lo que provocaría un error 500 (llamada a método sobre `null`) si el ID no existe.

**Impacto:** API/React Native — alto (integridad de datos, posible denegación de servicio lógica sobre cartas ajenas).

---

## SEG-006 — MEDIO: La policy `responderCartaXninio` no valida al dueño real

**Ubicación:** `app/Policies/CartaPolicy.php`.

```php
public function responderCartaXninio(?User $user, Carta $carta): bool
{
    if($carta->estado==='Respondida'){
        return false;
    }
    return true;
}
```

Esta policy solo verifica el estado de la carta, ignorando por completo quién es `$user`. Además, el tipo declarado es `User`, pero quien la invoca en la práctica es un `Ninio` autenticado (modelo distinto) — debe confirmarse que Laravel resuelve correctamente este caso o si la policy simplemente nunca actúa como control real de identidad.

**Impacto:** Web — bajo/medio (la ruta web ya depende de IDs cifrados como mitigación parcial). API — refuerza SEG-005.

---

## SEG-007 — MEDIO: `CartaController` no autoriza `show`/`edit`/`update`

**Ubicación:** `app/Http/Controllers/CartaController.php:153-176`.

Solo `destroy()` llama `$this->authorize('eliminar', $carta)`. Un gestor autenticado puede navegar a `/cartas/{id}`, `/cartas/{id}/edit` o enviar `PUT /cartas/{id}` para ver o modificar el campo `detalle` de una carta que no le pertenece (de otro gestor/comunidad), simplemente conociendo o adivinando el ID numérico.

**Impacto:** Web — medio (exposición de contenido de cartas y posibilidad de alterar el campo `detalle` de cartas ajenas).

---

## SEG-008 — MEDIO: Mensajes de excepción expuestos al usuario final

**Ubicación:** `CartaController::store/destroy`, `ResponderCartaController::guardarArchivo`, `Api\ResponderCarta::guardarCarta`, entre otros.

Ejemplo: `Session::flash('info','Carta no enviado'.$th->getMessage());` y `response()->json(['error'=>'Carta no registrado vuelva intentar'.$th->getMessage()])`. Esto filtra mensajes internos de excepciones (posibles nombres de tablas/columnas, rutas de archivos del servidor) a la interfaz visible por el usuario final. El riesgo se agrava si `APP_DEBUG=true` llegara a producción (el `.env.example` lo trae en `true` para entorno local; verificar que el `.env` real de producción tenga `APP_DEBUG=false`, dato que no se audita aquí por estar fuera del repositorio).

**Impacto:** Web/API — medio (fuga de información interna).

---

## SEG-009 — BAJO: Imports de controllers inexistentes en archivos de rutas

**Ubicación:** `routes/web.php:5` (`GestorController`), `routes/api.php:4` (`SensorDataController`).

Ambas clases no existen en el repositorio y no se referencian en ninguna ruta activa. No generan error en tiempo de ejecución, pero son evidencia de código/funcionalidad retirada sin limpiar, y podrían confundir a un desarrollador nuevo que asuma que esas features existen.

---

## SEG-010 — BAJO: Importación Excel de niños sin validación robusta

**Ubicación:** `app/Http/Controllers/NinioController.php:119-141`.

`subirImportacion()` usa `firstOrCreate` fila por fila sin validar previamente el formato de `genero`, `fecha_nacimiento`, `email`, etc. Una fila malformada puede crear un registro con datos inconsistentes (por ejemplo, `genero` fuera del enum `Male/Female`, lo que provocaría una excepción de base de datos a mitad de la importación, dejando el archivo parcialmente importado sin reporte claro al usuario).

---

## SEG-011 — BAJO: Notificaciones marcadas para cola pero enviadas de forma síncrona

**Ubicación:** `app/Notifications/EnviarNotificacionCartaNueva.php`, `app/Notifications/EnviarCartaRespondidaGestor.php`.

Ambas clases importan `Illuminate\Contracts\Queue\ShouldQueue` pero no lo implementan (`class ... extends Notification` sin `implements ShouldQueue`). El trait `Queueable` por sí solo no encola nada. Esto es más un hallazgo de rendimiento/arquitectura que de seguridad estricta, pero se incluye aquí porque el envío síncrono a Firebase/SMTP dentro del ciclo de vida de una petición HTTP aumenta la superficie de fallos y tiempos de espera (ver también `deuda-tecnica.md`).

---

## SEG-012 — INFORMATIVO: Autenticación del niño basada solo en `numero_child`

**Ubicación:** `routes/api.php:13-41`.

El "login" de la app móvil no exige contraseña ni PIN: basta con conocer el `numero_child` (probablemente un identificador visible en materiales físicos de la organización, como carnets o boletas) para obtener un token Sanctum válido en nombre de ese niño. Esto es una decisión de producto plausible dado el público objetivo (niños patrocinados sin capacidad de gestionar contraseñas), pero debe documentarse como riesgo aceptado explícitamente, no como un descuido. Se recomienda evaluar un segundo factor simple (ej. fecha de nacimiento) para reducir el riesgo de que un tercero acceda a la cuenta de un niño solo por conocer su número.

---

## Aspectos que SÍ están bien implementados (para no perder de vista lo que funciona)

- CSRF: manejado por el middleware estándar `VerifyCsrfToken` en el grupo `web`.
- Contraseñas: hasheadas vía cast `'password' => 'hashed'` en `User` y `Hash::make()` en los flujos correspondientes.
- `MisNiniosController` y `CartaDataTable` muestran el patrón correcto de acotamiento por comunidad/usuario, que debería replicarse en `NinioController`/`UserController`.
- Los enlaces de "responder carta" que sí cifran el ID (`Crypt::encryptString`) son un buen patrón, aplicado de forma inconsistente en el resto del sistema.
- Los archivos subidos (boletas, cartas PDF) se validan por `mimes`/`image` en las reglas de `CartaController::store`.

## Recomendación general

No se recomienda corregir estos hallazgos como parte de la Fase 0 (auditoría). Los ítems CRÍTICOS (SEG-001 a SEG-004) deberían priorizarse al inicio de la **Fase 2 — Seguridad** del roadmap, dado que son explotables por cualquier usuario autenticado (SEG-001, SEG-002) o incluso sin autenticación (SEG-003), y afectan datos personales de menores de edad.
