# Changelog

> Formato según `CLAUDE.md` sección 41. Un bloque por cambio funcional relevante.

---

## 2026-08-08 — Fase 2, Seguridad: hallazgos CRÍTICO y ALTO

**Fase:** Fase 2 — Seguridad
**Rama:** `security/fase-2-hallazgos-criticos`
**Motivo:** cerrar los hallazgos CRÍTICO y ALTO documentados en `docs/auditoria/seguridad.md`, autorizados explícitamente para esta fase.

### SEG-001 — Escalación de privilegios en /usuarios y /comunidad

**Cambio:** se registran los alias de middleware `role`/`permission`/`role_or_permission` de spatie/laravel-permission en `app/Http/Kernel.php` (no existían) y se agrupan las rutas `usuarios`, `comunidad` y `ninios` bajo `role:ADMINISTRADOR` en `routes/web.php`. Se añade `App\Policies\UserPolicy` y `$this->authorize()` en `UserController` como defensa en profundidad. `UserController::store`/`update` ahora rechazan asignar/sincronizar el rol ADMINISTRADOR mediante ese formulario (whitelist explícita en backend).
**Archivos:** `app/Http/Kernel.php`, `routes/web.php`, `app/Policies/UserPolicy.php`, `app/Providers/AuthServiceProvider.php`, `app/Http/Controllers/UserController.php`.
**Impacto:** ya no es posible ascender a un usuario a ADMINISTRADOR desde `/usuarios` (ni crear ni editar). `/comunidad` también se incluyó en la protección porque comparte el mismo patrón sin protección (cualquier autenticado podía reasignar el gestor dueño de cualquier comunidad).
**Pruebas:** `tests/Feature/Seguridad/UsuariosAutorizacionTest.php`.

### SEG-002 — Exposición y modificación de niños de cualquier comunidad

**Cambio:** las rutas de `NinioController` quedan restringidas a `role:ADMINISTRADOR` (mismo grupo que SEG-001). Se añade `NinioPolicy::viewAny`/`create` y `$this->authorize()` en todo `NinioController`. Se filtra `NinioDataTable::query()` por comunidad del gestor como defensa en profundidad, replicando el patrón de `App\DataTables\Carta\NinioDataTable`.
**Archivos:** `app/Http/Controllers/NinioController.php`, `app/Policies/NinioPolicy.php`, `app/DataTables/NinioDataTable.php`.
**Impacto:** no afecta a `MisNiniosController` (acceso legítimo de GESTOR a los niños de su propia comunidad).
**Pruebas:** `tests/Feature/Seguridad/NiniosAutorizacionTest.php`.

### SEG-003 — Descarga pública y anónima de PDF por ID secuencial

**Cambio:** `WelcomeController::descargarCartaPdf` ahora descifra el ID con `Crypt::decryptString` (404 si no es válido), replicando el patrón ya usado en `pdfninio`/`ver-archivo-pdf-por-ninio`. Se actualiza el único generador de este enlace (`Api\ResponderCarta::listaCartas`, campo `carta`) para emitir el ID ya cifrado.
**Archivos:** `app/Http/Controllers/WelcomeController.php`, `app/Http/Controllers/Api/ResponderCarta.php`.
**Impacto móvil:** ruptura de contrato deliberada y necesaria — el campo `carta` del listado de cartas pasa a contener un ID cifrado. La app solo sigue el enlace devuelto por el backend, no lo construye, por lo que no debería requerir cambios en el cliente, pero se recomienda confirmarlo con el equipo React Native antes de desplegar.
**Pruebas:** `tests/Feature/Seguridad/DescargaPdfCartaTest.php`.

### SEG-004 — IDOR en listar-mis-cartas (userId del cliente)

**Cambio:** `Api\ResponderCarta::listaCartas` usa `$request->user()` (niño resuelto por el token Sanctum) en vez de `Ninio::find($request->userId)`.
**Archivos:** `app/Http/Controllers/Api/ResponderCarta.php`.
**Impacto móvil:** **requiere coordinación con el equipo móvil antes de desplegar.** Si la app sigue enviando `userId`, el campo simplemente se ignora sin error; debe confirmarse que ningún flujo dependa de que el backend respete un `userId` distinto al del niño autenticado (ver `docs/auditoria/integracion-movil.md`, sección 7).
**Pruebas:** `tests/Feature/Api/ListarMisCartasTest.php`.

### SEG-005 — guardarCarta/eliminarCarta (API) no verificaban dueño

**Cambio:** ambos métodos verifican `$carta->ninio_id === $request->user()->id` (403 si no coincide) antes de procesar. `eliminarCarta` pasa de `Carta::find()` a `Carta::findOrFail()` (necesario para aplicar la verificación de forma segura; de paso corrige el error 500 que la propia auditoría señalaba para IDs inexistentes, ahora responde 404).
**Archivos:** `app/Http/Controllers/Api/ResponderCarta.php`.
**Pruebas:** `tests/Feature/Api/ResponderCartaAutorizacionTest.php`.

### SEG-007 — CartaController no autorizaba show/edit/update

**Cambio:** se añaden `CartaPolicy::view`/`update` (dueño del gestor o ADMINISTRADOR, mismo criterio que `eliminar`) y `$this->authorize()` en `CartaController::show/edit/update`.
**Archivos:** `app/Http/Controllers/CartaController.php`, `app/Policies/CartaPolicy.php`.
**Pruebas:** `tests/Feature/Seguridad/CartaAutorizacionTest.php`.

### SEG-006 — analizado, no corregido en esta fase

`CartaPolicy::responderCartaXninio` solo valida el estado de la carta, no la identidad de quien responde. Al inspeccionar `ResponderCartaController` (flujo web `cartas-ninio.*`) se confirmó que esas rutas están **fuera** del grupo `auth`/`verified` — no existe niño autenticado en ese flujo; la única protección es el ID de carta cifrado en la URL. No hay una identidad real contra la cual validar sin rediseñar el flujo (por ejemplo, exigir y verificar el `numero_child` cifrado en `guardarArchivo`, tal como ya hace `ResponderCartaController::ver()`). Se deja documentado como pendiente en `docs/auditoria/problemas-detectados.md` en vez de aplicar un cambio apresurado que podría romper enlaces ya enviados a patrocinadores/niños.

### Infraestructura de pruebas

Se habilita sqlite en memoria para Feature tests (`phpunit.xml`, antes usaba la conexión mysql real por defecto porque estaba comentada) y se retira `BCRYPT_ROUNDS=4` (entraba en conflicto con el cast `hashed` de `User` al validar hashes de bcrypt pre-generados con costo 10 en `UserFactory`). Se añade `tests/Concerns/CreaUsuariosDePrueba.php` como trait reutilizable para crear usuarios ADMINISTRADOR/GESTOR y comunidades de prueba.

### Bug preexistente detectado (no corregido, fuera de alcance)

Al escribir las pruebas de SEG-001 se detectó que `app/Http/Requests/User/UpdateRq.php` construye la regla `unique:users,email,'.$this->id` asumiendo un parámetro de ruta llamado `id`, pero `Route::resource('usuarios', ...)` genera el parámetro `usuario`. `$this->id` siempre resuelve a `null`, por lo que la validación de email único **siempre falla** al editar un usuario sin cambiar su email (falso positivo "el email ya está en uso"). No se corrige aquí por no ser parte del alcance de los hallazgos de seguridad autorizados; se registra en `docs/auditoria/problemas-detectados.md` como BUG-001 para una fase futura.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
