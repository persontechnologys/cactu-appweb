# Changelog

> Formato según `CLAUDE.md` sección 41. Un bloque por cambio funcional relevante.

---

## 2026-08-08 — Fase 1, Arquitectura: fundamentos de bajo riesgo

**Fase:** Fase 1 — Arquitectura
**Rama:** `security/fase-2-hallazgos-criticos`
**Motivo:** preparar el terreno arquitectónico definido en `docs/fases/roadmap.md`, sin tocar comportamiento visible, autorizado explícitamente para continuar tras el cierre de Fase 2.

### Middleware role/permission en Kernel

Ya estaba resuelto: se registró como parte del commit de SEG-001 (Fase 2), antes de empezar esta fase. Sin acción adicional.

### ARQ-002 — Extraer `RegistrarCartaAction` desde `CartaController::store`

**Cambio:** se crea `app/Actions/RegistrarCartaAction.php` (primera Action del proyecto) con la creación de la `Carta`, el guardado de boletas y del PDF adjunto, antes concentrados en `CartaController::store`. El controller conserva la validación (`Request::validate`), invoca la Action, envía la notificación y prepara la respuesta — sin cambiar el mensaje de error ni el flujo de `try/catch` existente.
**Archivos:** `app/Actions/RegistrarCartaAction.php` (nuevo), `app/Http/Controllers/CartaController.php`.
**Impacto:** ninguno sobre comportamiento observable.
**Pruebas:** `tests/Feature/Cartas/RegistrarCartaTest.php` (nuevo), escrito y verificado en verde **antes** del refactor, y sigue en verde después. Suite completa: 28/28 tests en verde.

### ARQ-003 — Imports muertos

**Cambio:** se eliminan 3 imports que apuntaban a clases inexistentes, confirmado por grep que no se usaban en ningún otro punto del archivo (`CLAUDE.md` sección 49): `GestorController` (`routes/web.php`), `SensorDataController` (`routes/api.php`) y `UserRqStore` (`app/Http/Controllers/UserController.php`, detectado en la misma revisión, no estaba en la ficha original de ARQ-003).
**Archivos:** `routes/web.php`, `routes/api.php`, `app/Http/Controllers/UserController.php`.
**Impacto:** ninguno. `route:list` sigue resolviendo las mismas 84 rutas; suite de tests en verde.

### Convención de nombres de Policies (documentación, sin cambios de código)

Se define la convención objetivo (verbos Laravel: `view`, `viewAny`, `create`, `update`, `delete`) para habilitar `authorizeResource()` a futuro. Al revisar `CartaPolicy` se encontró que sus métodos legacy en español (`ver`, `eliminar`) **no son alias** de los métodos Laravel ya agregados en Fase 2 (`view`, `update`) — tienen lógica distinta. No se renombra nada en esta fase; se documenta como `ARQ-004` en `docs/auditoria/problemas-detectados.md` y como decisión en `docs/cambios/decisiones-arquitectura.md` (nuevo archivo), pendiente de una decisión de negocio antes de tocar esos métodos.
**Archivos:** `docs/cambios/decisiones-arquitectura.md` (nuevo), `docs/auditoria/problemas-detectados.md`.

### Qué no se hizo en esta fase

No se creó un `Service` de almacenamiento de archivos (mencionado como idea en la ficha original de ARQ-002, pero fuera del alcance que el roadmap definió para Fase 1). No se convirtió la validación de `store()` a un Form Request. No se renombraron los métodos de `CartaPolicy`. No se pusheó la rama ni se abrió PR (decisión del usuario).

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

### SEG-008 — Mensajes de excepción expuestos al usuario final

**Cambio:** en `UserController::store/update`, `CartaController::store/destroy`, `MisNiniosController::destroy` y `Api\ResponderCarta::guardarCarta`, se retira `.$th->getMessage()` de los mensajes flash y respuestas JSON de error, y se añade `Log::error()` con el detalle real de la excepción para diagnóstico interno.
**Archivos:** `app/Http/Controllers/UserController.php`, `app/Http/Controllers/CartaController.php`, `app/Http/Controllers/MisNiniosController.php`, `app/Http/Controllers/Api/ResponderCarta.php`.
**Pruebas:** `tests/Feature/Seguridad/MensajesErrorTest.php` (caso representativo vía API; los otros 5 puntos siguen el mismo patrón, verificado por revisión de código).

### Infraestructura de pruebas

Se habilita sqlite en memoria para Feature tests (`phpunit.xml`, antes usaba la conexión mysql real por defecto porque estaba comentada) y se retira `BCRYPT_ROUNDS=4` (entraba en conflicto con el cast `hashed` de `User` al validar hashes de bcrypt pre-generados con costo 10 en `UserFactory`). Se añade `tests/Concerns/CreaUsuariosDePrueba.php` como trait reutilizable para crear usuarios ADMINISTRADOR/GESTOR y comunidades de prueba.

### Bug preexistente detectado (no corregido, fuera de alcance)

Al escribir las pruebas de SEG-001 se detectó que `app/Http/Requests/User/UpdateRq.php` construye la regla `unique:users,email,'.$this->id` asumiendo un parámetro de ruta llamado `id`, pero `Route::resource('usuarios', ...)` genera el parámetro `usuario`. `$this->id` siempre resuelve a `null`, por lo que la validación de email único **siempre falla** al editar un usuario sin cambiar su email (falso positivo "el email ya está en uso"). No se corrige aquí por no ser parte del alcance de los hallazgos de seguridad autorizados; se registra en `docs/auditoria/problemas-detectados.md` como BUG-001 para una fase futura.

## 2026-08-08 — Fase 4: Actions únicas para responder carta (ARQ-001)

**Fase:** Fase 4 — Unificación Web/API.
**Motivo:** el HTML de `detalle` de los 5 tipos de respuesta de carta (Contestación, Agradecimiento, Iniciada, Presentación mayor/menor) estaba duplicado carácter por carácter entre `ResponderCartaController` (web) y `Api\ResponderCarta` (móvil) — confirmado con `diff` antes de tocar nada, no por inspección visual.
**Cambio:** se crean 6 Actions en `app/Actions/`: `ResponderCartaContestacionAction`, `ResponderCartaAgradecimientoAction`, `ResponderCartaIniciadaAction`, `ResponderCartaPresentacionMayorAction`, `ResponderCartaPresentacionMenorAction` (arman el `detalle` a partir de primitivos, una por tipo) y `GuardarRespuestaCartaAction` (compartida por las 5: guarda imagen/foto_familia, marca la carta `Respondida`, fija `fecha_respondida` y notifica al gestor). Ambos controladores ahora delegan en ellas.
**Archivos:** `app/Actions/GuardarRespuestaCartaAction.php`, `app/Actions/ResponderCarta{Contestacion,Agradecimiento,Iniciada,PresentacionMayor,PresentacionMenor}Action.php`, `app/Http/Controllers/ResponderCartaController.php`, `app/Http/Controllers/Api/ResponderCarta.php`.
**Qué NO se unificó (decisión explícita, ver `docs/cambios/decisiones-arquitectura.md`):** resolución de la carta (id cifrado sin sesión en web vs. id plano + niño autenticado por Sanctum en API), autorización (`Policy` en web vs. comparación manual de `ninio_id` en API), el corte temprano cuando la carta ya está `Respondida` (403 en web vs. `200 {info:...}` sin persistir en API), y el formato de respuesta (redirect+flash vs. JSON) — son decisiones de cada canal, no duplicación accidental.
**Impacto Web:** ninguno sobre comportamiento observable (mismas rutas, misma validación, mismo resultado). **Impacto API:** ninguno sobre el contrato (mismas rutas, mismo JSON de respuesta en los 3 casos: éxito, ya respondida, error). **Impacto React Native:** ninguno — no se tocó ningún endpoint ni forma de respuesta.
**Pruebas:** `tests/Unit/Actions/ResponderCartaAccionesTest.php` (fija el HTML exacto de las 5 plantillas y el guardado/estado/notificación), `tests/Feature/Cartas/ResponderCartaWebTest.php`, `tests/Feature/Api/ResponderCartaTest.php` (end-to-end por canal); toda la suite (`php artisan test`, 40 tests) verificada en verde tras el cambio, incluyendo `ResponderCartaAutorizacionTest.php` (SEG-005) y `MensajesErrorTest.php` (SEG-008) ya existentes.

## 2026-08-08 — Fase 4: Form Requests para responder-carta (Web + API)

**Fase:** Fase 4 — Unificación Web/API.
**Motivo:** `docs/auditoria/funcionalidades-compartidas.md` señalaba la validación de campos obligatorios por tipo de carta como "el ejemplo más claro de violación de la regla de oro de negocio" — triplicada entre Blade (atributos `required`), `$request->validate([...])` inline en `ResponderCartaController` (web) y en `Api\ResponderCarta` (móvil), y `validar()` en cada pantalla de `CactuApp`. Se confirmó contra el código real que los 5 arrays de reglas eran idénticos carácter por carácter entre web y API (incluido el typo histórico `mifamila` en Presentación Menor, también presente en el nombre de campo que envía el móvil).
**Cambio:** se crean 5 `FormRequest` en `app/Http/Requests/Cartas/` (`ResponderCartaContestacionRequest`, `ResponderCartaAgradecimientoRequest`, `ResponderCartaIniciadaRequest`, `ResponderCartaPresentacionMayorRequest`, `ResponderCartaPresentacionMenorRequest`), cada uno con el array de reglas exacto que antes estaba duplicado, y `authorize()` fijo en `true` (la autorización real sigue post-validación y es distinta por canal, sin cambios — ver ARQ-001). Ambos controladores pasan de `Request $request` + `$request->validate([...])` inline al `FormRequest` correspondiente como type-hint del parámetro.
**Archivos:** `app/Http/Requests/Cartas/ResponderCarta{Contestacion,Agradecimiento,Iniciada,PresentacionMayor,PresentacionMenor}Request.php` (nuevos), `app/Http/Controllers/ResponderCartaController.php`, `app/Http/Controllers/Api/ResponderCarta.php`.
**Qué NO se tocó:** `eliminarCarta` y `listaCartas` (no forman parte de la duplicación ARQ-001), el umbral `edad > 5` (mayor/menor, sigue existiendo por separado en la vista web y en `Presentacion.js` del móvil — confirmado igual en ambos, pero es una decisión de qué formulario mostrar, no de validación), y el repositorio `CactuApp` (su `validar()` es una tercera pierna que no puede compartir código PHP; pasa a ser solo UX sobre la única fuente de verdad real, que ahora es el `FormRequest`).
**Impacto Web:** ninguno sobre comportamiento observable — mismo orden de validación→autorización, mismos mensajes de error por defecto. **Impacto API:** ninguno sobre el contrato (mismos campos, mismo `422` con los mismos nombres de campo en `errors`). **Impacto React Native:** ninguno — no se tocó ningún endpoint.
**Pruebas:** se añaden `test_falta_un_campo_obligatorio_devuelve_422` (`tests/Feature/Api/ResponderCartaTest.php`) y `test_falta_un_campo_obligatorio_redirige_con_error_de_validacion` (`tests/Feature/Cartas/ResponderCartaWebTest.php`) — caso no cubierto hasta ahora (`CLAUDE.md` sección 36, Validation Tests). Suite completa (`php artisan test`, 42 tests) en verde.

## 2026-08-08 — Fase 4: ARQ-004 (rename de CartaPolicy) y SEG-006 (identidad real del niño al responder carta)

**Fase:** Fase 4 — Unificación Web/API.

### ARQ-004 — `CartaPolicy::ver()` renombrado a `mostrarEnlaceVer()`

**Motivo:** el método legacy `ver()` (exige dueño/admin **y** `estado==='Respondida'`) no es alias de `view()`/`update()` (solo dueño/admin, agregados en SEG-007) pese al nombre parecido — riesgo de que un desarrollador futuro los colapse sin darse cuenta de que cambia una regla de negocio. Decisión del usuario (no autorizó cambiar el comportamiento, solo el nombre): mantener la lógica intacta.
**Cambio:** `ver()` → `mostrarEnlaceVer()`, sin tocar el cuerpo del método. Se actualiza su única referencia real (`resources/views/cartas/action.blade.php:8`, controla el link "Ver"/"Descargar PDF" en el listado de cartas) y la referencia que documentaba la auditoría en `resources/views/cartas/show.blade.php:58` — al revisarla se confirmó que está dentro de un bloque Blade completamente comentado (`{{-- ... --}}`, líneas 51-70), es decir, código muerto que no se ejecuta; se corrige por consistencia igualmente.
**Archivos:** `app/Policies/CartaPolicy.php`, `resources/views/cartas/action.blade.php`, `resources/views/cartas/show.blade.php`.
**Impacto:** ninguno sobre comportamiento observable. Suite completa verificada en verde tras el rename.

### SEG-006 — verificación de identidad real del niño al responder carta (web)

**Motivo:** `CartaPolicy::responderCartaXninio` solo valida que la carta no esté ya `Respondida`, no que quien responde sea el niño dueño real — las rutas `cartas-ninio.*` están fuera del grupo `auth`, sin sesión de niño; la única protección era el `id` de carta cifrado en el formulario. Quedó diferido en Fase 2 por temor a romper enlaces ya enviados a patrocinadores/niños. Al revisar el flujo completo se confirmó que los enlaces `GET` ya enviados (`cartas-ninio.index`, `carta-ninio-ver`) no cambian con este arreglo — solo se agrega un campo al formulario `POST`, que se renderiza en el momento desde la plantilla actual, no es HTML cacheado.
**Cambio:** se replica el patrón que ya usa `ResponderCartaController::ver()` (compara `numero_child` descifrado contra el dueño real de la carta). Los 5 formularios Blade de respuesta (`contestacion`, `agradecimineto`, `iniciada`, `presentacion-mayores`, `presentacion-menores`) agregan un campo `hidden` `ninio` con `Crypt::encryptString($ninio->numero_child)`, junto al `id` ya existente. `ResponderCartaController::procesarRespuesta()` lo descifra y compara contra `$carta->ninio->numero_child`; si falta, no descifra o no coincide, responde 404.
**Dónde NO se aplicó:** no se agregó a los `FormRequest` compartidos con la API (`app/Http/Requests/Cartas/`) — el canal móvil no envía ni necesita este campo, su identidad ya la resuelve Sanctum (SEG-005); meterlo ahí habría roto el contrato con `CactuApp`. La verificación vive solo en `ResponderCartaController::procesarRespuesta()`.
**Archivos:** `app/Http/Controllers/ResponderCartaController.php`, `resources/views/responder-cartas/{contestacion,agradecimineto,iniciada,presentacion-mayores,presentacion-menores}.blade.php`.
**Impacto Web:** un niño ya no puede responder la carta de otro niño conociendo/adivinando el `id` cifrado ajeno. **Impacto API:** ninguno. **Impacto React Native:** ninguno (este flujo web no lo consume el móvil).
**Pruebas:** `test_seg006_un_ninio_no_puede_responder_la_carta_de_otro_ninio`, `test_seg006_falta_el_campo_ninio_responde_404` y `test_el_formulario_renderizado_incluye_el_ninio_cifrado` (nuevos, `tests/Feature/Cartas/ResponderCartaWebTest.php` — el último verifica, con una petición `GET` real, que la vista efectivamente renderiza el campo nuevo y no solo que el `POST` lo acepta); se actualizaron los 2 tests existentes que llegan con éxito a `procesarRespuesta()` para incluir el nuevo campo `ninio`. `php artisan view:cache` verificado sin errores de sintaxis en los 5 formularios editados. Suite completa (`php artisan test`, 45 tests) en verde.

## 2026-08-08 — Fase 5: ARQ-005, versionado `/api/v1/` (alias aditivo)

**Fase:** Fase 5 — API + React Native.
**Motivo:** el roadmap pide introducir versionado antes de cualquier cambio de forma de respuesta. Se confirmó contra `routes/api.php` que las 12 rutas del API no tenían ningún prefijo de versión.
**Cambio:** en `app/Providers/RouteServiceProvider.php::boot()` se añade un segundo montaje del mismo `routes/api.php`, bajo `->prefix('api/v1')->name('v1.')`, junto al montaje original en `/api/`. Es el mismo archivo cargado dos veces (no hay una segunda copia de las rutas), así que ambos prefijos no pueden divergir en comportamiento. Se usó `name('v1.')` específicamente para evitar que las 2 rutas nombradas del archivo (`verarchivopdfporninio`, `descargarCartaPdf`, usadas por `Api\ResponderCarta::listaCartas` vía el helper `route()`) colisionaran entre montajes — ver detalle y riesgo evaluado en `docs/cambios/decisiones-arquitectura.md`.
**Archivos:** `app/Providers/RouteServiceProvider.php`, `docs/movil/api.md`, `docs/auditoria/matriz-api-movil.md`, `docs/cambios/decisiones-arquitectura.md`.
**Impacto Web:** ninguno. **Impacto API:** ninguno sobre `/api/...` existente; queda disponible `/api/v1/...` como alias idéntico. **Impacto React Native:** ninguno — `CactuApp` no necesita cambiar `API_URL`, sigue en `/api/...`.
**Pruebas:** `tests/Feature/Api/VersionadoApiV1Test.php` (nuevo, 3 tests: paridad de `/login`, paridad de `/listar-mis-cartas` byte-a-byte entre ambos prefijos, y no colisión de los 2 nombres de ruta). Suite completa (`php artisan test`, 48 tests) en verde.

## 2026-08-08 — Fase 5: DOC-001, docs/api/ formal + evaluación de API Resources (ARQ-006, sin implementar)

**Fase:** Fase 5 — API + React Native.
**Motivo:** cerrar los otros dos ítems de Fase 5 discutidos con el usuario: documentar formalmente `docs/api/` (DOC-001 quedaba pendiente desde la auditoría original) y evaluar Laravel API Resources (sin implementar todavía, solo diagnóstico, `CLAUDE.md` sección 9).
**Cambio — documentación:** se crea `docs/api/` (`README.md`, `autenticacion.md`, `cartas.md`, `documentos.md`), contrato formal por endpoint desde el lado backend (método, auth, headers, request, response, código HTTP, reglas de negocio, impacto móvil), consolidando lo ya reunido en `docs/auditoria/matriz-api-movil.md`/`api-consumida-por-movil.md` y complementando (sin duplicar) `docs/movil/api.md`.
**Cambio — evaluación:** se crea `docs/auditoria/evaluacion-api-resources.md`. Conclusión: Resources solo aplican de forma natural a `listar-mis-cartas` (único endpoint con forma "de modelo"); los 8 endpoints de escritura necesitan un envelope de respuesta común, patrón distinto y de mayor riesgo (requiere verificar contra el código real de `CactuApp` si alguna pantalla compara claves/strings exactos antes de tocar nada). Se registra como `ARQ-006`, estado "Evaluado", pendiente de autorización.
**Hallazgo nuevo detectado al documentar (no buscado a propósito):** `SEG-013` — el closure `POST /login` en `routes/api.php` expone `$th->getMessage()` en su `catch`, con HTTP `200`. Es el mismo patrón que `SEG-008` (Fase 2), no cubierto en ese cierre porque `SEG-008` solo revisó Controllers y este es un closure inline en el archivo de rutas. Registrado, **sin corregir** — pendiente de autorización.
**Archivos:** `docs/api/README.md`, `docs/api/autenticacion.md`, `docs/api/cartas.md`, `docs/api/documentos.md` (nuevos), `docs/auditoria/evaluacion-api-resources.md` (nuevo), `docs/auditoria/problemas-detectados.md` (DOC-001 actualizado a Resuelto, SEG-013 y ARQ-006 nuevos).
**Impacto Web/API/React Native:** ninguno — este cambio es 100% documentación y diagnóstico, cero código tocado.
**Pruebas:** no aplica (sin cambios de código). Suite completa sigue en 48/48 (sin re-ejecutar, no hubo cambios que pudieran afectarla).

## 2026-08-08 — Fase 5: SEG-013 corregido y ARQ-006 Paso A implementado (autorizado por el usuario)

**Fase:** Fase 5 — API + React Native.
**Motivo:** el usuario autorizó explícitamente corregir `SEG-013` y el "Paso A" de `ARQ-006`, ambos detectados/diagnosticados en el cambio anterior sin implementar.

### SEG-013 — fuga de mensaje de excepción y validación rota en `/login`

**Cambio:** se mueve `$request->validate([...])` fuera del `try/catch` en el closure `POST /login` (`routes/api.php`). Al verificar el comportamiento real antes de tocar el código (`php artisan tinker`) se confirmó que el bug era más amplio de lo documentado: el `catch(\Throwable)` interceptaba también la `ValidationException`, así que una petición sin `numero_child` devolvía HTTP `200` con un string plano en vez del `422` estándar de Laravel — no solo el `catch` genérico filtraba `$th->getMessage()`. El `catch` que queda ahora hace `Log::error()` y responde `{"error": "No se pudo iniciar sesión, intente nuevamente"}`, mismo patrón que `SEG-008`, sin cambiar el código HTTP de ese caso.
**Archivos:** `routes/api.php`, `tests/Feature/Seguridad/LoginTest.php` (nuevo), `docs/api/autenticacion.md`, `docs/cambios/decisiones-arquitectura.md`, `docs/auditoria/problemas-detectados.md`.
**Impacto Web:** No aplica. **Impacto API:** validación de `numero_child` ausente pasa de `200`+string a `422`+`{errors:{...}}` (corrección de bug, no cambio de contrato deliberado); excepciones genuinas no previstas dejan de filtrar detalle interno. **Impacto React Native:** ninguno esperado — `Login.js` usa un teclado numérico que no permite enviar el campo vacío en la práctica.
**Pruebas:** `tests/Feature/Seguridad/LoginTest.php` (2 tests nuevos: `422` estándar sin `numero_child`; no fuga de mensaje forzando una excepción real con `Schema::drop('personal_access_tokens')`, mismo patrón que `MensajesErrorTest` de `SEG-008`, no un mock).

### ARQ-006 (Paso A) — `CartaListaResource` para `listar-mis-cartas`

**Cambio:** se crea `app/Http/Resources/CartaListaResource.php`; `Api\ResponderCarta::listaCartas` delega en `CartaListaResource::collection($cartas)` en vez de construir el array a mano en un `foreach`. Mismos 9 campos, misma lógica por campo, sin cambio de contrato.
**Archivos:** `app/Http/Resources/CartaListaResource.php` (nuevo), `app/Http/Controllers/Api/ResponderCarta.php`, `tests/Feature/Api/ListarMisCartasTest.php`, `docs/cambios/decisiones-arquitectura.md`, `docs/auditoria/problemas-detectados.md`.
**Impacto Web:** No aplica. **Impacto API/React Native:** ninguno — forma de respuesta idéntica, verificada con un test escrito **antes** del refactor (detectó y corrigió dos suposiciones incorrectas del propio test contra el comportamiento real: `edad` depende de la fecha actual, `eliminado` serializa como entero `0`/`1`, no `true`/`false`) y vuelto a correr después, sin diferencias.
**Qué NO se hizo:** el "Paso B" (envelope común para los 8 endpoints de escritura) sigue sin autorizar — requiere verificar antes contra el código real de `CactuApp`.

**Pruebas (ambos cambios):** suite completa (`php artisan test`, 51 tests) en verde.

## 2026-08-08 — Fase 5: ARQ-006 Paso B verificado contra CactuApp real — no recomendado

**Fase:** Fase 5 — API + React Native.
**Motivo:** el usuario autorizó avanzar con el "Paso B" de `ARQ-006`. Antes de proponer cualquier código, tocaba la verificación que `docs/auditoria/evaluacion-api-resources.md` dejó explícitamente pendiente: abrir `/home/persontech/workspace/CactuApp` (rama `main`) y confirmar si alguna pantalla depende de claves/strings exactos de las respuestas de los 8 endpoints de escritura.
**Verificación realizada (solo lectura, ningún archivo de `CactuApp` fue modificado):** se leyeron `Login.js`, `Contestacion.js`, `Agradecimiento.js`, `Iniciada.js`, `PresentacionMayores.js`, `PresentacionMenores.js`, `DescargarEliminar.js`. Hallazgos: (1) el texto de los mensajes nunca se compara, solo se muestra en un toast — seguro de cambiar; (2) las claves `error`/`info`/`success` sí son estructurales — cada pantalla las revisa como ramas independientes con efectos distintos (navegación vs. solo toast), y 3 de las 6 pantallas ni siquiera revisan `error`; (3) `Login.js` compara `data.message === 'ok'` literal para decidir si autenticar.
**Conclusión:** un envelope común para los 8 endpoints de escritura **no es seguro como cambio de solo-backend** — rompería la distinción de comportamiento (`success` navega, `info` a veces navega hacia atrás) que el cliente ya implementa. Requeriría coordinar un release doble (backend + móvil), no una tarea de una sola sesión. **No se implementa.**
**Hallazgo adicional detectado en el camino (no buscado a propósito):** `PresentacionMayores.js`, `PresentacionMenores.js` y `DescargarEliminar.js` nunca revisan `result.error` — un `403` en esos 3 endpoints falla en silencio en la app. Es un bug preexistente del lado móvil (no introducido por nada de esta fase backend). Registrado como `#18` en `docs/movil/cambios-requeridos.md`, con corrección propuesta (solo móvil, 3 líneas, no requiere tocar este repositorio). También se corrigió una afirmación imprecisa en `docs/movil/manejo-errores.md` que generalizaba "el 403 está manejado correctamente" sin haber verificado las 6 pantallas individualmente.
**Archivos:** `docs/auditoria/evaluacion-api-resources.md`, `docs/auditoria/problemas-detectados.md`, `docs/movil/manejo-errores.md`, `docs/movil/cambios-requeridos.md`. Ningún archivo de código de `cactu-appweb` ni de `CactuApp` fue modificado.
**Impacto Web/API:** ninguno. **Impacto React Native:** ninguno directo (documentación); el hallazgo `#18` es una corrección pendiente de autorizar en el repositorio móvil.
**Pruebas:** no aplica (sin cambios de código).

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>

---

## 2026-08-08 — Fase 6, paso 1: infraestructura de colas real (PERF-001)

**Fase:** Fase 6 — PDF + Email + Jobs.
**Motivo:** primer paso de Fase 6, autorizado explícitamente por el usuario tras presentarle 4 opciones concretas de por dónde empezar (ver `docs/cambios/decisiones-arquitectura.md`). Se eligió infraestructura de colas por ser el ítem de menor riesgo (no toca `config/snappy.php`, núcleo de generación de PDF) y 100% verificable localmente.

**Verificación previa contra el código real (sin asumir nada, `CLAUDE.md` sección 3):** se confirmó que `config/snappy.php` apunta a un binario Windows (bug real, `PDF-001`, sin tocar aún), que ambas notificaciones importaban `ShouldQueue` sin implementarla, que no existía migración para la tabla `jobs`, que `app/Console/Kernel.php::schedule()` estaba vacío, y que `storage/app/temp` sí se limpia por request (corrección a una afirmación previa de `infraestructura-actual.md`).

**Cambio:**
- `EnviarCartaRespondidaGestor` y `EnviarNotificacionCartaNueva` ahora implementan `ShouldQueue`.
- Se publica y aplica la migración `database/migrations/2026_08_08_170139_create_jobs_table.php` (`php artisan queue:table`).
- `.env.example`: `QUEUE_CONNECTION` pasa de `sync` a `database`.
- `app/Console/Kernel.php`: se registra `queue:work --stop-when-empty` cada minuto, `withoutOverlapping()`.

**Archivos:** `app/Notifications/EnviarCartaRespondidaGestor.php`, `app/Notifications/EnviarNotificacionCartaNueva.php`, `app/Console/Kernel.php`, `.env.example`, `database/migrations/2026_08_08_170139_create_jobs_table.php` (nuevo), `tests/Unit/Notifications/NotificacionesShouldQueueTest.php` (nuevo), `docs/auditoria/infraestructura-actual.md`, `docs/auditoria/problemas-detectados.md`, `docs/cambios/decisiones-arquitectura.md`.
**Impacto Web/API:** ninguno sobre comportamiento observable hoy — `phpunit.xml` fuerza `QUEUE_CONNECTION=sync` en tests, y el `.env` local de desarrollo se deja igual a propósito. En producción, una vez que el `.env` real adopte `database` y se confirme la entrada de Cron (pendiente, no auditable desde este repositorio), el correo/Firebase dejará de bloquear la respuesta HTTP.
**Impacto React Native:** ninguno — cambio interno de infraestructura, sin tocar ningún endpoint ni contrato de API.
**Qué NO se hizo:** no se tocó `config/snappy.php` (`PDF-001`, bloqueado por falta de verificación contra producción real) ni se movió la generación de PDF a un Job — quedan como próximos pasos de Fase 6.
**Pruebas:** `tests/Unit/Notifications/NotificacionesShouldQueueTest.php` (nuevo). Suite completa: 53/53 tests en verde.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>

---

## 2026-08-08 — Fase 6, paso 2: PDF-001 corregido (config/snappy.php)

**Fase:** Fase 6 — PDF + Email + Jobs.
**Motivo:** siguiente paso de Fase 6 tras cerrar PERF-001. El usuario confirmó, con acceso File Manager de cPanel (sin SSH), el contenido real de `config/snappy.php` y `.env` en producción, y que la generación de PDF funciona hoy en `cactu.ec` — la verificación que este hallazgo tenía pendiente desde la Fase 0.

**Hallazgo confirmado:** producción tiene un `config/snappy.php` editado manualmente en el servidor (línea `'binary' => base_path('vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64')` activa) que **nunca se commiteó** — el repo seguía con la ruta de Windows hardcodeada. Sin este hallazgo, un futuro deploy desde git habría sobreescrito la configuración funcional de producción con la rota.

**Cambio:** `config/snappy.php` — se activa `'binary' => base_path('vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64')` en la sección `pdf` (igual a lo verificado en producción) y `base_path('vendor/h4cc/wkhtmltoimage-amd64/bin/wkhtmltoimage-amd64')` en `image` (mismo bug, código no usado hoy en la app, corregido por consistencia).
**Archivos:** `config/snappy.php`, `docs/auditoria/infraestructura-actual.md`, `docs/auditoria/problemas-detectados.md`, `docs/cambios/decisiones-arquitectura.md`.
**Impacto Web/API:** ninguno sobre producción (el repo ahora coincide con lo que ya corría ahí). En local, corrige la generación de PDF (antes rota por la ruta de Windows inexistente en este entorno).
**Impacto React Native:** ninguno — `config/snappy.php` no es parte del contrato de API.
**Qué NO se hizo:** no se usó `env('WKHTMLTOPDF_BINARY', ...)` como sugería la ficha original de `PDF-001` — no había ninguna variable de entorno real que lo necesitara, así que agregarla habría sido una capa de configuración sin uso (`CLAUDE.md` sección 56). Tampoco se movió la generación de PDF a un Job — al revisar el código real, los 3 usos de `PDF::loadView` son endpoints GET donde la respuesta HTTP es el PDF mismo (vista inline/descarga), no hay nada job-ificable ahí; se corrige esa parte del roadmap en `docs/auditoria/problemas-detectados.md`.
**Pruebas:** verificación manual vía `php artisan tinker` (`PDF::loadHTML(...)->output()` generó un PDF real, `Creator: wkhtmltopdf 0.12.4` en los metadatos). `tests/Feature/Seguridad/DescargaPdfCartaTest` en verde. Suite completa: 53/53 tests en verde.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>

---

## 2026-08-08 — Fase 6, paso 3: limpieza programada de storage/app/temp (INFRA-001) — Fase 6 cerrada

**Fase:** Fase 6 — PDF + Email + Jobs.
**Motivo:** último ítem pendiente de Fase 6. `storage/app/temp` ya se limpia por request en el flujo normal, pero sin ninguna tarea periódica que cubra el caso de un proceso PHP muerto a mitad de camino (cliente desconectado, timeout, OOM) — riesgo de acumulación silenciosa en un disco de cuota limitada (cPanel) compartido con logs/caché/sesiones.

**Cambio:** se crea `app/Console/Commands/LimpiarArchivosTemporalesCommand.php` (`php artisan temp:limpiar {--horas=24}`), que borra de `storage/app/temp` los archivos más viejos que el umbral (24h por defecto). Se registra en `app/Console/Kernel.php` vía `$schedule->command('temp:limpiar')->daily()->withoutOverlapping()` — reutiliza el mismo cron de `schedule:run` ya requerido para las colas, sin necesitar una segunda entrada en cPanel.
**Archivos:** `app/Console/Commands/LimpiarArchivosTemporalesCommand.php` (nuevo), `app/Console/Kernel.php`, `tests/Feature/Console/LimpiarArchivosTemporalesCommandTest.php` (nuevo), `docs/auditoria/infraestructura-actual.md`, `docs/auditoria/problemas-detectados.md` (nueva ficha `INFRA-001`), `docs/cambios/decisiones-arquitectura.md`.
**Impacto Web/API:** ninguno sobre comportamiento observable de las respuestas de carta.
**Impacto React Native:** ninguno.
**Qué NO se hizo:** no se atacó la causa raíz de por qué el `finally` puede no correr (por ejemplo, `ignore_user_abort(true)`) — cambiar ese comportamiento global de PHP tiene otras implicancias (workers de PHP-FPM ocupados por clientes ya desconectados) fuera del alcance de este hallazgo.
**Pruebas:** `tests/Feature/Console/LimpiarArchivosTemporalesCommandTest.php` (nuevo, 2 casos: borra solo lo viejo, no falla si la carpeta no existe). Suite completa: 55/55 tests en verde.

**Con esto, Fase 6 (PDF + Email + Jobs) queda cerrada en su alcance real:** `PERF-001` resuelto, `PDF-001` resuelto, `INFRA-001` resuelto. El ítem "mover generación de PDF a un Job" del roadmap original se descartó por no aplicar al código real (ver `PERF-001` en `problemas-detectados.md`).

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>

---

## 2026-08-08 — Fase 7, paso 1: endpoint de token FCM + corrección de pérdida silenciosa (SEG-014)

**Fase:** Fase 7 — Firebase + Notificaciones.
**Motivo:** antes de autorizar el resto de Fase 7 (tabla de dispositivos, reintentos), se auditó el estado real de sus 3 puntos contra el código. Se confirmó que el login era el único punto de escritura de `fcm_token` y, al hacerlo, se detectó un bug no documentado: un login sin `fcm_token` en el body borraba el token existente (`$ninio->fcm_token = $request->fcm_token` sin condición ni validación). El usuario autorizó atacar primero este punto (arreglo puntual, bajo riesgo) antes de decidir sobre la migración completa a tabla de dispositivos.

**Cambio 1 — SEG-014:** en el closure `POST /login` (`routes/api.php`), `fcm_token` ahora se valida (`nullable|string`) y solo se sobrescribe cuando el request lo trae (`$request->filled('fcm_token')`); si no viene, se conserva el valor guardado.
**Cambio 2 — endpoints nuevos:** `POST /actualizar-token-fcm` y `POST /eliminar-token-fcm` (dentro del grupo `auth:sanctum`), resuelven el punto 3 del roadmap de Fase 7 ("endpoint explícito para actualizar/eliminar el token FCM fuera del login"). Ambos actúan sobre `$request->user()` (el niño resuelto por el token Sanctum), nunca por un id enviado por el cliente — mismo patrón de autorización que `SEG-004`/`SEG-005`.
**Archivos:** `routes/api.php`, `app/Http/Requests/ActualizarTokenFcmRequest.php` (nuevo), `app/Http/Controllers/Api/TokenFcmController.php` (nuevo), `docs/auditoria/problemas-detectados.md` (nueva ficha `SEG-014`), `docs/fases/roadmap.md`, `docs/api/autenticacion.md`, `docs/movil/firebase.md`, `docs/movil/api.md`, `docs/movil/cambios-requeridos.md`.
**Impacto Web:** ninguno. **Impacto API:** aditivo — no cambia la forma de ninguna respuesta existente, solo agrega 2 rutas nuevas (disponibles también bajo `/api/v1/` por el alias de `ARQ-005`). **Impacto React Native:** ninguno inmediato — `CactuApp` no consume los endpoints nuevos todavía; integrarlos (`onTokenRefresh`, `signOut`) es trabajo pendiente del repositorio móvil, registrado en `docs/movil/cambios-requeridos.md` (#10/#11).
**Qué NO se hizo:** no se migró `Ninio.fcm_token` a una tabla de dispositivos (punto 1 del roadmap de Fase 7) ni se agregó manejo de tokens inválidos/reintentos en el envío Firebase (punto 2) — quedan pendientes de autorización explícita, son de mayor alcance (la migración cambia schema y requiere coordinar con el equipo móvil).
**Pruebas:** `tests/Feature/Seguridad/LoginTest.php` (2 casos nuevos) y `tests/Feature/Api/TokenFcmTest.php` (nuevo, 6 casos: requiere auth, valida el campo, actualiza, elimina, aísla por niño). Suite completa: 63/63 tests en verde.

---

## 2026-08-08 — Fase 7 (cierre parcial): migración a FCM HTTP v1 (`FIREBASE-001`) — tokens inválidos y reintentos resueltos

**Fase:** Fase 7 — Firebase + Notificaciones.
**Motivo:** el usuario autorizó continuar con los 2 puntos pendientes de Fase 7 (manejo de tokens inválidos/reintentos y tabla de dispositivos). Al investigar el código real antes de escribir el `try/catch` de "reintentos", se encontró que el envío usaba la API legacy de FCM (`fcm.googleapis.com/fcm/send`, paquete `kutia-software-company/larafirebase`), **decomisionada por Google desde 2024** (confirmado contra la documentación oficial de Firebase, no asumido). Muy probablemente las notificaciones push de "Nueva carta" llevaban ~2 años sin llegar a ningún niño, sin ningún error visible porque no había manejo de excepciones alrededor del envío. Se presentó el hallazgo (`FIREBASE-001`) al usuario antes de seguir, y se autorizó migrar directamente a FCM HTTP v1.

**Cambio:** se retira `kutia-software-company/larafirebase` y se instala `kreait/laravel-firebase` (^5.10), que usa FCM HTTP v1 con credenciales de cuenta de servicio (`FIREBASE_CREDENTIALS`) en vez de la server key legacy. Se crea `app/Services/FirebaseService.php` (primer Service del proyecto, ver `CLAUDE.md` sección 15) que envía el mensaje y captura `Kreait\Firebase\Exception\Messaging\NotFound` — la excepción específica que el SDK lanza cuando el token ya no está registrado — limpiando `Ninio::fcm_token` sin relanzar. Se crea `app/Notifications/Channels/FirebaseChannel.php`, un canal de notificación propio que reemplaza al que aportaba el paquete retirado, registrado en `AppServiceProvider::boot()`. `EnviarNotificacionCartaNueva` declara `$tries = 3` y `$backoff = 30` (Laravel las propaga automáticamente al job `SendQueuedNotifications` que envuelve toda `ShouldQueue` Notification) — cualquier error que `FirebaseService` no reconozca como "token inválido" se relanza y activa ese reintento normal de la cola.

**Por qué esto resuelve los 2 puntos pendientes de Fase 7 con un solo cambio:** el punto "manejo de tokens inválidos y reintentos" no era separable de la migración — construir reintentos sobre un endpoint decomisionado no tenía sentido. Al migrar a HTTP v1 (que sí distingue errores por tipo, vía excepciones tipadas), el manejo de tokens inválidos y los reintentos salieron como consecuencia directa del mismo cambio.

**Archivos:** `app/Services/FirebaseService.php` (nuevo), `app/Notifications/Channels/FirebaseChannel.php` (nuevo), `app/Providers/AppServiceProvider.php`, `app/Notifications/EnviarNotificacionCartaNueva.php`, `app/Http/Controllers/HomeController.php` (limpieza de import muerto de `Kutia\Larafirebase`), `config/firebase.php` (nuevo), `config/larafirebase.php` (eliminado), `.env.example`, `.env` (local, no versionado), `.gitignore`, `composer.json`/`composer.lock`, `docs/auditoria/problemas-detectados.md` (ficha `FIREBASE-001`), `docs/fases/roadmap.md`, `docs/movil/firebase.md`.
**Impacto Web:** ninguno. **Impacto API:** ninguno (no es un endpoint HTTP de CACTU). **Impacto React Native:** ninguno en el contrato — el SDK `@react-native-firebase/messaging` del móvil sigue generando tokens FCM estándar, compatibles con HTTP v1; es un cambio puramente de cómo envía el backend, no de qué recibe el dispositivo.
**Qué NO se hizo:** no se migró `Ninio.fcm_token` a una tabla de dispositivos (único punto que queda abierto en Fase 7) — cambia schema y requiere coordinar con el equipo móvil, así que queda para decidirse por separado.
**Pruebas:** `tests/Feature/Notifications/EnviarNotificacionFirebaseTest.php` (nuevo, 5 casos: envío correcto, sin token no intenta enviar, token no registrado se limpia sin relanzar, error transitorio se propaga sin tocar el token, `$tries`/`$backoff` configurados), mockeando `Kreait\Firebase\Contract\Messaging` directamente en el contenedor — sin depender de credenciales reales. Suite completa: 68/68 tests en verde.

**Verificación end-to-end contra Firebase real (mismo día, entorno local):** el usuario cargó sus credenciales reales de cuenta de servicio (`storage/app/firebase/service-account.json`, no versionado). Se confirmó autenticación real con `Messaging::validate()` (`validate_only`, sin entregar nada) contra un token inventado y contra un token real pero desactualizado (`NotFound`, como se esperaba). Luego, con un `fcm_token` fresco obtenido logueándose desde `CactuApp` real contra el backend local, se hizo un envío real (no `validate_only`) vía `FirebaseService::enviarNotificacion()` — **el usuario confirmó haber recibido la notificación de prueba en el teléfono.** Migración verificada de punta a punta, no solo "lista para". Detalle completo en `docs/auditoria/problemas-detectados.md` (`FIREBASE-001`).

---

## 2026-08-08 — Fase 7 (cierre): multi-dispositivo por niño (`FIREBASE-002`) — Fase 7 cerrada

**Fase:** Fase 7 — Firebase + Notificaciones.
**Motivo:** único punto pendiente de Fase 7. Antes de diseñar nada se confirmó con el usuario si el negocio realmente necesitaba multi-dispositivo (no era un pedido explícito, era el modelo objetivo aspiracional de `CLAUDE.md` sección 25) y se inspeccionó `CactuApp` para no asumir capacidades del móvil que no existen. El usuario confirmó que sí hace falta. Al diseñar la migración de datos se encontró evidencia concreta del caso de negocio: 144 grupos de `fcm_token` compartidos entre 843 `Ninio` (hermanos patrocinados usando un mismo teléfono familiar).

**Cambio:** nueva tabla `dispositivos` (`ninio_id` FK cascade, `fcm_token` único, timestamps) — el token FCM mismo es la clave del dispositivo, sin inventar un `device_id` que `CactuApp` no provee. `Ninio::dispositivos()` (hasMany). `App\Actions\RegistrarDispositivoAction::registrar()` centraliza el upsert por token (usado en login y `POST /actualizar-token-fcm`), reasignando el dispositivo si el mismo teléfono ya estaba asociado a otro niño. `POST /eliminar-token-fcm` cambia de contrato: ahora exige `fcm_token` en el body para identificar qué dispositivo puntual borrar. `FirebaseService::enviarNotificacion` itera todos los dispositivos del niño; un token rechazado por Firebase (`NotFound`) borra solo ese dispositivo. `CartaController::enviarNotificacion` y `EnviarNotificacionCartaNueva::via()` chequean `$ninio->dispositivos()->exists()` en vez de la columna vieja.
**Migración de datos:** 2138 `Ninio` con `fcm_token` no nulo → 1439 dispositivos creados (valores distintos de token; el criterio de desempate en duplicados es el `updated_at` más reciente, replicando el comportamiento "último login gana" que el sistema ya tenía). Verificado en tinker antes y después de escribir código de negocio nuevo.
**Columna vieja:** `ninios.fcm_token` se deprecia (deja de leerse/escribirse desde código nuevo, comentario de deprecación en `Ninio::$fillable`) pero **no se dropea** en esta rama — decisión deliberada dado el entorno de producción (cPanel, deploy manual, sin blue/green). El drop queda para una migración separada, con autorización explícita nueva. Detalle completo de esta y las demás decisiones en `docs/cambios/decisiones-arquitectura.md`.
**Archivos:** `database/migrations/2026_08_08_192218_create_dispositivos_table.php` (nuevo), `app/Models/Dispositivo.php` (nuevo), `app/Models/Ninio.php`, `app/Actions/RegistrarDispositivoAction.php` (nuevo), `routes/api.php`, `app/Http/Controllers/Api/TokenFcmController.php`, `app/Http/Requests/EliminarTokenFcmRequest.php` (nuevo), `app/Services/FirebaseService.php`, `app/Http/Controllers/CartaController.php`, `app/Notifications/EnviarNotificacionCartaNueva.php`, `docs/auditoria/problemas-detectados.md` (nueva ficha `FIREBASE-002`), `docs/cambios/decisiones-arquitectura.md`, `docs/fases/roadmap.md`, `docs/movil/firebase.md`, `docs/movil/cambios-requeridos.md`, `docs/api/autenticacion.md`.
**Impacto Web:** ninguno. **Impacto API:** `GET /user` y `POST /login` sin cambios de forma en su respuesta. `POST /eliminar-token-fcm` cambia de contrato (ahora exige `fcm_token` en el body), sin impacto real porque no tiene consumidor móvil todavía. **Impacto React Native:** ninguno inmediato — conectar `CactuApp` a este modelo (enviar el token consistentemente, llamar `eliminar-token-fcm` en un futuro logout) sigue siendo trabajo pendiente del repositorio móvil.
**Qué NO se hizo:** no se dropeó la columna vieja `ninios.fcm_token` (ver arriba). No se agregaron columnas de metadata de dispositivo (`plataforma`, `nombre`, `last_seen_at`) — `CactuApp` no manda nada de eso hoy y no había caso de uso concreto (`CLAUDE.md` sección 56, YAGNI); `updated_at` ya cumple el rol de "última vez visto". No se tocó el repositorio móvil.
**Pruebas:** `tests/Feature/Seguridad/LoginTest.php` (caso nuevo: reasignación de dispositivo entre hermanos que comparten teléfono), `tests/Feature/Api/TokenFcmTest.php` (reescrito: no duplica dispositivo, reasigna de otro niño, exige el campo en eliminar, aísla dispositivos del mismo niño), `tests/Feature/Notifications/EnviarNotificacionFirebaseTest.php` (reescrito: envía a cada dispositivo, un token inválido no afecta a los demás del mismo niño). Suite completa: 75/75 tests en verde.

**Con esto, Fase 7 (Firebase + Notificaciones) queda cerrada en su alcance real:** `SEG-014` resuelto, `FIREBASE-001` resuelto y verificado end-to-end contra un dispositivo real, `FIREBASE-002` resuelto. Pendiente fuera de este repositorio: cargar credenciales de Firebase en producción (`FIREBASE-001`) y el trabajo del lado móvil listado en `docs/movil/cambios-requeridos.md` (#10-#12).

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>

---

## 2026-08-08 — Fase 3 (inicio), UX/UI: auditoría del frontend actual y cierre de `UX-002`

**Fase:** Fase 3 — UX/UI.
**Motivo:** el usuario autorizó comenzar la Fase 3 del roadmap. Antes de tocar cualquier vista, `CLAUDE.md` sección 3/5 exige auditar el estado real del frontend sin asumir nada de documentación previa.

**Auditoría:** se creó `docs/auditoria/ux-ui-actual.md`, leyendo el código real (no la documentación vieja) de layouts, `sections/`, los 6 módulos de vistas (cartas, ninios, usuarios, comunidad, mis-ninios, responder-cartas), las librerías CSS/JS realmente cargadas en el navegador, y el estado de `UX-001` (plantillas PDF v1/v2). Hallazgo principal: **CACTU ya usa el template Limitless** como base del panel administrativo desde el primer commit (`d176b8a`) — no es una migración pendiente. El pipeline de Vite declarado en `package.json` está muerto en la práctica (`@vite()` comentado en ambos layouts). Se registraron 4 hallazgos nuevos en `docs/auditoria/problemas-detectados.md` (`UX-002` a `UX-005`) y se precisó `UX-001` con evidencia de código: la plantilla "v2" no está conectada a ningún flujo de producción real (ni la descarga oficial ni el enlace público que reciben los patrocinadores la usan), solo se ve en una comparación interna lado a lado en `cartas/show.blade.php`, y contiene al menos un archivo huérfano nunca referenciado.

**Cambio implementado — `UX-002`:** se extrae el `<head>` común (metas, hojas de estilo, scripts core, configuración global de `jquery-validate`/`jquery-confirm`/CSRF) a `resources/views/sections/head.blade.php`, incluido ahora por los dos layouts que existen en el sistema (`layouts/app.blade.php`, el panel administrativo, y `responder-cartas/principal.blade.php`, el flujo público sin autenticación) en vez de duplicar ~90 líneas en cada uno. Cada layout conserva su propio `<body>` sin cambios (sidebar/navbar en el panel, tarjeta centrada en el flujo público) — el objetivo fue eliminar la duplicación, no rediseñar nada.

**Único cambio de comportamiento:** `responder-cartas/principal.blade.php` cargaba jQuery desde un CDN público (`code.jquery.com`); ahora usa el mismo archivo local que el panel administrativo (`assets/js/jquery/jquery.min.js`), eliminando una dependencia de red externa evitable en el único flujo verdaderamente público del sistema.

**Archivos:** `resources/views/sections/head.blade.php` (nuevo), `resources/views/layouts/app.blade.php`, `resources/views/responder-cartas/principal.blade.php`, `docs/auditoria/ux-ui-actual.md` (nuevo), `docs/auditoria/problemas-detectados.md` (fichas `UX-001` actualizada, `UX-002` a `UX-005` nuevas), `docs/cambios/decisiones-arquitectura.md`.
**Impacto Web:** ninguno visible — mismo layout, mismo comportamiento, salvo la fuente de jQuery en el flujo público (ahora local en vez de CDN). **Impacto API:** ninguno. **Impacto React Native:** ninguno (son vistas Blade, no consumidas por `CactuApp`).
**Pruebas:** compilación de todas las vistas Blade del sistema sin errores (`php artisan view:cache`); verificación HTTP real contra el servidor local (`php artisan serve`) de ambos layouts — panel (`/login`, confirmando `jquery.min.js` local en la respuesta) y flujo público (`/cartas-ninio/{numero_child}`, confirmando `jquery.min.js` local y el mensaje de bienvenida del niño renderizando correctamente). No se pudo probar de forma interactiva en un navegador real (clicks, JS en ejecución) por no contar con una herramienta de automatización de navegador en este entorno — queda pendiente una verificación manual del usuario si lo considera necesario, especialmente el flujo de captura de foto por cámara en `responder-cartas`.
**Nota importante detectada durante la verificación (no causada por este cambio):** `php artisan test` reporta **16 fallos de 75** en este árbol de trabajo (`Tests\Feature\Cartas\RegistrarCartaTest`, `ResponderCartaWebTest`, `Tests\Feature\Seguridad\CartaAutorizacionTest`, `LoginTest`, `MensajesErrorTest`, `NiniosAutorizacionTest`, `UsuariosAutorizacionTest`), contradiciendo el "75/75 en verde" registrado al cierre de Fase 7. Se confirmó con `git stash` que **no los causan los cambios de este cambio** (fallan igual sin los 3 archivos tocados aquí) — son preexistentes en el estado actual del árbol de trabajo. No se investigó la causa raíz por estar fuera del alcance de UX/UI autorizado; queda como hallazgo a registrar y priorizar por separado (`TEST-002`).

---

## 2026-08-08 — Fase 3, UX/UI: cierre de `UX-001` — se retira la plantilla PDF "v2"

**Fase:** Fase 3 — UX/UI.
**Motivo:** el usuario decidió explícitamente retirar "v2" — el cliente no la aprobó. La auditoría (`docs/auditoria/ux-ui-actual.md` sección 6) ya había confirmado en código que v2 nunca estuvo conectada a ningún flujo de producción real, así que retirarla no cambia nada de lo que ven los patrocinadores o el equipo — solo elimina código sin uso y la comparación interna que ya no hace falta.

**Cambio:** se elimina `resources/views/cartas/v2/` completo (`verpdf.blade.php`, `pdf-contestacion.blade.php`, `pdf-agradecimiento.blade.php`, `pdf-presentacion.blade.php`, `pdf-presentacion-fixed.blade.php` [ya huérfano desde antes], `pdf-iniciada.blade.php`). `CartaController::verPDF()` pierde el parámetro `$version` y la rama condicional que elegía entre v1 y v2 — ahora siempre usa `cartas.verpdf`. La ruta `cartas-ver-carta-pdf/{id}/{version?}` se simplifica a `cartas-ver-carta-pdf/{id}` (el segmento opcional no tenía otro uso). `cartas/show.blade.php` pasa de dos columnas lado a lado (rotuladas "(v1)"/"(v2)") a una sola columna con el PDF, sin rótulo de versión.

**Archivos:** `resources/views/cartas/v2/*` (6 archivos eliminados), `app/Http/Controllers/CartaController.php`, `routes/web.php`, `resources/views/cartas/show.blade.php`, `docs/auditoria/problemas-detectados.md` (ficha `UX-001` cerrada), `docs/cambios/decisiones-arquitectura.md`.
**Impacto Web:** ninguno para el usuario final — la descarga oficial y el enlace público ya usaban v1 exclusivamente; el único cambio visible es que `cartas.show` (uso interno) ya no muestra la comparación v1/v2. **Impacto API:** ninguno. **Impacto React Native:** ninguno.
**Pruebas:** `php artisan view:cache` (todas las vistas Blade compilan sin error). Se creó una `Carta` de prueba temporal contra la base de datos local para verificar de punta a punta: `CartaController::verPDF()` invocado directamente devuelve `200`/`application/pdf`; `cartas.show` renderizado con esa carta confirma un solo iframe y ninguna referencia a "(v1)"/"(v2)" en el HTML resultante. La carta de prueba se eliminó al terminar. Suite completa: mismos 16 fallos preexistentes que antes de este cambio (`TEST-002`), sin fallos nuevos ni relacionados a `Carta`/PDF — confirmado comparando la lista de tests fallidos antes y después.
**Qué NO se hizo:** no se investigó ni se tocó `TEST-002` (los 16 tests que fallan) — es un hallazgo aparte, sin relación con las plantillas PDF.

---

## 2026-08-08 — Incidente crítico: `TEST-002` vaciaba la base de datos real en cada corrida de tests (resuelto, con pérdida y restauración de datos)

**Fase:** Transversal — incidente detectado durante Fase 3, resuelto de inmediato por su severidad (`CLAUDE.md` sección 44: pérdida de datos es prioridad máxima).

**Qué pasó:** al investigar la causa raíz de `TEST-002` (los 16 tests que fallaban) por pedido explícito del usuario, se encontró que `bootstrap/cache/config.php` (config cacheada, no trackeada en git, con fecha anterior al inicio de esta sesión) fijaba `APP_ENV=local` y `DB_CONNECTION=mysql` **ignorando por completo** las variables de `phpunit.xml` (`APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`). Como consecuencia, `php artisan test` corría contra la base de datos real de desarrollo (`cactu`), y el trait `RefreshDatabase` (usado por casi toda la suite) ejecutaba `migrate:fresh` — elimina y recrea todas las tablas — al inicio de cada proceso de test.

**Impacto:** cada vez que se corrió `php artisan test` durante esta sesión (varias veces, verificando `UX-002` y `UX-001`), se vació la base de datos real de desarrollo del usuario sin que nadie lo supiera. Al confirmarlo, la base ya estaba completamente vacía: 0 usuarios, 0 niños, 0 cartas, 0 comunidades, 0 dispositivos. El usuario preguntó explícitamente por qué había perdido datos; la primera respuesta (basada solo en revisar la carta de prueba creada/eliminada para `UX-001`) fue incompleta — la investigación de `TEST-002` reveló la causa real más tarde, en la misma sesión.

**Cómo se corrigió:**
1. `php artisan config:clear` — elimina la config cacheada obsoleta. Verificado que sin ella, `database.default` vuelve a resolver correctamente según el entorno (`sqlite` bajo variables de test, `mysql` en desarrollo normal). No fue necesario ningún cambio de código: el archivo no estaba trackeado en git, era un artefacto local de una sesión o prueba anterior (posiblemente relacionada con preparar el entorno de cPanel), nunca limpiado.
2. El usuario restauró su propio backup de la base `cactu`.
3. Se identificó que el backup, al ser anterior a hoy, no tenía las 2 migraciones de Fase 7 (`jobs`, `dispositivos`). Se indicó correr `php artisan migrate` (sin `:fresh`) — la migración de `dispositivos` incluye su propio backfill de datos (`migrarTokensExistentes()`, reconstruye desde `ninios.fcm_token`), así que no hizo falta ningún script adicional.
4. Verificado tras la restauración: 24 usuarios, 6527 niños, 6642 cartas, 28 comunidades, 2266 dispositivos; tabla `migrations` con 3 batches distintos (historial real e incremental, no aplanado por un `migrate:fresh`).

**Archivos:** ninguno de código — `bootstrap/cache/config.php` era un artefacto local no versionado; `docs/auditoria/problemas-detectados.md` (ficha `TEST-002` actualizada con severidad `CRÍTICO` y el incidente completo).
**Impacto Web/API/Móvil:** ninguno sobre el código o los contratos — el sistema en sí no cambió. El impacto fue exclusivamente sobre datos de la base de desarrollo local del usuario, restaurados por el propio usuario desde su backup.
**Qué NO se hizo:** no se determinó con certeza quién ni cuándo generó la config cacheada originalmente (es anterior al inicio de esta sesión) — se documenta como recomendación para el futuro, no como causa atribuible a un cambio de código de esta rama.
**Recomendación para que no se repita:** nunca dejar config cacheada (`php artisan config:cache`/`optimize`) en un entorno de desarrollo local donde también se corren tests — reservar el cacheo de config exclusivamente para un despliegue real a producción.
**Lección para esta sesión:** antes de correr `php artisan test` de nuevo, se verificará explícitamente que la suite esté apuntando a una base de datos aislada (no la real) — no se debe volver a asumir que `phpunit.xml` es la única fuente de verdad del entorno de test sin confirmarlo contra la config efectiva de la aplicación.

---

## 2026-08-08 — Fase 3, UX/UI: cierre de `UX-005` — flujo público de responder-cartas alineado al patrón del panel

**Fase:** Fase 3 — UX/UI.
**Motivo:** era el hallazgo de mayor impacto real de la auditoría (`docs/auditoria/ux-ui-actual.md` sección 5.4) — lo ven los niños/familias y patrocinadores, no el staff. Antes de tocar código se releyeron las 5 vistas completas (en la auditoría solo se había leído `agradecimineto.blade.php` en detalle), confirmando que las 5 comparten el mismo problema estructural: `<h1>`/`<p>` sueltos sin tarjeta, pares `<label>`+`<input class="form-control input ">` (la clase `input` no existe, resto muerto).

**Cambio:** `resources/views/responder-cartas/{agradecimineto,contestacion,iniciada,presentacion-mayores,presentacion-menores}.blade.php` se envuelven en `card`/`card-header`/`card-body`/`card-footer` (el mismo patrón de `ninios/create.blade.php` y el resto del panel: `<form>` envolviendo al `<div class="card">`, no al revés). Se quita la clase `input` muerta de todos los `form-control`. Cada campo pasa a `<div class="mb-3"><label class="form-label">...</label>...</div>` consistente, reemplazando la mezcla previa de `mb-2`/`md-2`/sin wrapper.

**Decisión de diseño — `form-label` en vez de `form-floating`:** el panel admin usa `form-floating` (etiqueta que "flota" dentro del campo), pero varias etiquetas de estos formularios públicos son oraciones completas ("Cuéntale por qué quisieras que te conteste tu patrocinador..."), no palabras cortas como "Email". `form-floating` está diseñado para lo segundo; forzarlo aquí habría desbordado texto o se habría visto mal. Se prioriza la herramienta de Bootstrap correcta para el contenido real, documentado en `docs/cambios/decisiones-arquitectura.md`.

**Cero cambios de comportamiento:** mismos `name`/`id` de cada campo (verificado campo por campo contra `app/Http/Requests/Cartas/ResponderCarta*Request.php` — el conjunto de nombres de cada vista coincide exactamente con las reglas de validación de su Request), mismas rutas de submit, mismo JS de captura de cámara (`jslib-html5-camera-photo`, incluida la doble cámara personal+familia de los dos formularios de Presentación), mismo `jquery.validate`/`jquery-confirm`. Se preservó intencionalmente el typo histórico `mifamila` (documentado en el propio `ResponderCartaPresentacionMenorRequest` como contrato compartido con `CactuApp`, no se toca sin coordinar con el móvil) y la diferencia real preexistente entre archivos: solo `contestacion.blade.php` tiene activo el iframe del PDF adjunto del patrocinador (en `agradecimineto`/`iniciada` sigue comentado, sin tocar ninguno de los dos estados).

**Archivos:** los 5 `resources/views/responder-cartas/*.blade.php` listados arriba, `docs/auditoria/problemas-detectados.md` (ficha `UX-005` cerrada), `docs/cambios/decisiones-arquitectura.md`.
**Impacto Web:** visual únicamente, en el flujo público — estructura en tarjetas en vez de texto suelto. **Impacto API:** ninguno (son vistas Blade del canal Web, no del canal API/móvil que usa `Api\ResponderCarta`). **Impacto React Native:** ninguno — `CactuApp` tiene su propia UI nativa para este flujo, no consume estas vistas Blade.
**Pruebas:** `php artisan view:cache` sin errores. Para cada uno de los 5 tipos de carta se comparó el conjunto de campos (`name=`) de la vista reescrita contra las reglas de su `FormRequest` — coincidencia exacta en los 5. Se renderizaron las 5 vistas directamente contra cartas reales ya existentes en la base (`Carta::where('tipo', ...)`, sin crear ni modificar ningún registro) — las 5 renderizan sin error, con la cantidad esperada de `form-label` por formulario (6, 5, 5, 21 y 25 respectivamente, coincidiendo con la cantidad de campos de cada `FormRequest`). Se hizo además una verificación HTTP de punta a punta contra un niño real con una carta de Agradecimiento pendiente (siguiendo el enlace cifrado real desde `/cartas-ninio/{numero_child}`, sin crear datos): la página responde 200, muestra 2 tarjetas, 6 `form-label`, jQuery servido local, y cero apariciones de la clase muerta `input`. Suite completa: 75/75 en verde. Base de datos real verificada sin cambios en ningún momento de esta verificación (solo lecturas).
**Qué NO se hizo:** no se tocó ninguna de las 5 vistas de tipo de carta más allá de su estructura visual — ningún nombre de campo, ninguna ruta, ningún JS de negocio. No se corrigió el typo `mifamila` (documentado como contrato con el móvil). No se activó el iframe del PDF en `agradecimineto`/`iniciada` (decisión de negocio ajena a esta tarea, se preservó el estado existente de cada archivo).

---

## 2026-08-08 — Fase 3, UX/UI: cierre de `UX-003` — primer Blade Component del proyecto

**Fase:** Fase 3 — UX/UI.
**Motivo:** el dropdown de "acciones por fila" (Editar/Eliminar) estaba duplicado casi carácter por carácter en 4 archivos (`ninios`, `usuarios`, `comunidad`, `mis-ninios`), sin ningún punto único de edición — confirmado en la auditoría (`docs/auditoria/ux-ui-actual.md` sección 5.2) y en que `app/View/Components` no existía en todo el proyecto.

**Cambio:** se crea `resources/views/components/tabla-acciones.blade.php` — componente Blade anónimo (`@props(['editRoute', 'deleteRoute', 'deleteMsg'])`), el primero de todo el proyecto. `ninios/action.blade.php`, `usuarios/action.blade.php`, `comunidad/action.blade.php` y `mis-ninios/action.blade.php` pasan de ~20 líneas de markup duplicado cada uno a `<x-tabla-acciones :edit-route="..." :delete-route="..." :delete-msg="..." />`.

**Qué queda fuera a propósito:** `cartas/action.blade.php` no se toca — tiene contenido genuinamente distinto (enlaces de Ver/Descargar PDF/Documentos, más un popover de información con ~150 líneas de CSS propio). Meterlo en el mismo componente habría requerido slots y props condicionales para un único caso de uso real, complejidad sin necesidad concreta (`CLAUDE.md` sección 56).

**Cero cambios de comportamiento:** las clases `DataTable` en PHP (`NinioDataTable`, `UserDataTable`, `ComunidadDataTable`) siguen invocando exactamente la misma vista con la misma variable (`view('ninios.action', ['n' => $n])`, etc.) — no se tocó ninguna de ellas. El HTML resultante es idéntico al anterior: mismas rutas, mismo `data-msg`, mismos íconos, mismo `onclick="eliminar(this)"`.

**Archivos:** `resources/views/components/tabla-acciones.blade.php` (nuevo), `resources/views/{ninios,usuarios,comunidad,mis-ninios}/action.blade.php`, `docs/auditoria/problemas-detectados.md` (ficha `UX-003` cerrada).
**Impacto Web:** ninguno visible — mismo HTML renderizado. **Impacto API:** ninguno. **Impacto React Native:** ninguno.
**Pruebas:** `php artisan view:cache` sin errores. Se renderizaron las 4 vistas directamente (`view(...)->render()`) contra datos reales existentes, confirmando HTML idéntico al patrón anterior. Suite completa: 75/75 en verde. Se detectó que el conteo de `cartas` subió en 1 fila entre una verificación y la siguiente (6642→6643); se confirmó que la config seguía sin cachear (`ls bootstrap/cache/config.php` → no existe) y que la carta nueva (id 10884, creada minutos antes) es tráfico real de un usuario del sistema en vivo (el backend corre con `php artisan serve` accesible por LAN para pruebas móviles) — no relacionado con esta sesión ni con ningún test.

---

## 2026-08-08 — Fase 3, UX/UI: cierre de `UX-004` — `mis-ninios` migrado a DataTable server-side

**Fase:** Fase 3 — UX/UI.
**Motivo:** único de los 5 listados del sistema (`cartas`, `ninios`, `usuarios`, `comunidad`, `mis-ninios`) que no usaba Yajra DataTables server-side — tenía una tabla HTML cruda, DataTables inicializado client-side en JS, y ~20 líneas del diccionario de idioma español hardcodeadas inline en vez de una configuración compartida (`docs/auditoria/ux-ui-actual.md` sección 5.1).

**Cambio:** se crea `app/DataTables/MisNinioDataTable.php`, calcado de `NinioDataTable.php` (mismas columnas, mismo `editColumn`/`filterColumn` de `comunidad_id`, reutiliza `<x-tabla-acciones>` de `UX-003` vía `mis-ninios/action.blade.php` sin tocarlo). El alcance por gestor autenticado —antes resuelto leyendo la relación `User::ninios()` (`HasManyThrough` vía `Comunidad`)— se replica en el método `query()` de la nueva clase con `Ninio::whereHas('comunidad', fn($q) => $q->where('user_id', Auth::id()))`. `MisNiniosController::index()` pasa de armar la vista a mano (`view('mis-ninios.index', ['ninios' => Auth::user()->ninios])`) a `$dataTable->render('mis-ninios.index')`, igual que `NinioController::index()`. `mis-ninios/index.blade.php` queda estructuralmente idéntica a `ninios/index.blade.php`.

**Verificación de que el alcance por gestor no cambió:** antes de dar por buena la migración, se comparó para un gestor real el conjunto de IDs de niños que devolvía la relación vieja (`User::ninios()`) contra el que devuelve la nueva `query()` — coincidencia exacta, 353 niños en ambos casos. Es el paso más sensible del cambio (una regla de autorización por comunidad, no solo una vista), así que se verificó con datos reales antes de continuar.

**Archivos:** `app/DataTables/MisNinioDataTable.php` (nuevo), `app/Http/Controllers/MisNiniosController.php`, `resources/views/mis-ninios/index.blade.php`, `docs/auditoria/problemas-detectados.md` (ficha `UX-004` cerrada).
**Impacto Web:** visual mínimo (mismo look final, ahora paginado/buscable server-side como el resto del sistema en vez de cargar todos los niños del gestor de una vez en el HTML). **Impacto API:** ninguno. **Impacto React Native:** ninguno.
**Pruebas:** comparación directa del alcance de datos (353/353 IDs idénticos, ver arriba). Simulación de la petición AJAX real que dispara el JS de DataTables (`recordsTotal: 353`, paginación con `length=5` devolviendo 5 filas, nombre de comunidad resuelto vía `editColumn`, columna de acciones con las rutas de `mis-ninios` correctas). Render completo de la página sin errores. `php artisan view:cache` sin errores. Suite completa: 75/75 en verde. Base real sin cambios propios en ningún momento (solo lecturas y simulaciones de request en memoria).
**Qué NO se hizo:** no se agregó la columna `email` (que sí tiene `ninios/index.blade.php` en el panel admin) — el listado original de `mis-ninios` nunca la mostró, y no se pidió agregarla; se mantiene el mismo conjunto de columnas que ya tenía.

**Con este cambio, los 3 hallazgos abiertos de Fase 3 (`UX-003`, `UX-004`) quedan resueltos junto con `UX-001`, `UX-002`, `UX-005` — el núcleo de la Fase 3 identificado en la auditoría está completo.**

---

## 2026-08-08 — Seguridad: cierre de `SEG-015` — ingreso protegido en responder-cartas web + fix del 404 al responder

**Fase:** Transversal, seguridad. Fuera de Fase 3, pedido explícito del usuario mientras se conversaba sobre próximos pasos de UX/UI: "si algún otro usuario tiene ese enlace puede responder intencionalmente sería bueno al menos proteger con el id del niño como se hace en la app móvil".

**Investigación (antes de tocar código):** se confirmó que `ResponderCartaController::index()` recibía `numero_child` **en texto plano** como parámetro de URL (`/cartas-ninio/{numero_child}`), sin cifrar (había una línea de descifrado comentada en el propio código) y sin ningún límite de intentos. Se verificó contra datos reales que los valores no son de alta entropía (secuenciales/estructurados). Se investigó también el login de la app móvil (`POST /api/login`) para comparar, y se encontró que **usa la misma credencial débil** (`numero_child` sin contraseña ni PIN) — ya documentado como `SEG-012` (informativo, decisión de negocio, fuera de este alcance). La diferencia real no era la fortaleza de la credencial sino la exposición: la web la dejaba embebida en una URL persistente y compartible; la app la pide una vez por sesión sin dejar rastro visible. Se presentó el hallazgo completo al usuario, quien autorizó la mitigación más completa de las tres opciones planteadas (rate limiting + sesión con expiración), sin tocar la decisión de negocio de `SEG-012`.

**Bug funcional encontrado en el camino (no buscado):** los 10 enlaces "Regresar a mis cartas"/"Cancelar" de las 5 vistas de respuesta, y el redirect de éxito de `procesarRespuesta()`, generaban `route('cartas-ninio.index', Crypt::encryptString($ninio->numero_child))` — pasaban un valor cifrado a una ruta que esperaba el valor plano. Verificado con una petición HTTP real contra un niño real: 404 seguro. **Toda familia que respondía una carta por web caía en un error en vez de ver la confirmación de envío.** Se arregló en el mismo cambio, al ser el mismo método/flujo.

**Cambio:**
1. `RateLimiter::for('numero-child', ...)` (`RouteServiceProvider`, 5 intentos/minuto por IP) — aplicado a `POST /cartas-ninio` (nuevo) y a `POST /login` de la API (mismo problema de fondo).
2. `index()` deja de aceptar `numero_child` como parámetro de URL. Flujo nuevo: `GET /cartas-ninio` (formulario, `mostrarFormularioIngreso()`) → `POST /cartas-ninio` (`ingresar()`, valida y abre una ventana de sesión de 45 min — no la sesión HTTP completa, una ventana propia más corta) → `GET /cartas-ninio/mis-cartas` (`index()`, lee el niño de esa ventana).
3. `ver()` y el redirect final de `procesarRespuesta()` abren/renuevan la misma ventana de sesión al validar la identidad (ambos ya prueban posesión del `numero_child` cifrado) — así que entrar por un link directo de notificación push también deja navegar "mis cartas" sin volver a pedir el número, y de paso el redirect de éxito ya no lleva el parámetro roto.
4. `responder-cartas/principal.blade.php` ahora renderiza sin `$ninio` (la pantalla de ingreso todavía no sabe quién es el niño) — se agregó un `@isset($ninio)` en el encabezado, mostrando "Cartas de CACTU" genérico en su lugar.

**Archivos:** `app/Providers/RouteServiceProvider.php`, `routes/api.php`, `routes/web.php`, `app/Http/Controllers/ResponderCartaController.php`, `resources/views/responder-cartas/ingresar.blade.php` (nuevo), `resources/views/responder-cartas/principal.blade.php`, las 5 vistas de tipo de carta (`agradecimineto`, `contestacion`, `iniciada`, `presentacion-mayores`, `presentacion-menores`), `tests/Feature/Seguridad/ResponderCartaSesionTest.php` (nuevo, 7 casos), `tests/Feature/Cartas/ResponderCartaWebTest.php` (aserción del redirect ampliada), `docs/auditoria/problemas-detectados.md` (ficha `SEG-015` nueva).

**Impacto Web:** el flujo público gana un paso (ingresar el número una vez) antes de ver las cartas — a cambio, deja de haber una URL de por vida con el número embebido, y se arregla el 404 posterior a responder. **Impacto API:** `POST /login` gana rate limiting (5/min por IP); sin cambio de contrato. **Impacto React Native:** ninguno esperado — `CactuApp` no debería acercarse al límite de 5 intentos/minuto en uso normal.

**Pruebas:** verificación HTTP real de punta a punta contra el servidor local con cookies reales — formulario de ingreso, apertura de sesión, listado de cartas de un niño real, apertura de una carta específica, y rate limiting (confirmado 429 al superar 5 intentos/min desde la misma IP). El paso de "responder y ver la confirmación" no se pudo completar por HTTP real porque el entorno sandbox no tiene salida SMTP hacia `mail.cactu.ec` (limitación de este entorno, no del código — se confirmó que la carta de prueba usada no quedó guardada a medias tras el error de correo). Se verificó en su lugar con `tests/Feature/Seguridad/ResponderCartaSesionTest.php` (7 casos nuevos) y una aserción ampliada en `ResponderCartaWebTest.php` que sigue el redirect completo hasta `200` con `Notification::fake()`. Suite completa: 82/82 en verde. Toda la data de prueba creada durante la verificación (niños, cartas) se eliminó al terminar; la base real quedó sin cambios propios.

**Qué NO se hizo:** no se tocó `SEG-012` — si `numero_child` debe dejar de ser la única credencial (agregar fecha de nacimiento, PIN, etc.) sigue siendo una decisión de negocio pendiente, explícitamente fuera de este alcance. No se agregó throttle a `ver()`/`verArchivo()`/`guardarX()` — ya requieren un token cifrado no adivinable (protección distinta, ya adecuada). No se cambió nada del canal API/móvil salvo el rate limit de `/login` — `CactuApp` no se tocó.

---

## 2026-08-08 — Seguridad: `SEG-015` (parte 2) — el link cifrado directo ya no alcanza por sí solo

**Fase:** Transversal, seguridad. Ampliación pedida por el usuario tras el cierre inicial de `SEG-015`: "el problema en la web es que ese enlace de la carta que se envía por correo se puede extraviar y puede quedar en caché [...] quería hacer una especie de login para esas cartas con el número del child [...] no presentar directamente esa carta por url encriptada".

**Por qué el cierre anterior no alcanzaba:** `SEG-015` (primera parte) protegió la puerta de entrada general (`index()`), pero `ver()` — el link **directo** que manda `EnviarNotificacionCartaNueva` por notificación — seguía aceptando el link cifrado por sí solo, sin sesión. Si ese correo/notificación se extraviaba, quedaba cacheado, o se reenviaba por error, quien lo tuviera podía ver **y responder** la carta sin demostrar independientemente que conocía el `numero_child` del niño — exactamente el escenario que describió el usuario.

**Cambio:**
1. `ver()` exige ahora una sesión activa que coincida con el niño del link. Si no la hay, guarda la URL puntual como destino pendiente y redirige a `cartas-ninio.ingresar` — tras ingresar el número correcto, cae **directo en esa carta específica**, no en la lista general (un solo paso extra).
2. `procesarRespuesta()` (la acción real detrás de los 5 `guardarX()`) gana el mismo chequeo de sesión, además del chequeo ya existente del `numero_child` cifrado del formulario (`SEG-006`).
3. `verArchivo()` (foto/PDF del patrocinador) gana el mismo chequeo; al ser un recurso embebido, corta con `404` en vez de redirigir a una página de login.

**Decisión explícita de no agregar una contraseña nueva:** se evaluó y se descartó — eso resolvería `SEG-012` (decisión de negocio, dado que los niños patrocinados no manejan contraseñas), que el usuario no autorizó tocar. La sesión corta ya reutilizada de la parte 1 cierra el problema real (el link deja de ser autosuficiente) sin inventar una credencial nueva ni volver más difícil un flujo que, de por sí, ya requiere un adulto ayudando (los formularios de Presentación tienen 18-25 campos).

**Archivos:** `app/Http/Controllers/ResponderCartaController.php` (`ver()`, `verArchivo()`, `procesarRespuesta()`, nueva constante `SESION_INTENDED`), `tests/Feature/Cartas/ResponderCartaWebTest.php` (3 tests nuevos, 2 actualizados), `docs/auditoria/problemas-detectados.md` (ficha `SEG-015` ampliada con la parte 2).
**Impacto Web:** el link directo de las notificaciones deja de abrir la carta inmediatamente si no hay sesión — pide el número una vez y vuelve directo a la carta. **Impacto API:** ninguno (cambio exclusivo del canal Web). **Impacto React Native:** ninguno.
**Pruebas:** verificación HTTP real de punta a punta con una carta/niño desechables — se generó el link exacto que mandaría la notificación real, se abrió sin sesión previa (cayó en el formulario, no en la carta), se ingresó el número, y se confirmó el aterrizaje directo en esa carta puntual. Suite completa: 85/85 en verde (7 casos nuevos sobre el cierre anterior). Datos de prueba eliminados al terminar; base real sin cambios propios.

---

## 2026-08-08 — Seguridad: `SEG-015` (parte 3) — falta un "cerrar sesión" explícito

**Fase:** Transversal, seguridad. El usuario preguntó directamente: "no olvides de cerrar sesión.?" — señalando que se había armado el "login" (`ingresar()`) del flujo de responder-cartas pero ningún "logout".

**Por qué importa:** en un dispositivo compartido (computadora de la comunidad, teléfono familiar prestado), sin una forma explícita de cerrar sesión, la próxima persona que entrara a `cartas-ninio` dentro de la ventana de 45 min vería las cartas del niño anterior sin volver a pedir el número.

**Cambio:** `ResponderCartaController::salir()` (`POST /cartas-ninio/salir`) limpia las 3 claves de sesión del flujo (`SESION_NINIO`, `SESION_NINIO_EXPIRA`, `SESION_INTENDED`) y redirige al formulario de ingreso. Se agregó un botón "Cerrar sesión" en `responder-cartas/principal.blade.php`, visible en toda página donde ya hay un niño identificado (la lista de cartas, una carta puntual, cualquiera de los 5 formularios de respuesta) y ausente en el formulario de ingreso (ahí no hay sesión que cerrar).

**Archivos:** `app/Http/Controllers/ResponderCartaController.php` (`salir()`), `routes/web.php`, `resources/views/responder-cartas/principal.blade.php`, `tests/Feature/Seguridad/ResponderCartaSesionTest.php` (2 tests nuevos: cierra sesión y corta el acceso; el botón aparece/no aparece según corresponda).
**Impacto Web:** agrega un botón visible, sin tocar ningún flujo existente. **Impacto API:** ninguno. **Impacto React Native:** ninguno.
**Pruebas:** verificación HTTP real de punta a punta — ingresar con un número real, confirmar que se ve el nombre del niño, cerrar sesión, confirmar que volver a `mis-cartas` rebota al formulario en vez de mostrar las cartas del niño anterior. Suite completa: 87/87 en verde.

---

## 2026-08-08 — Fase 3, UX/UI: auditoría Parte 0 — carta individual, landing, dashboard y filtros

**Fase:** Fase 3 — UX/UI (continuación, sin tocar código todavía — solo diagnóstico, `CLAUDE.md` sección 9)
**Rama:** `security/fase-2-hallazgos-criticos`
**Motivo:** el usuario pidió continuar mejorando UX/UI dividido en partes, empezando por una auditoría de `cartas/show`, `welcome`, `home`+`HomeController` y los filtros de los listados sin filtro (`ninios`, `usuarios`, `comunidad`) frente al patrón ya maduro de `cartas/index`.
**Cambio:** solo documentación, cero código tocado. Se creó `docs/auditoria/ux-ui-dashboard-vistas.md` con el detalle de cada punto auditado y se registraron 4 hallazgos nuevos como Pendientes en `docs/auditoria/problemas-detectados.md`: `UX-006` (código muerto en `cartas/show`), `UX-007` (`welcome.blade.php` es un alias literal de `home.blade.php`, sin landing pública propia), `UX-008` (dashboard sin datos ni diferenciación por rol — `HomeController::index()` no pasa ninguna variable a la vista), `UX-009` (solo `cartas/index` tiene formulario de filtros; `ninios`/`usuarios`/`comunidad` no tienen ninguno).
**Archivos:** `docs/auditoria/ux-ui-dashboard-vistas.md` (nuevo), `docs/auditoria/problemas-detectados.md`.
**Impacto Web/API/React Native:** ninguno — es diagnóstico.
**Pendiente:** `UX-008` (KPIs del dashboard) requiere que el usuario defina primero qué métricas de negocio le interesan antes de diseñar nada (preguntas concretas en la sección 5 del documento de auditoría). El resto de los hallazgos (`UX-006`, `UX-007`, `UX-009`) puede implementarse con autorización explícita, un paso a la vez, en el orden propuesto en la sección 8 del documento.

---

## 2026-08-08 — Fase 3, UX/UI: cierre de `UX-006` — limpieza de `cartas/show` + tarjeta de metadatos

**Fase:** Fase 3 — UX/UI
**Rama:** `security/fase-2-hallazgos-criticos`
**Motivo:** primer paso autorizado del plan de la auditoría Parte 0 (el usuario eligió empezar por `cartas.show`, el cambio de menor riesgo y sin dependencias externas). El usuario ya definió las métricas del dashboard (cartas pendientes/enviadas por responder, niños por comunidad/sin carta reciente) para cuando se aborde `UX-008`.
**Cambio:** se elimina el bloque de ~20 líneas de HTML comentado (diseño viejo de comparación v1/v2, obsoleto desde el cierre de `UX-001`). Se agrega una segunda columna (`col-md-6`) con una tarjeta "Detalle de la carta": tipo (badge con color/ícono), estado (badge de color), niño, comunidad, gestor, fecha de creación y fecha de respuesta (o "Sin responder"). Se reutilizan los accessors ya existentes en `App\Models\Carta` (`color`, `icono`, `estado_color`) y el mismo formato de fecha (`d/m/Y H:i`) que usa `CartaDataTable`, sin crear lógica nueva.
**Archivos:** `resources/views/cartas/show.blade.php`.
**Impacto Web:** la vista de detalle de carta pasa de una sola columna (solo PDF) a dos columnas (PDF + metadatos); ningún dato nuevo se persiste ni se calcula, todo viene de columnas y relaciones ya cargadas por `CartaController::show()`. **Impacto API:** ninguno. **Impacto React Native:** ninguno — esta vista no la consume `CactuApp`.
**Pruebas:** `php artisan view:cache` sin errores; render directo de la vista (vía `tinker`, con `View::share('errors', ...)` para simular el contexto de request) contra una carta real respondida (confirma ausencia del comentario viejo, presencia de "Detalle de la carta" y de los badges) y contra una carta real sin responder (confirma "Sin responder"). Suite completa: 87/87 en verde. Base real sin cambios (solo lecturas).

---

## 2026-08-08 — Fase 3, UX/UI: cierre de `UX-007` — `welcome` deja de ser un alias de `home`

**Fase:** Fase 3 — UX/UI
**Rama:** `security/fase-2-hallazgos-criticos`
**Motivo:** segundo paso del plan de la auditoría Parte 0. El usuario, ante las tres opciones planteadas (mantener alias con limpieza / separar en landing propia / dejar como está), eligió separar `welcome` en una landing pública propia — ver la decisión completa en `docs/cambios/decisiones-arquitectura.md`.
**Cambio (primer intento, revertido):** `resources/views/welcome.blade.php` dejó de ser `@include('home')` y pasó a ser un archivo raíz independiente (`<!DOCTYPE html>` + `@include('sections.head')`, patrón calcado de `responder-cartas/principal.blade.php`). El usuario lo revisó y pidió volver atrás: rompía la consistencia visual con el resto del sistema, incluido `login`.
**Cambio (segundo intento, también corregido):** `welcome.blade.php` pasó a `@extends('layouts.app')` — el mismo layout que usa `login`, con su navbar/header habituales (para invitados, el navbar ya mostraba el link "Ingresar"). Se le agregó una portada (`cactu-cover`) con degradado CSS y una ola decorativa en SVG. El usuario la vio y pidió simplificar más: el logo usado (`logo_icon.svg`, la variante clara para el navbar oscuro) no se distinguía bien en tema claro, y la sucesión de bloques (portada → tarjetas → botón) se leía como "tres páginas divididas horizontalmente" en vez de un solo contenido cohesivo.
**Cambio (versión final):** se quitó la portada por completo. `welcome.blade.php` queda con un único contenedor centrado vertical y horizontalmente (`d-flex flex-column justify-content-center align-items-center`, `min-height: 60vh`, sin CSS custom): logo `logo_icon_azul.svg` (la variante correcta para fondo claro, la misma que usaba el `home.blade.php` original), título "CACTU", descripción, las dos tarjetas informativas ya existentes ("Gestión de Comunidades"/"Correspondencia") y el botón "Iniciar Sesión"; sin el banner interno de "Actualización Importante". `routes/web.php` gana un chequeo `Auth::check()` en la ruta `/`: un usuario ya autenticado se redirige a `route('home')` en vez de ver la landing pública (antes no hacía falta, porque `welcome` y `home` eran la misma vista). `home.blade.php`/`HomeController` quedan sin cambios (eso es `UX-008`, pendiente).
**Archivos:** `resources/views/welcome.blade.php`, `routes/web.php`, `tests/Feature/WelcomeTest.php` (nuevo).
**Impacto Web:** un visitante sin sesión ve una landing propia, simple y centrada, con el mismo navbar/layout que el resto del sistema, correcta en tema claro y oscuro. Un usuario autenticado que visite `/` ahora aterriza en `/home`. **Impacto API:** ninguno. **Impacto React Native:** ninguno — `CactuApp` no consume esta ruta.
**Pruebas:** `php artisan view:cache` sin errores; verificación HTTP real contra el servidor local (`GET /` sin sesión → 200, contiene "Iniciar Sesión", no contiene "Acceso Rápido" ni "Actualización Importante"); `tests/Feature/WelcomeTest.php` (2 casos nuevos: landing pública para invitado, redirección a `/home` para usuario autenticado vía `actingAs`). Suite completa: 89/89 en verde. Base real sin cambios propios.

---

## 2026-08-08 — Fase 3, UX/UI: cierre de `UX-008` — dashboard con KPIs reales por rol

**Fase:** Fase 3 — UX/UI
**Rama:** `security/fase-2-hallazgos-criticos`
**Motivo:** tercer paso del plan de la auditoría Parte 0. El usuario ya había definido las métricas de negocio (cartas por responder/respondidas, niños por comunidad/sin carta) en una sesión anterior; en esta sesión se confirmaron los detalles de diseño pendientes: eliminar el banner vencido de "Actualización Importante", mantener la tarjeta de "Manual de Usuario" (es un link funcional, no decorativo), definir "niño sin carta reciente" como "nunca ha recibido ninguna carta" (más simple y sin ambigüedad de umbral temporal), y el layout concreto (4 KPI cards + Manual de Usuario + tabla de niños por comunidad).
**Cambio:** `HomeController::index()` calcula 4 métricas con el mismo patrón de alcance por rol que ya usan `CartaDataTable`/`NinioDataTable` (`$user->hasRole('ADMINISTRADOR')` ve todo; un `GESTOR` ve solo lo suyo vía las relaciones ya existentes en `User`: `cartas()`, `ninios()`, `comunidades()`): cartas con `estado = 'Enviado'` (por responder), cartas con `estado = 'Respondida'`, total de niños en el alcance del usuario, y niños sin ninguna carta asociada (`whereDoesntHave('cartas')`). También arma un desglose de niños por comunidad (`withCount('ninios')`). Se agregó la relación `Comunidad::ninios()` (hasMany, inversa de `Ninio::comunidad()`), que faltaba y era necesaria para ese desglose sin duplicar queries manuales. `home.blade.php` se reescribió por completo: se eliminó el banner vencido, las tarjetas decorativas sin datos ("Acceso Rápido"), la tarjeta "Sistema de Gestión CACTU", la tarjeta de logo/descripción de CACTU (redundante con la nueva landing de `welcome.blade.php`, cerrada en `UX-007`) y la rama `@else`/`@guest` (código muerto: la ruta `home` siempre exige `auth`, por lo que esa rama nunca se ejecutaba). Quedan 4 KPI cards, la tarjeta de "Manual de Usuario" (se mantiene, funcional) y una tabla de niños por comunidad.
**Archivos:** `app/Http/Controllers/HomeController.php`, `app/Models/Comunidad.php` (relación `ninios()` nueva), `resources/views/home.blade.php`.
**Impacto Web:** el dashboard post-login pasa de contenido 100% estático e idéntico para todos los roles, a KPIs reales con el mismo alcance por rol que el resto del sistema. **Impacto API:** ninguno. **Impacto React Native:** ninguno — `CactuApp` no consume esta ruta.
**Pruebas:** `php artisan view:cache` sin errores; render real del controller contra la base de datos local (vía `tinker`, con `View::share('errors', ...)` para simular el contexto de request) con un `ADMINISTRADOR` real (`alopez@cactu.org.ec`: ve el total global — 272 cartas por responder, 6373 respondidas, 6528 niños, 3508 sin carta, 28 comunidades en la tabla) y con un `GESTOR` real (`ialmache@cactu.org.ec`: ve solo su alcance — 0 cartas por responder, 6 respondidas, 388 niños, 382 sin carta, 1 sola comunidad en la tabla, verificado contra `$gestor->comunidades()->count()`/`$gestor->ninios()->count()`/`$gestor->cartas()->count()` directamente). Suite completa: 89/89 en verde. Base real sin cambios (solo lecturas).

---

## 2026-08-08 — Fase 3, UX/UI: cierre de `UX-009` — filtros en `ninios`, `usuarios` y `comunidad`

**Fase:** Fase 3 — UX/UI
**Rama:** `security/fase-2-hallazgos-criticos`
**Motivo:** cuarto y último paso del plan de la auditoría Parte 0. El usuario confirmó los campos concretos a filtrar en cada listado y autorizó implementar los 3 juntos en la misma sesión, por tratarse de un cambio mecánico de bajo riesgo que reutiliza un patrón ya probado en `cartas/index` (card colapsable de filtros + formulario GET + AJAX contra el DataTable, auto-aplicación con debounce, indicador de "filtros aplicados", persistencia en la URL vía `history.pushState`), no una decisión de diseño nueva en cada listado.
**Cambio:**
- `ninios/index.blade.php` + `NinioController::index()` + `NinioDataTable`: filtros por Comunidad, Estado (`ACTIVO`/`INACTIVO`) y Género (`Male`/`Female`). El método `applyFilters()` se agregó dentro del `query()` existente, preservando el filtro de alcance por rol ya vigente (`SEG-002`: un `GESTOR` solo ve niños de sus comunidades) — los filtros de búsqueda se aplican encima de ese alcance, nunca lo amplían.
- `usuarios/index.blade.php` + `UserController::index()` + `UserDataTable`: filtros por Rol (vía `whereHas('roles', ...)`, usando los roles reales del sistema — `Spatie\Permission\Models\Role::all()`), Estado y Provincia (`COTOPAXI`/`TUNGURAHUA`, los mismos valores fijos que ya usan los formularios de creación/edición de usuario).
- `comunidad/index.blade.php` + `ComunidadController::index()` + `ComunidadDataTable`: filtro por Gestor asignado (`user_id`).
- Ninguno de estos 3 listados requiere alcance por rol en los filtros (a diferencia del dashboard de `UX-008`): las 3 rutas ya están restringidas a `ADMINISTRADOR` por el middleware `role:` del grupo de rutas en `routes/web.php` — un `GESTOR` nunca llega a ver estas pantallas (usa `mis-ninios` en su lugar).
**Archivos:** `app/Http/Controllers/NinioController.php`, `app/Http/Controllers/UserController.php`, `app/Http/Controllers/ComunidadController.php`, `app/DataTables/NinioDataTable.php`, `app/DataTables/UserDataTable.php`, `app/DataTables/ComunidadDataTable.php`, `resources/views/ninios/index.blade.php`, `resources/views/usuarios/index.blade.php`, `resources/views/comunidad/index.blade.php`.
**Impacto Web:** los 3 listados ganan filtros propios de dominio, consistentes visualmente con `cartas/index`. **Impacto API:** ninguno. **Impacto React Native:** ninguno.
**Pruebas:** `php artisan view:cache` sin errores; render real de los 3 controllers contra la base local (vía `tinker`) confirmando la presencia del card "Filtros de Búsqueda"; verificación de cada filtro de punta a punta comparando el resultado de `DataTable::query()` con el conteo directo esperado, contra datos reales: comunidad (`North Pastocalle`, id 1 → 386 niños, coincide), género (`Female` → 3399, coincide), rol (`GESTOR` → 22 usuarios, coincide), estado de usuario (`ACTIVO` → 23, coincide), gestor asignado en comunidad (`ialmache@cactu.org.ec` → 1 comunidad, coincide). Suite completa: 89/89 en verde. Base real sin cambios (solo lecturas).

---

## 2026-08-08 — `TEST-001`: actualización de la ficha a la cobertura real + limpieza del boilerplate

**Fase:** Transversal (documentación + limpieza menor, `CLAUDE.md` sección 9)
**Rama:** `security/fase-2-hallazgos-criticos`
**Motivo:** el usuario pidió revisar `TEST-001` después de notar que su descripción original ("ausencia total de pruebas... ni de autorización, ni de flujo de cartas, ni de endpoints API") ya no coincidía con la realidad — hoy la suite tiene 89 tests en 22 archivos, con cobertura sólida en exactamente esas tres áreas.
**Cambio:** se reescribió la ficha `TEST-001` en `docs/auditoria/problemas-detectados.md` para reflejar el estado real: se documenta qué está cubierto hoy (autorización, flujo de cartas web y API, notificaciones Firebase, comando de limpieza), se baja la severidad de ALTO a MEDIO (el riesgo original ya no aplica), y se listan las brechas concretas que sí siguen vigentes — módulo `comunidad` sin ningún test, CRUD de `ninios`/`usuarios` sin probar (solo su autorización), y los KPIs de `UX-008` junto con los filtros de `UX-009` (ambos de esta misma sesión) verificados solo manualmente, sin test de regresión. Se eliminaron `tests/Feature/ExampleTest.php` y `tests/Unit/ExampleTest.php` — boilerplate genérico de Laravel sin relación con CACTU, confirmado sin referencias externas (`grep` en todo el repo fuera de `vendor/`); el de `Feature` pegaba a `GET /` y había quedado redundante con `WelcomeTest.php`.
**Archivos:** `docs/auditoria/problemas-detectados.md`; eliminados `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`.
**Impacto Web/API/React Native:** ninguno — solo documentación y 2 archivos de test eliminados, sin código de aplicación tocado.
**Pruebas:** suite completa tras el borrado: 87/87 en verde (89 menos los 2 boilerplate eliminados). No se escribieron tests nuevos para las brechas detectadas — decisión explícita del usuario de dejarlo como diagnóstico por ahora, priorizable en Fase 8.

---

## 2026-08-08 — `BUG-001`: diagnóstico corregido + `UpdateRq` deja de depender de un campo oculto

**Fase:** Fase 8 — Performance y Calidad (mantenimiento)
**Rama:** `security/fase-2-hallazgos-criticos`
**Motivo:** siguiente ítem elegido por el usuario tras el cierre de `TEST-001`. Antes de aplicar el fix propuesto en la ficha original (`$this->route('usuario')` en vez de `$this->id`), se verificó el comportamiento actual con `tinker` reproduciendo el payload exacto que envía el formulario real de `usuarios/edit.blade.php` — y la validación **sí pasaba**. La ficha original estaba equivocada: no era un bug reproducible en producción.
**Causa de la confusión:** `usuarios/edit.blade.php` tenía un `<input type="hidden" name="id" value="{{ $user->id }}">` (línea 12) que hacía que `$this->id` en `UpdateRq` resolviera correctamente vía el input del formulario, no vía el parámetro de ruta. El test de `UsuariosAutorizacionTest::test_un_administrador_no_puede_ascender_a_otro_usuario_a_administrador_desde_el_formulario` arma su payload a mano sin ese campo `id` (a diferencia del navegador real), por lo que ahí sí fallaba — y ese fallo fue el origen del hallazgo original, sin verificarlo contra el flujo real completo.
**Cambio:** se aplicó igual la corrección propuesta (`$this->route('usuario')`), porque aunque no rompía nada hoy, depender de un campo oculto controlado por el cliente en vez del parámetro de ruta (la fuente de verdad, ya validada por el route model binding) es frágil — cualquier cambio futuro al formulario que quitara ese campo habría reintroducido el problema real, sin aviso. Se eliminó el `<input type="hidden" name="id">`, ya innecesario. Se corrigió el test que traía el workaround (cambiaba el email para esquivar el bug, con un comentario que ahora quedaba desactualizado) para usar el mismo email, y se agregó `test_editar_usuario_sin_cambiar_su_email_no_falla_la_validacion` como regresión explícita del caso real.
**Archivos:** `app/Http/Requests/User/UpdateRq.php`, `resources/views/usuarios/edit.blade.php`, `tests/Feature/Seguridad/UsuariosAutorizacionTest.php`.
**Impacto Web:** ninguno visible — el formulario ya funcionaba antes; ahora es robusto independientemente del payload del cliente. **Impacto API:** ninguno. **Impacto React Native:** ninguno.
**Pruebas:** `php artisan view:cache` sin errores; render real de `usuarios/edit` vía `tinker` confirmando que el campo oculto ya no está presente; test nuevo verificado en verde (`assertSessionDoesntHaveErrors` + `assertRedirect`). Suite completa: 88/88 en verde (87 + 1 test nuevo). Base real sin cambios (solo lecturas).
