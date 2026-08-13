# Problemas detectados

> Formato según `CLAUDE.md` sección 43. Los problemas de seguridad (`SEG-*`) se detallan en `seguridad.md`; aquí se listan también con referencia cruzada para tener un catálogo único, más los problemas de arquitectura, UX/UI, performance y documentación no cubiertos allí.

---

**ID:** SEG-001
**Módulo:** Usuarios
**Problema:** Escalación de privilegios — gestión de usuarios/roles sin autorización de backend
**Tipo:** SEGURIDAD
**Severidad:** CRÍTICO
**Archivos afectados:** `app/Http/Controllers/UserController.php`, `routes/web.php`, `app/Http/Kernel.php`, `app/DataTables/UserDataTable.php`
**Comportamiento actual:** Cualquier usuario autenticado puede acceder a `/usuarios/*` y asignarse o asignar el rol ADMINISTRADOR.
**Comportamiento esperado:** Solo ADMINISTRADOR puede gestionar usuarios y roles; verificación en backend, no solo en el menú.
**Solución propuesta:** Registrar middleware `role:` de Spatie en `Kernel.php` y aplicarlo a las rutas `usuarios`/`comunidad`/`ninios`; añadir `$this->authorize()` explícito en los controladores como defensa en profundidad.
**Impacto Web:** Alto. **Impacto API:** No aplica. **Impacto React Native:** No aplica.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Ver `docs/cambios/changelog.md`. **Fase:** Fase 2 — Seguridad.

---

**ID:** SEG-002
**Módulo:** Niños
**Problema:** Exposición/modificación de niños de cualquier comunidad
**Tipo:** SEGURIDAD
**Severidad:** CRÍTICO
**Archivos afectados:** `app/Http/Controllers/NinioController.php`, `app/DataTables/NinioDataTable.php`, `routes/web.php`
**Comportamiento actual:** Cualquier autenticado ve y modifica niños de cualquier comunidad vía `/ninios/*`.
**Comportamiento esperado:** Acceso restringido a ADMINISTRADOR (o a la comunidad del gestor, replicando el patrón de `MisNiniosController`).
**Solución propuesta:** Igual que SEG-001, más filtrar `NinioDataTable::query()` cuando aplique.
**Impacto Web:** Alto (PII de menores). **Impacto API:** No aplica. **Impacto React Native:** No aplica.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Ver `docs/cambios/changelog.md`. **Fase:** Fase 2 — Seguridad.

---

**ID:** SEG-003
**Módulo:** Cartas / API
**Problema:** Descarga pública y anónima de PDF por ID secuencial
**Tipo:** SEGURIDAD
**Severidad:** CRÍTICO
**Archivos afectados:** `routes/api.php`, `app/Http/Controllers/WelcomeController.php`
**Comportamiento actual:** `GET /api/dcpdf/{id}` sin autenticación ni cifrado de ID.
**Comportamiento esperado:** Cifrar el ID (como en `ver-archivo-pdf-por-ninio`) o exigir `auth:sanctum` + verificación de pertenencia.
**Solución propuesta:** Reemplazar `{id}` plano por `Crypt::encryptString`, replicando el patrón ya usado en la ruta hermana.
**Impacto Web:** No aplica. **Impacto API:** Crítico. **Impacto React Native:** Crítico (expone datos de menores).
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Ver `docs/cambios/changelog.md`. **Fase:** Fase 2 — Seguridad.

---

**ID:** SEG-004
**Módulo:** API móvil (Cartas)
**Problema:** IDOR en `listar-mis-cartas` por confiar en `userId` del cliente
**Tipo:** SEGURIDAD
**Severidad:** CRÍTICO
**Archivos afectados:** `app/Http/Controllers/Api/ResponderCarta.php`
**Comportamiento actual:** Usa `Ninio::find($request->userId)` en vez del niño autenticado por token.
**Comportamiento esperado:** Usar `$request->user()`.
**Solución propuesta:** Cambio de una línea: `$ninio = $request->user();`. Requiere confirmar con el cliente móvil que ya no envía `userId` o que se ignora sin romper el contrato.
**Impacto Web:** No aplica. **Impacto API:** Crítico. **Impacto React Native:** Crítico.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Ver `docs/cambios/changelog.md`. **Pendiente:** confirmar con el equipo React Native antes de desplegar (ver sección 7 de `integracion-movil.md`). **Fase:** Fase 2 — Seguridad.

---

**ID:** SEG-005
**Módulo:** API móvil (Cartas)
**Problema:** `guardarCarta`/`eliminarCarta` no verifican pertenencia de la carta al niño autenticado
**Tipo:** SEGURIDAD
**Severidad:** ALTO
**Archivos afectados:** `app/Http/Controllers/Api/ResponderCarta.php`
**Comportamiento actual:** Cualquier niño autenticado puede responder/eliminar cartas de otro niño.
**Comportamiento esperado:** Verificar `$carta->ninio_id === $request->user()->id` antes de procesar.
**Solución propuesta:** Añadir verificación explícita o policy análoga a `responderCartaXninio` pero que sí valide identidad real.
**Impacto Web:** No aplica. **Impacto API:** Alto. **Impacto React Native:** Alto.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Ver `docs/cambios/changelog.md`. **Fase:** Fase 2 — Seguridad.

---

**ID:** SEG-006
**Módulo:** Cartas (respuesta web del niño)
**Problema:** `CartaPolicy::responderCartaXninio` no valida el dueño real de la carta
**Tipo:** SEGURIDAD
**Severidad:** MEDIO
**Archivos afectados:** `app/Policies/CartaPolicy.php`, `app/Http/Controllers/ResponderCartaController.php`
**Comportamiento actual:** La policy solo verifica que la carta no esté ya `Respondida`; ignora `$user`.
**Análisis (Fase 2):** las rutas `cartas-ninio.*` (`routes/web.php`) están fuera del grupo `auth`/`verified` — no existe niño autenticado en este flujo web; la única protección real es el ID de carta cifrado en la URL (`Crypt::encryptString`). No hay una identidad verificable contra la cual validar sin rediseñar el flujo (p. ej. exigir y comparar el `numero_child` cifrado dentro de `guardarArchivo`, tal como ya hace `ResponderCartaController::ver()`).
**Solución propuesta:** en una fase futura, hacer que `guardarContestacion`/`guardarAgradecimiento`/`guardarIniciada`/`guardarPresentacionMayor`/`guardarPresentacionMenor` reciban también el `numero_child` cifrado (o un token de sesión temporal emitido al abrir `cartas-ninio.index`) y verificarlo contra `$carta->ninio->numero_child` antes de guardar, replicando `ver()`.
**Impacto Web:** Bajo/medio (mitigado parcialmente por el ID cifrado). **Impacto API:** No aplica (la API usa `responderCartaXninio` solo indirectamente; el control real ahí es SEG-005).
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`, Fase 4). Los 5 formularios web de respuesta envían además el `numero_child` cifrado del niño; `ResponderCartaController::procesarRespuesta()` lo verifica contra el dueño real de la carta antes de procesar (mismo patrón que `ver()`), respondiendo 404 si no coincide o falta. `CartaPolicy::responderCartaXninio` no se tocó (sigue validando solo el estado; la verificación de identidad no es responsabilidad de la Policy en este flujo sin sesión). Ver `docs/cambios/changelog.md` y `docs/cambios/decisiones-arquitectura.md` (por qué ya no aplicaba el riesgo de romper enlaces ya enviados).

---

**ID:** SEG-007
**Módulo:** Cartas
**Problema:** `CartaController` no autorizaba `show`/`edit`/`update`, solo `destroy`
**Tipo:** SEGURIDAD
**Severidad:** MEDIO
**Archivos afectados:** `app/Http/Controllers/CartaController.php`, `app/Policies/CartaPolicy.php`
**Comportamiento actual (previo a la corrección):** un gestor autenticado podía ver/editar el `detalle` de una carta de otro gestor conociendo el ID.
**Comportamiento esperado:** autorización explícita en `show`/`edit`/`update`, igual que ya tenía `destroy`.
**Solución aplicada:** `CartaPolicy::view`/`update` (dueño o ADMINISTRADOR) + `$this->authorize()` en el controlador.
**Impacto Web:** Medio. **Impacto API:** No aplica. **Impacto React Native:** No aplica.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Ver `docs/cambios/changelog.md`. **Fase:** Fase 2 — Seguridad.

---

**ID:** BUG-001
**Módulo:** Usuarios
**Problema:** `UpdateRq` construye la regla de email único con un parámetro de ruta que no existe
**Tipo:** BUG
**Severidad:** MEDIO
**Archivos afectados:** `app/Http/Requests/User/UpdateRq.php`
**Comportamiento actual (corregido):** la regla original `'unique:users,email,'.$this->id` asumía un parámetro de ruta llamado `id`, pero `Route::resource('usuarios', UserController::class)` genera el parámetro `usuario`. **Corrección al diagnóstico (2026-08-08):** el hallazgo original decía que esto rompía la edición en producción; se verificó con `tinker` reproduciendo el payload exacto que envía el formulario real y **no era así** — `resources/views/usuarios/edit.blade.php` tenía un `<input type="hidden" name="id" value="{{ $user->id }}">` que hacía que `$this->id` resolviera igual, vía el input, no vía la ruta. El test de `UsuariosAutorizacionTest` que "reprodujo" el bug no enviaba ese campo `id` en su payload (a diferencia del navegador real), por eso solo fallaba ahí. El problema real no era una edición rota en producción, sino una dependencia frágil de un campo oculto controlado por el cliente en vez del parámetro de ruta (la fuente de verdad).
**Comportamiento esperado:** la regla `unique` debe excluir al usuario que se está editando usando el identificador de la ruta, no un campo de formulario que podría faltar o ser manipulado.
**Cómo se detectó:** al escribir pruebas de autorización para SEG-001 (Fase 2), reutilizar el email original de un usuario en `PUT /usuarios/{id}` produjo el error de validación "El valor del campo email ya está en uso." en el test — luego, al revisar el hallazgo (2026-08-08), se confirmó que no se reproducía con el payload real del formulario.
**Solución aplicada:** `UpdateRq::rules()` usa `$this->route('usuario')` en vez de `$this->id`. Se eliminó el `<input type="hidden" name="id">` de `usuarios/edit.blade.php` (ya no se necesita, y era la fuente de la fragilidad). Se corrigió el test de `UsuariosAutorizacionTest` que traía el workaround (cambiar el email solo para evitar el bug) y se agregó `test_editar_usuario_sin_cambiar_su_email_no_falla_la_validacion` como regresión explícita.
**Impacto Web:** Bajo — el comportamiento visible para el usuario no cambiaba (el formulario real ya funcionaba), pero ahora es robusto independientemente de lo que el cliente envíe en el body. **Impacto API:** No aplica. **Impacto React Native:** No aplica.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Ver `docs/cambios/changelog.md`.

---

**ID:** SEG-008
**Módulo:** Usuarios / Cartas / Niños
**Problema:** mensajes de excepción (`$th->getMessage()`) expuestos al usuario final
**Tipo:** SEGURIDAD
**Severidad:** MEDIO
**Archivos afectados:** `app/Http/Controllers/UserController.php` (store/update), `app/Http/Controllers/CartaController.php` (store/destroy), `app/Http/Controllers/MisNiniosController.php` (destroy), `app/Http/Controllers/Api/ResponderCarta.php` (guardarCarta)
**Comportamiento actual (previo a la corrección):** los mensajes flash y las respuestas JSON de error concatenaban `$th->getMessage()`, filtrando detalles internos (nombres de tablas/columnas, rutas de archivo) a la interfaz visible por el usuario.
**Comportamiento esperado:** registrar el detalle técnico en el log del servidor; mostrar al usuario un mensaje genérico.
**Solución aplicada:** se añade `Log::error(...)` con el mensaje real de la excepción en cada uno de los 6 puntos, y se retira `.$th->getMessage()` de los mensajes/flash/JSON de respuesta.
**Impacto Web:** Medio. **Impacto API:** Medio.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Ver `docs/cambios/changelog.md`. **Fase:** Fase 2 — Seguridad.

---

**ID:** ARQ-001
**Módulo:** Cartas (Web + API móvil)
**Problema:** Duplicación total de la lógica de armado de `detalle` y de guardado de respuestas entre `ResponderCartaController` (web) y `Api\ResponderCarta` (móvil)
**Tipo:** ARQUITECTURA
**Severidad:** ALTO
**Archivos afectados:** `app/Http/Controllers/ResponderCartaController.php`, `app/Http/Controllers/Api/ResponderCarta.php`
**Descripción:** Los 4 tipos de carta (Contestación, Agradecimiento, Iniciada, Presentación mayor/menor) tienen su HTML de `detalle` construido por concatenación de strings, **duplicado casi carácter por carácter** entre el controlador web y el de la API. Cualquier cambio de redacción, corrección ortográfica o campo nuevo debe replicarse manualmente en dos lugares, con alto riesgo de que diverjan (de hecho ya difieren ligeramente en el manejo de autorización, ver SEG-005/SEG-006).
**Ubicación A:** `ResponderCartaController::guardarContestacion/guardarAgradecimiento/guardarIniciada/guardarPresentacionMayor/guardarPresentacionMenor`.
**Ubicación B:** `Api\ResponderCarta::responderContestacion/responderAgradecimineto/responderIniciada/responderCartaPresentacionMayor/responderCartaPresentacionMenor`.
**Riesgo:** Divergencia de reglas de negocio entre Web y API, tal como exige evitar `CLAUDE.md` sección 11 ("Regla de oro de negocio").
**Solución recomendada:** Extraer una `Action` por tipo de carta (p. ej. `GuardarRespuestaContestacionAction`) invocada desde ambos controladores, centralizando la plantilla de `detalle` y el guardado del archivo.
**Impacto Web:** Medio. **Impacto API:** Medio. **Impacto React Native:** Medio (mismo contrato, menor riesgo de romperlo si se centraliza con cuidado).
**Fase sugerida:** Fase 4 — Unificación Web/API.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Se crearon 6 Actions en `app/Actions/`: `ResponderCartaContestacionAction`, `ResponderCartaAgradecimientoAction`, `ResponderCartaIniciadaAction`, `ResponderCartaPresentacionMayorAction`, `ResponderCartaPresentacionMenorAction` (una por tipo, arman el `detalle` a partir de primitivos) y `GuardarRespuestaCartaAction` (compartida por las 5, guarda imagen/foto_familia, marca `Respondida` y notifica al gestor). `ResponderCartaController` (web) y `Api\ResponderCarta` (móvil) ahora delegan en ellas; lo que se mantuvo deliberadamente **sin unificar** por canal (resolución de la carta, autorización, formato de respuesta) se documenta en `docs/cambios/decisiones-arquitectura.md`. Ver `docs/cambios/changelog.md`.

---

**ID:** ARQ-002
**Módulo:** Cartas
**Problema:** `CartaController` concentra validación, transacción, subida de archivos, notificación síncrona y generación de PDF en un mismo método (`store`)
**Tipo:** ARQUITECTURA
**Severidad:** MEDIO
**Archivos afectados:** `app/Http/Controllers/CartaController.php`
**Comportamiento actual:** El método `store()` hace ~80 líneas mezclando las responsabilidades citadas.
**Solución propuesta:** Extraer a una Action (`RegistrarCartaAction`) y un `Service` de almacenamiento de archivos, siguiendo la arquitectura objetivo de `CLAUDE.md`. No se implementa aún — solo se documenta (regla "primero comprender, después modificar").
**Impacto Web:** Bajo (funciona, pero es difícil de probar y mantener). **Impacto API:** No aplica. **Impacto React Native:** No aplica.
**Fase sugerida:** Fase 1 — Arquitectura.
**Estado:** **Resuelto parcialmente** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Se extrajo `RegistrarCartaAction`, con test de regresión (`tests/Feature/Cartas/RegistrarCartaTest.php`) verificado en verde antes y después del cambio. El `Service` de almacenamiento de archivos mencionado como idea original **no se implementó** — no había necesidad concreta que lo justificara todavía (evitar sobrearquitectura, `CLAUDE.md` sección 56). Ver `docs/cambios/changelog.md`.

---

**ID:** ARQ-003
**Módulo:** Global
**Problema:** Imports de controllers inexistentes (`GestorController`, `SensorDataController`)
**Tipo:** DEUDA TÉCNICA
**Severidad:** BAJO
**Archivos afectados:** `routes/web.php:5`, `routes/api.php:4`
**Solución propuesta:** Eliminar los `use` no utilizados, o si representan funcionalidad planificada, documentarla explícitamente en el roadmap.
**Fase sugerida:** Cualquier fase de limpieza menor (bajo riesgo, alto valor de claridad).
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Se eliminaron los 3 imports muertos confirmados por grep (se sumó `UserRqStore` en `UserController.php`, detectado en la misma revisión). Ver `docs/cambios/changelog.md`.

---

**ID:** ARQ-004
**Módulo:** Cartas (Policies)
**Problema:** `CartaPolicy` mezcla verbos legacy en español (`ver`, `eliminar`) con verbos Laravel (`view`, `update`, agregados en SEG-007/Fase 2) que **no son alias entre sí** — tienen lógica distinta
**Tipo:** ARQUITECTURA
**Severidad:** BAJO
**Archivos afectados:** `app/Policies/CartaPolicy.php`, `app/Http/Controllers/CartaController.php:188` (usa `eliminar`), `resources/views/cartas/action.blade.php:8`, `resources/views/cartas/show.blade.php:58` (usan `ver`)
**Comportamiento actual:** `ver()` exige `estado === 'Respondida'` **además** de ser dueño/admin (controla cuándo se muestra el link "ver" en las vistas). `view()` (nueva) autoriza a dueño/admin sin esa restricción de estado, y protege `show/edit/update` del controller a nivel backend. `eliminar()` sigue vigente para `destroy`.
**Riesgo:** ninguno inmediato — ambos métodos funcionan correctamente para su propósito actual. El riesgo es de mantenibilidad: un desarrollador futuro podría asumir que `ver`/`view` son el mismo concepto y colapsarlos sin darse cuenta de que cambia una regla de negocio (cuándo se puede ver el link vs. cuándo se puede ver el detalle).
**Solución propuesta:** Detectado durante Fase 1 al definir la convención de nombres de Policies (ver `docs/cambios/decisiones-arquitectura.md`). Antes de unificar nomenclatura hace falta una decisión de negocio, no solo de naming: ¿la restricción "solo si Respondida" debe aplicarse también a `show`/`edit`/`update`, o el link de las vistas debe dejar de depender de un método de Policy separado? Se deja pendiente para revisar con el usuario.
**Impacto Web:** Bajo (comportamiento actual correcto, solo claridad de código). **Impacto API:** No aplica. **Impacto React Native:** No aplica.
**Fase sugerida:** A definir junto con el usuario, posiblemente Fase 4 (cuando se toque `CartaPolicy` de nuevo para authorizeResource()).
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`, Fase 4). Decisión del usuario: mantener ambos métodos con su lógica actual (son conceptos distintos, no alias) y solo eliminar la ambigüedad de nombres. `ver()` se renombró a `mostrarEnlaceVer()`; sus 2 referencias se actualizaron (`resources/views/cartas/action.blade.php`, real, y `resources/views/cartas/show.blade.php`, dentro de un bloque Blade comentado — código muerto, detectado y corregido por consistencia). Sin cambio de comportamiento. Ver `docs/cambios/decisiones-arquitectura.md`.

---

**ID:** ARQ-005
**Módulo:** API (routing)
**Problema:** las 12 rutas de `routes/api.php` no tenían ningún prefijo de versión, contradiciendo el objetivo de Fase 5 (`docs/fases/roadmap.md`) de "introducir versionado (`/api/v1/`) antes de cualquier cambio de forma de respuesta"
**Tipo:** ARQUITECTURA
**Severidad:** BAJO (sin impacto de seguridad ni funcional; bloquea poder cambiar la forma de una respuesta API sin romper a `CactuApp`)
**Archivos afectados:** `app/Providers/RouteServiceProvider.php`, `routes/api.php` (sin cambios en su contenido)
**Comportamiento actual (antes):** único punto de entrada `/api/...`; cualquier cambio de forma de respuesta rompería el contrato con el móvil sin posibilidad de coexistencia entre versión vieja y nueva.
**Comportamiento esperado:** disponer de `/api/v1/...` como prefijo versionado, de modo que futuros cambios de forma de respuesta puedan introducirse ahí sin tocar `/api/...`.
**Solución propuesta y aplicada:** montar `routes/api.php` una segunda vez en `RouteServiceProvider::boot()` bajo `->prefix('api/v1')->name('v1.')`, como alias aditivo (mismo archivo, sin duplicar definiciones). Se aisló el nombre de ruta (`v1.`) para evitar que las 2 rutas nombradas (`verarchivopdfporninio`, `descargarCartaPdf`, usadas por `route()` en `Api\ResponderCarta::listaCartas`) colisionaran entre ambos montajes — riesgo real detectado antes de implementar, no hipotético. Detalle completo en `docs/cambios/decisiones-arquitectura.md`.
**Impacto Web:** No aplica. **Impacto API:** ninguno sobre `/api/...` existente; se suma `/api/v1/...` como alias idéntico. **Impacto React Native:** ninguno — `CactuApp` no necesita cambiar `API_URL`.
**Fase sugerida:** Fase 5 — API + React Native.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`, Fase 5). Ver `docs/cambios/changelog.md` y `docs/cambios/decisiones-arquitectura.md`.

---

**ID:** PDF-001
**Módulo:** Infraestructura / PDF
**Problema:** `config/snappy.php` apunta a un binario de Windows en vez del binario Linux vendorizado
**Tipo:** ARQUITECTURA / INFRAESTRUCTURA
**Severidad:** ALTO (a confirmar contra la configuración real del servidor de producción, no versionada)
**Archivos afectados:** `config/snappy.php`
**Comportamiento actual:** `'binary' => '"C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf"'` está activo; la línea que usa `base_path('vendor/h4cc/wkhtmltopdf-amd64/...')` (compatible con cPanel Linux) está comentada.
**Comportamiento esperado:** El binario debe resolverse según el entorno (`env('WKHTMLTOPDF_BINARY', base_path('vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64'))`), no hardcodeado a una ruta de Windows.
**Riesgo:** Si el `.env`/config real de producción no sobreescribe este valor, toda generación de PDF (núcleo del módulo Cartas) fallaría en cPanel. Dado que el sistema está reportado como funcional, es posible que exista una configuración local no versionada — **se requiere confirmar contra el servidor real antes de tocar esto**.
**Fase sugerida:** Fase 6 — PDF + Email + Jobs (verificar con prioridad antes, dado el riesgo de romper una función crítica).
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`, Fase 6). Verificado contra el servidor real por el usuario (acceso File Manager de cPanel, no SSH): confirmó que `config/snappy.php` en **producción** ya tiene la línea `'binary' => base_path('vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64')` activa (no la ruta de Windows), que no hay ninguna variable `WKHTMLTOPDF`/`SNAPPY`/`PDF_BINARY` en el `.env` de producción, que `vendor/h4cc/wkhtmltopdf-amd64/bin/` sí existe con el binario, y que el módulo Cartas genera PDF sin error hoy en `cactu.ec`. Conclusión: producción tenía un `config/snappy.php` editado manualmente y nunca commiteado — el archivo del repo había divergido silenciosamente. Se corrige el repo para que coincida con lo ya probado en producción (`base_path('vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64')`, sin `env()` de por medio — no hacía falta, nada lo sobreescribía). Se aplicó el mismo fix al binario de `wkhtmltoimage` (`'image' =>`, mismo patrón de bug, código no usado actualmente en la app pero corregido por consistencia). Verificado localmente con `PDF::loadHTML(...)->output()` vía tinker: genera un PDF real (`Creator: wkhtmltopdf 0.12.4`), y `tests/Feature/Seguridad/DescargaPdfCartaTest` sigue en verde. **No se usó `env('WKHTMLTOPDF_BINARY', ...)`** como sugería la ficha original — se prefirió el valor fijo verificado en producción, evitando una capa de configuración que nadie usa (`CLAUDE.md` sección 56).

---

**ID:** PERF-001
**Módulo:** Notificaciones
**Problema:** Notificaciones enviadas de forma síncrona pese a importar `ShouldQueue`
**Tipo:** PERFORMANCE
**Severidad:** MEDIO
**Archivos afectados:** `app/Notifications/EnviarNotificacionCartaNueva.php`, `app/Notifications/EnviarCartaRespondidaGestor.php`
**Comportamiento actual:** El envío de correo/Firebase bloquea la respuesta HTTP de `CartaController::store` y de los métodos `guardarArchivo`/`guardarCarta`.
**Comportamiento esperado:** Implementar `ShouldQueue` real y despachar mediante `Queue`/`Job`, aprovechando que `QUEUE_CONNECTION` puede pasar de `sync` a `database` sin necesitar Redis/Supervisor (compatible con cPanel vía cron, ver `infraestructura-actual.md`).
**Fase sugerida:** Fase 6 — PDF + Email + Jobs.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`, Fase 6). Ambas notificaciones ahora declaran `implements ShouldQueue` (antes solo importaban la interfaz sin implementarla). Se publicó la migración `jobs` (faltaba; solo existía `failed_jobs`) y `QUEUE_CONNECTION` pasa de `sync` a `database` en `.env.example`. `app/Console/Kernel.php` registra `queue:work --stop-when-empty` cada minuto (`withoutOverlapping`), el patrón cron-friendly ya documentado en `infraestructura-actual.md` para hosting sin Supervisor/Redis. Test de regresión: `tests/Unit/Notifications/NotificacionesShouldQueueTest.php`. El `.env` local de desarrollo se dejó en `sync` deliberadamente (no se tocó) para no exigir un `queue:work` corriendo en paralelo durante las pruebas manuales con el móvil. Ver `docs/cambios/changelog.md` y `docs/cambios/decisiones-arquitectura.md`. `PDF-001` se resolvió por separado (ver ficha propia); "mover la generación de PDF a un Job" del roadmap original no aplica tal como está descrito — los 3 usos de `PDF::loadView` son endpoints GET donde la respuesta HTTP es el propio PDF (vista inline/descarga), no hay nada que job-ificar en segundo plano ahí. La limpieza programada de `storage/app/temp` (último ítem de Fase 6) se resolvió aparte — ver `INFRA-001` más abajo. Con esto, Fase 6 queda cerrada en su alcance real.

---

**ID:** INFRA-001
**Módulo:** Infraestructura / Cartas (respuesta web)
**Problema:** `storage/app/temp` puede acumular archivos huérfanos sin ningún mecanismo de barrido periódico
**Tipo:** INFRAESTRUCTURA
**Severidad:** BAJO
**Archivos afectados:** `app/Http/Controllers/ResponderCartaController.php` (origen de los archivos), `app/Console/Kernel.php`, `app/Console/Commands/LimpiarArchivosTemporalesCommand.php`
**Comportamiento actual (antes de esta corrección):** `ResponderCartaController::crearArchivoTemporalDesdeBase64` escribe cada foto en base64 (subida desde el formulario web de respuesta de carta) a `storage/app/temp`, y la borra en un `finally` apenas la Action correspondiente termina de usarla. Eso cubre el caso normal, pero si el proceso PHP muere antes de llegar al `finally` — cliente que se desconecta a mitad de request (comportamiento por defecto de PHP: aborta el script si detecta que el cliente cortó la conexión), timeout de ejecución, u OOM del hosting — el archivo queda huérfano y nada lo vuelve a tocar. `app/Console/Kernel.php::schedule()` no tenía ninguna tarea de limpieza.
**Riesgo:** en cPanel compartido con cuota de disco limitada, acumulación silenciosa hasta agotar la cuota — lo que además rompe `storage/logs`, la caché de archivos y las sesiones (`SESSION_DRIVER=file`), todas en el mismo disco/cuota, no solo la subida de fotos.
**Comportamiento esperado:** una tarea programada que borre periódicamente los archivos de `storage/app/temp` más viejos que un umbral razonable, como red de seguridad independiente del `try/finally` normal.
**Fase sugerida:** Fase 6 — PDF + Email + Jobs.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`, Fase 6). Se crea el comando `php artisan temp:limpiar {--horas=24}`, que borra de `storage/app/temp` los archivos con `Storage::lastModified()` anterior al umbral (por defecto 24 horas — muy por encima de los milisegundos que tarda el borrado normal, para no arriesgar tocar un archivo en uso). Registrado en `app/Console/Kernel.php` vía `$schedule->command('temp:limpiar')->daily()->withoutOverlapping()`, aprovechando el mismo cron de `schedule:run` ya requerido para las colas (`PERF-001`). Test de regresión: `tests/Feature/Console/LimpiarArchivosTemporalesCommandTest.php` (verifica que borra solo lo viejo, no lo reciente, y que no falla si la carpeta no existe). Ver `docs/cambios/changelog.md` y `docs/cambios/decisiones-arquitectura.md`.

---

**ID:** TEST-001
**Módulo:** Global
**Problema:** Cobertura de pruebas desigual entre módulos
**Tipo:** DEUDA TÉCNICA
**Severidad:** MEDIO (bajada desde ALTO — el riesgo original ya no aplica, ver actualización 2026-08-08)
**Archivos afectados:** ninguno de dominio hoy (el boilerplate `tests/Feature/ExampleTest.php`/`tests/Unit/ExampleTest.php` mencionado originalmente se eliminó, ver changelog).
**Comportamiento actual (actualizado 2026-08-08):** la afirmación original ("ausencia total de pruebas... ni de autorización, ni de flujo de cartas, ni de endpoints API") ya no es cierta — la suite tiene 89 tests en 22 archivos, con cobertura sólida en exactamente esas tres áreas: autorización (`CartaAutorizacionTest`, `NiniosAutorizacionTest`, `UsuariosAutorizacionTest`, `ResponderCartaSesionTest`, `ResponderCartaAutorizacionTest`), flujo de cartas (`RegistrarCartaTest`, `ResponderCartaWebTest`, `ResponderCartaTest` API, `ResponderCartaAccionesTest`), y endpoints API (`ListarMisCartasTest`, `TokenFcmTest`, `VersionadoApiV1Test`, `DescargaPdfCartaTest`), además de notificaciones Firebase y el comando de limpieza de temporales. La brecha real que queda, más acotada que la original: sin ningún test —
- Módulo `comunidad` completo (ni CRUD ni autorización propia ni el filtro de `UX-009`).
- CRUD de `ninios`/`usuarios` (crear/editar/eliminar; solo está probada la autorización de acceso, no las reglas de validación/negocio del formulario).
- `HomeController` (KPIs de `UX-008`) — verificado manualmente vía `tinker` al implementarlo, sin test de regresión.
- Los filtros de `ninios`/`usuarios`/`comunidad` de `UX-009` — mismo caso, verificados manualmente, sin test de regresión.
- `PerfilController` y la importación de niños (`ninios-importar`).
**Riesgo:** menor que el original (los flujos de mayor sensibilidad — autorización, cartas, API — ya están cubiertos), pero cambios futuros en `comunidad`, en el CRUD de `ninios`/`usuarios`, o en los KPIs/filtros agregados hoy, pueden romperse sin que la suite lo detecte.
**Fase sugerida:** Fase 8 — Performance + Calidad. Priorizar `comunidad` (cero cobertura) y los KPIs/filtros de `UX-008`/`UX-009` (cambios recientes, sin red de seguridad todavía).

---

**ID:** UX-001
**Módulo:** Cartas
**Problema:** Coexistencia de plantillas de PDF "v1" y "v2" sin retirar las antiguas
**Tipo:** DEUDA TÉCNICA / UX-UI
**Severidad:** BAJO
**Archivos afectados:** `resources/views/cartas/pdf-*.blade.php` vs `resources/views/cartas/v2/pdf-*.blade.php`, `CartaController::verPDF` (parámetro `$version`)
**Comportamiento actual:** `verPDF` decide entre v1 y v2 mediante un parámetro de ruta opcional; no está documentado cuándo debe usarse cada una ni hay plan de retiro de la v1.
**Solución propuesta:** Documentar el criterio de uso o completar la migración a v2 y retirar v1.
**Fase sugerida:** Fase 3 — UX/UI.
**Actualización (2026-08-08, auditoría `docs/auditoria/ux-ui-actual.md`, sección 6):** confirmado en código, con precisión adicional — v2 **no está conectado a ningún flujo de producción real**. `CartaController::descargarPdf()` (descarga oficial) y `WelcomeController::descargarCartaPdf()` (enlace público que reciben los patrocinadores) usan v1 exclusivamente; v2 solo es alcanzable desde `cartas/show.blade.php`, que la muestra lado a lado con v1 en dos iframes rotulados "(v1)"/"(v2)" como comparación interna. Además, `cartas/v2/pdf-presentacion-fixed.blade.php` es código muerto: no está referenciado por ningún controller ni `@include`.
**Decisión del usuario (2026-08-08):** retirar "v2" — el cliente no la aprobó. CACTU se queda solo con la plantilla v1.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Se eliminó `resources/views/cartas/v2/` completo (6 archivos). `CartaController::verPDF()` pierde el parámetro `$version` y la rama condicional — siempre usa `cartas.verpdf`. Ruta `cartas-ver-carta-pdf/{id}/{version?}` simplificada a `cartas-ver-carta-pdf/{id}`. `cartas/show.blade.php` pasa de dos columnas (v1/v2 lado a lado) a una sola columna con el PDF, sin rótulo de versión. Verificado: `php artisan view:cache` sin errores, endpoint del PDF probado contra una carta real (200, `application/pdf`), vista `show` renderizada directamente confirmando un solo iframe y ausencia de rótulos "(v1)"/"(v2)", suite completa sin regresiones nuevas (mismos 16 fallos preexistentes de `TEST-002`, confirmado que la lista no cambió). Detalle en `docs/cambios/changelog.md`.

---

**ID:** UX-002
**Módulo:** Global (layouts)
**Problema:** Duplicación completa del `<head>` del layout entre `layouts/app.blade.php` (panel) y `responder-cartas/principal.blade.php` (flujo público sin autenticación), en vez de una estructura compartida
**Tipo:** ARQUITECTURA / UX-UI
**Severidad:** BAJO
**Archivos afectados:** `resources/views/layouts/app.blade.php`, `resources/views/responder-cartas/principal.blade.php`
**Comportamiento actual:** ambos archivos copian ~90 líneas casi idénticas (metadatos, hojas de estilo, scripts core, configuración de `jquery-validate`/`jquery-confirm`/CSRF) en vez de heredar de una base común. Ya causó una divergencia real: `principal.blade.php` carga jQuery desde un CDN público (`code.jquery.com`) en vez del archivo local que usa el resto del sistema.
**Solución propuesta:** extraer el `<head>` compartido a un partial reutilizable (ej. `sections/head.blade.php`), incluido por ambos layouts; cada uno conserva su propio `<body>` (panel con sidebar/navbar vs. tarjeta centrada pública). De paso, corregir el jQuery del CDN por el archivo local.
**Impacto Web:** cambio de estructura de layout, sin cambio de contenido visual esperado (salvo la fuente de jQuery, que pasa a ser local). **Impacto API:** ninguno. **Impacto móvil:** ninguno.
**Fase sugerida:** Fase 3 — UX/UI. **Primer paso autorizado por el usuario el 2026-08-08** (ver `docs/cambios/decisiones-arquitectura.md`).
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Se extrajo `resources/views/sections/head.blade.php`, incluido ahora por ambos layouts. Verificado con `php artisan view:cache` (compila sin errores) y contra el servidor local real (`/login` y `/cartas-ninio/{numero_child}`, confirmando en ambos casos que `jquery.min.js` se sirve local). No se probó de forma interactiva en navegador (sin herramienta de automatización disponible en este entorno) — pendiente de verificación manual del usuario si lo considera necesario. Detalle en `docs/cambios/changelog.md`.

---

**ID:** UX-003
**Módulo:** Global (ninios, usuarios, comunidad, mis-ninios, cartas)
**Problema:** El dropdown de "acciones por fila" de cada listado está duplicado casi carácter por carácter en 4 archivos, sin extraer a un componente reutilizable
**Tipo:** ARQUITECTURA / DEUDA TÉCNICA
**Severidad:** BAJO
**Archivos afectados:** `resources/views/{ninios,usuarios,comunidad,mis-ninios}/action.blade.php` (esqueleto idéntico); `cartas/action.blade.php` lo extiende con más opciones
**Comportamiento actual:** no existe ningún Blade Component (`<x-...>`) en todo el proyecto — `app/View/Components` no existe. Toda la reutilización se hace con `@include`, y este patrón específico no tiene ningún punto único de edición.
**Solución propuesta:** extraer un Blade Component (ej. `<x-tabla-acciones>`) con slot para acciones extra (como las que agrega `cartas/action.blade.php`), cuando se toque alguno de estos módulos. No es urgente por sí solo.
**Fase sugerida:** Fase 3 — UX/UI (oportunista, junto con otro cambio en esos módulos).
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Se crea `resources/views/components/tabla-acciones.blade.php` (componente Blade anónimo, primero de todo el proyecto — `@props(['editRoute', 'deleteRoute', 'deleteMsg'])`) con el markup exacto que compartían los 4 archivos. `ninios/action.blade.php`, `usuarios/action.blade.php`, `comunidad/action.blade.php` y `mis-ninios/action.blade.php` pasan a ser un `<x-tabla-acciones ... />` de 5 líneas cada uno. **`cartas/action.blade.php` queda fuera a propósito**: tiene contenido genuinamente distinto (Ver, Descargar PDF, Documentos, popover de información con ~150 líneas de CSS propio) — forzarlo en el mismo componente con slots habría agregado complejidad para un solo caso de uso real, sin necesidad concreta (`CLAUDE.md` sección 56). Cero cambios en las clases `DataTable` de PHP — siguen invocando la misma vista con la misma variable (`$n`, `$user`, `$comunidad`). Verificado: `php artisan view:cache` sin errores, las 4 vistas renderizadas directamente contra datos reales producen HTML idéntico al anterior (mismas rutas, mismo `data-msg`, mismos íconos), suite completa 75/75 en verde, base real sin cambios propios (el único incremento de filas observado durante la verificación fue tráfico real de un usuario del sistema en vivo, no de esta sesión). Detalle en `docs/cambios/changelog.md`.

---

**ID:** UX-004
**Módulo:** Mis Niños
**Problema:** `mis-ninios/index.blade.php` usa un patrón de listado distinto al resto del sistema
**Tipo:** ARQUITECTURA / UX-UI
**Severidad:** BAJO
**Archivos afectados:** `resources/views/mis-ninios/index.blade.php`
**Comportamiento actual:** los otros 4 listados (`cartas`, `ninios`, `usuarios`, `comunidad`) usan Yajra DataTables server-side (`$dataTable->table()`/`scripts()`); `mis-ninios` usa una tabla HTML cruda con DataTables inicializado client-side en JS, y hardcodea ~20 líneas del diccionario de idioma español inline en vez de reutilizar una configuración compartida.
**Solución propuesta:** migrar a una `MisNinioDataTable` (Yajra) siguiendo el patrón de `NinioDataTable`, cuando se toque este módulo.
**Fase sugerida:** Fase 3 — UX/UI (oportunista).
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Se crea `app/DataTables/MisNinioDataTable.php` siguiendo exactamente el patrón de `NinioDataTable` (misma estructura de columnas, mismo `editColumn`/`filterColumn` de comunidad, reutiliza el componente `<x-tabla-acciones>` de `UX-003` vía `mis-ninios/action.blade.php`). El alcance por gestor —antes resuelto con `Auth::user()->ninios` (relación `HasManyThrough` en `User`)— se replica en `query()` con `Ninio::whereHas('comunidad', fn($q) => $q->where('user_id', Auth::id()))`; se verificó que ambos enfoques devuelven exactamente el mismo conjunto de IDs contra un gestor real (353 niños, coincidencia exacta). `MisNiniosController::index()` pasa de construir la vista a mano a `$dataTable->render('mis-ninios.index')` (mismo patrón que `NinioController::index()`). `mis-ninios/index.blade.php` pierde la tabla HTML cruda, el idioma de DataTables hardcodeado inline (~20 líneas) y el `<script>` de inicialización client-side; queda idéntica en estructura a `ninios/index.blade.php`. Verificado: `php artisan view:cache` sin errores, comparación directa de `query()` contra la relación vieja (coincidencia exacta), simulación de la petición AJAX real de DataTables (353 `recordsTotal`, paginación correcta, nombre de comunidad resuelto, columna de acciones con las rutas correctas de `mis-ninios`), suite completa 75/75 en verde, base real sin cambios propios. Detalle en `docs/cambios/changelog.md`.

---

**ID:** UX-005
**Módulo:** Responder Cartas (flujo público)
**Problema:** El flujo público de responder cartas (el que usan los patrocinadores y niños/familias, sin autenticación) tiene visiblemente menos pulido visual que el panel administrativo
**Tipo:** UX-UI
**Severidad:** BAJO
**Archivos afectados:** `resources/views/responder-cartas/{agradecimineto,contestacion,iniciada,presentacion-mayores,presentacion-menores}.blade.php`
**Comportamiento actual:** HTML sin estructurar en tarjetas (`<h1>`/`<h3>` sueltos), `<label>` no asociados al patrón `form-floating` que sí usa el panel administrativo, y una clase CSS inexistente (`class="form-control input "`, sin efecto) heredada del origen del archivo.
**Solución propuesta:** alinear visualmente estas vistas al patrón de formulario ya maduro del panel (sección 5.3 de `docs/auditoria/ux-ui-actual.md`), sin tocar la lógica de negocio (captura de foto, validación, confirmación de envío).
**Fase sugerida:** Fase 3 — UX/UI. Es el módulo con mayor impacto real (lo ve el usuario final, no el staff), candidato fuerte a próximo paso tras UX-002.
**Ajuste de criterio al implementar:** se usó `form-label` estándar de Bootstrap en vez de `form-floating` (que sí se usa en el panel admin) — varias etiquetas de estos formularios son oraciones largas ("Cuéntale por qué quisieras que te conteste tu patrocinador, envíale un mensaje de despedida"), y `form-floating` está pensado para etiquetas cortas tipo "Email"; forzarlo aquí habría desbordado o se habría visto mal. Se prioriza la herramienta correcta para el contenido real sobre la coincidencia mecánica con el panel.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Los 5 archivos se envuelven en `card`/`card-header`/`card-body`/`card-footer` (patrón ya usado en todo el panel admin), se quita la clase CSS muerta `input` de todos los `form-control`, y cada campo pasa a `<div class="mb-3"><label class="form-label">...</label><input class="form-control">...</div>` consistente. **Cero cambios de comportamiento**: mismos `name`/`id` de campo, mismas rutas, mismo JS de captura de cámara (`jslib-html5-camera-photo`), mismo `jquery.validate`/`jquery-confirm`. Se preservó intencionalmente el typo histórico `mifamila` (documentado en `ResponderCartaPresentacionMenorRequest` como contrato compartido con `CactuApp`) y la diferencia real entre archivos: `contestacion.blade.php` es el único con el iframe del PDF del patrocinador activo (los otros dos lo tienen comentado, sin tocar). Verificado: los 5 archivos compilan (`view:cache`), el conjunto de campos (`name=`) de cada vista coincide exactamente con las reglas de validación de su `FormRequest` correspondiente (`ResponderCarta*Request`), render directo contra cartas reales existentes de cada tipo sin errores, y una verificación HTTP de punta a punta contra un niño real (enlace cifrado real, solo lectura) confirmando 2 tarjetas, 6 `form-label`, jQuery local y cero apariciones de la clase muerta `input`. Suite completa 75/75 en verde, base de datos real sin cambios (solo lecturas). Detalle en `docs/cambios/changelog.md`.

---

**ID:** DOC-001
**Módulo:** Global
**Problema:** Sin documentación de arquitectura, API ni integración móvil previa a esta auditoría
**Tipo:** DOCUMENTACIÓN
**Severidad:** MEDIO
**Solución propuesta:** Este mismo directorio `docs/` es el punto de partida; continuar completando `docs/api/` y `docs/movil/` en fases posteriores conforme se confirme el contrato con el repositorio React Native.
**Fase sugerida:** Transversal.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`, Fase 5). `docs/movil/` (10 archivos) ya estaba completo desde la auditoría cruzada Fase 0.1, verificado contra `CactuApp` real. Se crea `docs/api/` (`README.md`, `autenticacion.md`, `cartas.md`, `documentos.md`) con el contrato formal desde el lado backend, formato `CLAUDE.md` sección 23, consolidando lo ya reunido en `docs/auditoria/matriz-api-movil.md`/`api-consumida-por-movil.md`.

---

**ID:** SEG-013
**Módulo:** API (autenticación)
**Problema:** el `catch (\Throwable $th)` del closure `POST /login` en `routes/api.php` devuelve `response()->json($th->getMessage())` — expone el mensaje interno de cualquier excepción (nombres de tabla/columna, detalle de conexión a BD) directo al cliente, con HTTP `200` en vez de un código de error
**Tipo:** SEGURIDAD
**Severidad:** MEDIO (mismo patrón que `SEG-008`, no incluido en su cierre porque `SEG-008` solo revisó Controllers y este es un closure inline en el archivo de rutas — se detectó al documentar `docs/api/autenticacion.md` para Fase 5, no es un hallazgo nuevo de la auditoría original)
**Archivos afectados:** `routes/api.php` (closure de `/login`)
**Comportamiento actual:** cualquier excepción no prevista durante el login (p. ej. fallo de conexión a BD) se devuelve tal cual al cliente como string plano, con código `200`.
**Comportamiento esperado:** igual que `SEG-008` — `Log::error()` con el detalle real, respuesta genérica al cliente, código HTTP de error apropiado (`500`).
**Solución propuesta:** aplicar el mismo patrón de `SEG-008` a este closure (o migrarlo a un método de Controller, ya que un closure inline no es el lugar natural para lógica de autenticación con manejo de errores — evaluar junto con la introducción de API Resources, ya que también toca la forma de la respuesta de este mismo endpoint).
**Impacto Web:** No aplica. **Impacto API:** Medio — filtra detalle interno en un escenario de error real de BD/infraestructura, poco frecuente pero posible en producción. **Impacto React Native:** ninguno funcional (la app no distingue este caso hoy, ver `docs/movil/autenticacion.md`), pero si se cambia el código HTTP de `200` a `500` en este `catch`, confirmar que `Login.js` no asuma siempre `200` en su manejo de la promesa.
**Fase sugerida:** Fase 5 — API + React Native (junto con la evaluación de API Resources, ver `docs/auditoria/evaluacion-api-resources.md`).
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Se movió `$request->validate(...)` fuera del `try/catch` (así Laravel maneja `ValidationException` de forma estándar — esto además corrigió un segundo bug real detectado al verificar: la validación fallida devolvía HTTP `200` con un string plano en vez del `422` estándar, confirmado con `php artisan tinker` antes de tocar el código). El `catch(\Throwable)` ahora registra `Log::error()` y responde un mensaje genérico, sin cambiar el código HTTP (mismo alcance que `SEG-008`, no se tocó por no estar autorizada la verificación de impacto en `Login.js`). Ver `docs/cambios/changelog.md` y `tests/Feature/Seguridad/LoginTest.php`.

---

**ID:** ARQ-006
**Módulo:** API (todas las respuestas JSON)
**Problema:** los 12 endpoints de la API no comparten un envelope de respuesta común — mezcla real de claves `success`/`error`/`info`/`errors` según el endpoint, y 3 casos confirmados donde un error se devuelve con HTTP `200` en vez de un código `4xx/5xx` (`POST /login`, `procesarRespuesta`, `WelcomeController::pdfninio`)
**Tipo:** ARQUITECTURA
**Severidad:** BAJO (sin impacto de seguridad propio — el de `/login` está cubierto aparte por `SEG-013` —, pero dificulta mantenibilidad y es el bloqueo real para "estandarizar respuestas JSON" que pide el roadmap)
**Archivos afectados:** `routes/api.php`, `app/Http/Controllers/Api/ResponderCarta.php`, `app/Http/Controllers/WelcomeController.php`
**Comportamiento actual:** cada endpoint arma su respuesta JSON a mano, sin una clase o helper compartido.
**Comportamiento esperado:** ver `docs/auditoria/evaluacion-api-resources.md` — recomienda separar en dos piezas: (A) una `JsonResource` solo para `listar-mis-cartas` (único endpoint con forma "de modelo"), de bajo riesgo y sin cambio de contrato; (B) un envelope común para los 8 endpoints de escritura, que **requiere verificar antes contra el código real de `CactuApp`** si alguna pantalla compara claves/strings exactos, antes de decidir si se introduce bajo `/api/v1/` (alias ya disponible, `ARQ-005`) o se coordina una migración con el equipo móvil.
**Solución propuesta:** la del documento de evaluación, por pasos (A/B/C), no una reescritura de las 12 respuestas de una vez.
**Impacto Web:** No aplica. **Impacto API:** Alto si se hace mal (cambia contrato de 8 endpoints activos); Bajo si se sigue el Paso A primero. **Impacto React Native:** Alto para el Paso B — requiere confirmar contra `CactuApp` real antes de tocar nada.
**Fase sugerida:** Fase 5 — API + React Native.
**Estado:** **Resuelto parcialmente — Paso A implementado, Paso B verificado y NO recomendado** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Paso A: se crea `app/Http/Resources/CartaListaResource.php` y `Api\ResponderCarta::listaCartas` delega en `CartaListaResource::collection($cartas)` en vez de armar el array a mano. Forma de salida verificada como idéntica campo por campo (incluido el caso con PDF adjunto/respondida) con `tests/Feature/Api/ListarMisCartasTest.php`, escrito y verificado en verde **antes** del refactor y de nuevo después, sin diferencia. Paso B: se abrió `/home/persontech/workspace/CactuApp` (rama `main`) y se leyeron las 6 pantallas afectadas — un envelope común rompería la distinción real de comportamiento que el cliente ya implementa (`success` navega, `info` a veces navega hacia atrás y a veces no, `error` no se revisa en 3 de las 6 pantallas, `Login.js` compara `data.message === 'ok'` literal). Conclusión: no es un cambio seguro de solo-backend, requeriría coordinar un release doble con el equipo móvil. Se deja sin implementar. Hallazgo adicional detectado en el camino: `#18` en `docs/movil/cambios-requeridos.md` (3 pantallas nunca manejan el 403, bug preexistente del móvil, fuera de este repositorio). Ver `docs/auditoria/evaluacion-api-resources.md`, `docs/movil/manejo-errores.md` y `docs/cambios/decisiones-arquitectura.md`.

---

**ID:** SEG-014
**Módulo:** API (autenticación / Firebase)
**Problema:** el closure `POST /login` en `routes/api.php` asignaba `$ninio->fcm_token = $request->fcm_token` sin condición — un login cuyo body no incluyera `fcm_token` sobrescribía el campo con `null`, borrando un token de push válido ya guardado
**Tipo:** BUG
**Severidad:** MEDIO (pérdida silenciosa de capacidad de notificar al niño; no es una falla de seguridad, sino de negocio/UX — el niño deja de recibir notificaciones de cartas nuevas hasta el próximo login que sí incluya el token)
**Archivos afectados:** `routes/api.php` (closure de `/login`)
**Comportamiento actual (antes):** cualquier login sin `fcm_token` en el body (p. ej. reintento de red del cliente, o un futuro consumidor de la API que no conozca este campo) borraba el token existente.
**Comportamiento esperado:** el login solo debe actualizar `fcm_token` cuando el cliente efectivamente lo envía; si no viene, debe conservar el valor guardado.
**Cómo se detectó:** al auditar el estado real de la Fase 7 del roadmap (Firebase + Notificaciones) contra el código, antes de autorizar esa fase — no es un hallazgo de la auditoría original de Fase 0.
**Solución propuesta y aplicada:** condicionar la asignación a `$request->filled('fcm_token')`, y validar el campo como `nullable|string` en el `$request->validate()` existente (antes no se validaba en absoluto).
**Impacto Web:** No aplica. **Impacto API:** Medio — corrige pérdida de dato real, sin cambiar la forma de la respuesta ni el código HTTP. **Impacto React Native:** Ninguno funcional — la app siempre reenvía el valor cacheado de `AsyncStorage['fcmtoken']`, así que el bug rara vez se disparaba en el uso real de `CactuApp`; el riesgo era mayor de cara a futuro (nuevos clientes, reintentos manuales de login vía Postman/debug, etc.).
**Fase sugerida:** Fase 7 — Firebase + Notificaciones (paso 1).
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Ver `tests/Feature/Seguridad/LoginTest.php` (casos `test_login_sin_fcm_token_en_el_body_no_borra_el_token_existente` y `test_login_con_fcm_token_en_el_body_actualiza_el_token_existente`). De paso se agregaron dos endpoints nuevos, `POST /actualizar-token-fcm` y `POST /eliminar-token-fcm` (`auth:sanctum`), para que el token pueda actualizarse/eliminarse sin pasar por un login — cierra el punto 3 del roadmap de Fase 7 (`app/Http/Controllers/Api/TokenFcmController.php`, `tests/Feature/Api/TokenFcmTest.php`). Ver `docs/api/autenticacion.md`, `docs/movil/firebase.md`, `docs/movil/cambios-requeridos.md` (ítem #11) y `docs/cambios/changelog.md`.

---

**ID:** FIREBASE-001
**Módulo:** Firebase / Notificaciones push
**Problema:** el envío real de notificaciones Firebase (`EnviarNotificacionCartaNueva::toFirebase()`, vía `kutia-software-company/larafirebase` v1.3.8) usa la **API HTTP legacy de FCM** (`https://fcm.googleapis.com/fcm/send`, autenticada con `FIREBASE_SERVER_KEY`, `config/larafirebase.php:5`). Google decomisionó esa API: el envío de mensajes vía HTTP/XMPP legacy quedó deprecado el 20/06/2023 y las peticiones al endpoint empezaron a fallar a partir del 20-22/06/2024 (confirmado por búsqueda externa contra la documentación oficial de Firebase y anuncios de Google, no asumido de memoria — ver fuentes al pie de este hallazgo).
**Tipo:** BUG
**Severidad:** CRÍTICO — si el `FIREBASE_SERVER_KEY` de producción sigue siendo el de la API legacy (lo más probable, ya que el código nunca se actualizó a HTTP v1), **las notificaciones push llevan potencialmente más de 2 años completamente rotas en producción**, sin ningún registro de error visible: no hay try/catch alrededor del envío (ver también el punto 2 pendiente de Fase 7), así que una respuesta de error HTTP de Firebase (404/`UNREGISTERED`) se descarta en silencio — `FirebaseChannel::send()` (`vendor/kutia-software-company/larafirebase/src/Channels/FirebaseChannel.php:12-16`) ni siquiera revisa el resultado de `toFirebase()`.
**Archivos afectados:** `app/Notifications/EnviarNotificacionCartaNueva.php` (`toFirebase()`), `config/larafirebase.php`, paquete `kutia-software-company/larafirebase` (composer.lock, versión 1.3.8, sin soporte para HTTP v1 — confirmado contra el código del paquete en `vendor/`, no tiene ninguna clase que use OAuth2/service account).
**Comportamiento actual:** el niño nunca recibe la notificación push de "Nueva carta" (el canal `mail` sí sigue funcionando, es independiente). No hay forma de detectarlo desde los logs actuales sin instrumentar el envío.
**Comportamiento esperado:** las notificaciones deberían enviarse vía FCM HTTP v1 (autenticación por cuenta de servicio/OAuth2, no server key), el mecanismo soportado activamente por Firebase.
**Cómo se detectó:** al investigar el código real antes de implementar "manejo de tokens inválidos/expirados y reintentos" (punto 2 pendiente de Fase 7, autorizado por el usuario) — construir reintentos sobre un endpoint permanentemente decomisionado no tiene sentido; el hallazgo cambia el alcance real de ese punto del roadmap.
**Solución propuesta:** no implementada todavía, requiere decisión del usuario. Opciones evaluadas: (A) migrar a `kreait/firebase-php` (librería oficial-adyacente, mantenida, soporta HTTP v1 con credenciales de cuenta de servicio JSON); (B) migrar al sucesor del paquete actual, `gg-innovative/larafirebase`, que sí soporta HTTP v1 con una API similar a la actual (menor esfuerzo de reescritura); (C) implementar el envío HTTP v1 a mano. Cualquier opción requiere generar/obtener credenciales de cuenta de servicio de Firebase para el proyecto de CACTU (fuera del alcance de este repositorio, es una acción en la consola de Firebase) antes de escribir código.
**Impacto Web:** No aplica. **Impacto API:** No aplica directamente (no es un endpoint HTTP de CACTU). **Impacto React Native:** Alto pero silencioso — la app nunca recibió push reales si esta hipótesis se confirma; el usuario final no tiene ninguna señal de que algo esté roto, ni el equipo de negocio, salvo por reportes anecdóticos de "no me llegó la notificación".
**Verificación pendiente (no se pudo confirmar desde este repositorio):** no se probó un envío real contra las credenciales de producción (`FIREBASE_SERVER_KEY` de producción no disponible ni debe probarse sin autorización explícita, para no arriesgar un envío real a dispositivos de niños). Antes de invertir en la migración, confirmar con el usuario si hay evidencia externa (logs de Firebase Console, reportes de usuarios) de que las notificaciones dejaron de llegar, y desde cuándo.
**Fase sugerida:** Fase 7 — Firebase + Notificaciones (bloqueaba el punto 2 "manejo de tokens inválidos y reintentos" tal como estaba planteado — no tenía sentido reintentar contra un endpoint muerto).
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`, autorizado explícitamente por el usuario: opción "migrar ya a HTTP v1 con kreait/firebase-php"). Se retira `kutia-software-company/larafirebase` y se instala `kreait/laravel-firebase` (^5.10), que envía vía FCM HTTP v1 con credenciales de cuenta de servicio (`FIREBASE_CREDENTIALS`, ya no `FIREBASE_SERVER_KEY`). De paso queda resuelto también el punto 2 pendiente del roadmap de Fase 7 ("manejo de tokens inválidos/expirados y reintentos"):
- **Tokens inválidos:** `app/Services/FirebaseService.php` captura `Kreait\Firebase\Exception\Messaging\NotFound` (la excepción que el SDK lanza cuando el token ya no está registrado) y limpia `Ninio::fcm_token` sin relanzar — no tiene sentido reintentar contra un token muerto.
- **Reintentos:** `EnviarNotificacionCartaNueva` declara `$tries = 3` y `$backoff = 30` (propiedades que Laravel propaga automáticamente del `Notification` al job `SendQueuedNotifications` que lo envuelve). Cualquier error que no sea `NotFound` (red, cuota, servidor) se relanza desde `FirebaseService` y activa el reintento normal de la cola.
**Archivos:** `app/Services/FirebaseService.php` (nuevo), `app/Notifications/Channels/FirebaseChannel.php` (nuevo, reemplaza el canal que aportaba el paquete retirado), `app/Providers/AppServiceProvider.php` (registra el canal `firebase`), `app/Notifications/EnviarNotificacionCartaNueva.php`, `app/Http/Controllers/HomeController.php` (se limpia el import muerto de `Kutia\Larafirebase\Facades\Larafirebase` y el código comentado que lo usaba), `config/firebase.php` (nuevo, publicado del paquete), `config/larafirebase.php` (eliminado), `.env.example`, `.gitignore` (`/storage/app/firebase` no versionado), `composer.json`/`composer.lock`.
**Pruebas:** `tests/Feature/Notifications/EnviarNotificacionFirebaseTest.php` (nuevo, 5 casos: envío correcto, sin token no intenta enviar, token no registrado se limpia sin relanzar, error transitorio se propaga sin tocar el token, `$tries`/`$backoff` configurados) — mockeando `Kreait\Firebase\Contract\Messaging` (bindable directo en el contenedor), sin depender de credenciales reales. Suite completa: 68/68 en verde.
**Verificación end-to-end (2026-08-08, entorno local):** el usuario colocó las credenciales reales de cuenta de servicio en `storage/app/firebase/service-account.json` y las activó en `.env` (`FIREBASE_CREDENTIALS`). Se verificó en 3 pasos, cada uno más concluyente que el anterior:
1. `Messaging::validate()` (nunca entrega nada) con un token inventado → `InvalidMessage` — confirma que la autenticación con Google fue aceptada (un fallo de credenciales habría dado `AuthenticationError`, no un error de formato de token).
2. `Messaging::validate()` con un `fcm_token` real pero viejo (de una prueba anterior) → `NotFound` (`NotRegistered`) — confirma autenticación + conectividad real contra la API de Firebase, y de paso confirma que `FirebaseService` habría limpiado ese token si el envío hubiera pasado por ahí.
3. **Envío real (sin `validate_only`)** vía `FirebaseService::enviarNotificacion()` — el mismo código que usa `EnviarNotificacionCartaNueva` en producción — a un `fcm_token` fresco de un dispositivo con `CactuApp` real, obtenido haciendo login desde la app apuntando al backend local. **El usuario confirmó haber recibido la notificación "Prueba CACTU" en el teléfono.** Verificación end-to-end completa: credenciales → HTTP v1 → entrega real en el dispositivo.
**Pendiente real, fuera de este repositorio:** cargar las mismas credenciales de cuenta de servicio en el `.env` de producción (`storage/app/firebase/service-account.json` no se versiona por diseño — cada entorno necesita su propia copia). Sin esas credenciales, el envío fallará igual que antes de esta migración, con la diferencia de que ahora **sí** quedará registrado en `storage/logs` en vez de fallar en silencio, porque cualquier excepción que no sea `NotFound` se relanza.
**Fuentes externas consultadas (WebSearch, 2026-08-08):** [Migrate from legacy FCM APIs to HTTP v1 (Firebase docs oficiales)](https://firebase.google.com/docs/cloud-messaging/migrate-v1?authuser=3), [FCM API Deprecation (Microsoft Q&A, cita fechas de corte)](https://learn.microsoft.com/en-us/answers/questions/1321757/fcm-api-deprecation), [kutia-software-company/larafirebase — GitHub](https://github.com/kutia-software-company/larafirebase) (confirma que no soporta HTTP v1 y remite a `gg-innovative/larafirebase` como sucesor, no elegido — se prefirió `kreait/laravel-firebase` por ser la librería más madura/mantenida, según lo autorizado).

---

**ID:** FIREBASE-002
**Módulo:** Firebase / Notificaciones push
**Problema:** `Ninio.fcm_token` era una columna simple — un solo token por niño. Si el mismo `numero_child` se logueaba desde dos dispositivos, el último login "ganaba" y el otro dejaba de recibir notificaciones sin ningún aviso.
**Tipo:** ARQUITECTURA
**Severidad:** MEDIO — no es una falla de seguridad, es pérdida silenciosa de capacidad de notificar. Se confirmó que no era un caso hipotético: contra la base de datos real (`cactu`, producción de referencia local), **144 grupos de `fcm_token` duplicados entre 843 filas de `ninios`** — hermanos patrocinados que comparten un único teléfono familiar para loguearse, cada uno con su propio `numero_child`. El caso de negocio ya estaba ocurriendo, no era solo el objetivo aspiracional de `CLAUDE.md` sección 25.
**Archivos afectados:** `app/Models/Ninio.php`, `routes/api.php` (login), `app/Http/Controllers/Api/TokenFcmController.php`, `app/Services/FirebaseService.php`, `app/Notifications/EnviarNotificacionCartaNueva.php`, `app/Http/Controllers/CartaController.php`.
**Comportamiento actual (antes):** un solo `fcm_token` por `Ninio`; el segundo hermano logueado en el mismo teléfono pisaba silenciosamente el token del primero.
**Comportamiento esperado:** un `Ninio` puede tener N dispositivos, cada uno con su propio token; las notificaciones se envían a todos.
**Cómo se detectó:** antes de autorizar este punto (único abierto de Fase 7), se preguntó explícitamente al usuario si el negocio realmente necesitaba multi-dispositivo (para no sobrearquitecturar algo que nadie pidió, `CLAUDE.md` sección 56) y se inspeccionó el repo móvil (`CactuApp`) para confirmar que hoy no tiene ningún concepto de dispositivo (sin device id, sin `onTokenRefresh`, sin multi-sesión). El usuario confirmó que sí hace falta. Al diseñar la migración de datos se corrió una consulta contra la BD real que reveló los 144 grupos de tokens compartidos, validando el caso de negocio con evidencia concreta.
**Solución propuesta y aplicada:** tabla nueva `dispositivos` (`ninio_id` FK, `fcm_token` único, timestamps) — el `fcm_token` mismo es la clave del dispositivo, no se inventó un `device_id` porque `CactuApp` no provee ninguno. `App\Actions\RegistrarDispositivoAction` centraliza el upsert (`updateOrCreate` por `fcm_token`), usado desde login y `POST /actualizar-token-fcm`; reasigna automáticamente el dispositivo si el mismo teléfono ya estaba asociado a otro niño. `POST /eliminar-token-fcm` cambia de contrato: ahora exige `fcm_token` en el body para saber qué dispositivo puntual borrar (antes borraba "el único que había"); sin impacto real porque el endpoint no tiene consumidor móvil todavía. `FirebaseService::enviarNotificacion` itera todos los dispositivos del niño; un token rechazado (`NotFound`) borra solo ese dispositivo, no afecta a los demás.
**Columna vieja `ninios.fcm_token`:** se deprecia (deja de leerse/escribirse desde código nuevo, marcada con comentario en `Ninio::$fillable`) pero **no se dropea todavía** — decisión deliberada dado el entorno de producción real (cPanel compartido, deploy manual, sin blue/green, `CLAUDE.md` sección 50): mantenerla intacta permite revertir el despliegue por código sin ninguna migración `down()` de columna bajo presión. El drop queda para una migración separada, con autorización explícita nueva, tras un período de estabilidad en producción.
**Impacto Web:** ninguno. **Impacto API:** `GET /user` y `POST /login` no cambian de forma en ningún byte de su respuesta. `POST /eliminar-token-fcm` cambia de contrato (ahora exige `fcm_token` en el body), documentado para cuando el móvil lo consuma. **Impacto React Native:** ninguno inmediato — `CactuApp` no tiene consumidor de estos endpoints hoy; conectar `onTokenRefresh`/logout a ellos sigue siendo trabajo pendiente del repositorio móvil (`docs/movil/cambios-requeridos.md` #10-#12).
**Fase sugerida:** Fase 7 — Firebase + Notificaciones (punto 3, último pendiente). **Con esto, Fase 7 queda cerrada en su alcance real.**
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Migración de datos verificada: 2138 `Ninio` con `fcm_token` no nulo → 1439 dispositivos (valores distintos), coincide exacto con lo esperado. Tests: `tests/Feature/Seguridad/LoginTest.php` (caso nuevo de reasignación entre hermanos), `tests/Feature/Api/TokenFcmTest.php` (reescrito, incluye no-duplicación y reasignación), `tests/Feature/Notifications/EnviarNotificacionFirebaseTest.php` (reescrito, incluye envío a múltiples dispositivos y aislamiento de fallos). Suite completa: 75/75 tests en verde. Ver `docs/cambios/changelog.md` y `docs/cambios/decisiones-arquitectura.md`.

---

**ID:** TEST-002
**Módulo:** Global (infraestructura de configuración/testing)
**Problema:** `bootstrap/cache/config.php` (config cacheada, no trackeada en git) ignoraba las variables de entorno de `phpunit.xml`, causando que `php artisan test` corriera contra la base de datos real de desarrollo (`mysql`/`cactu`) en vez de la SQLite en memoria que la suite espera — y que `RefreshDatabase` ejecutara `migrate:fresh` (elimina y recrea todas las tablas) contra esa base real en cada proceso de test.
**Tipo:** BUG — **INCIDENTE DE PÉRDIDA DE DATOS**
**Severidad:** **CRÍTICO** (`CLAUDE.md` sección 44: pérdida de datos es prioridad máxima, por encima de arquitectura/UX). Los 16 fallos de tests eran solo el síntoma visible; el problema real es que **cada ejecución de `php artisan test` vaciaba la base de datos de desarrollo real del usuario**.
**Cómo se detectó:** al investigar por qué fallaban específicamente los tests que hacen `POST`/`PUT` (error 419 "CSRF token mismatch" — la detección de entorno de test de Laravel, `runningUnitTests()`, depende de que `app.env` resuelva a `testing`, y la config cacheada lo tenía fijado en `local`). Se confirmó forzando las variables de entorno a mano (`APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan config:show database.default`) que igual devolvía `mysql` — la caché ignoraba por completo las variables de `phpunit.xml`. Se confirmó el impacto real revisando la tabla `migrations`: las 14 migraciones estaban todas en el mismo `batch` (consistente con un `migrate:fresh` reciente, no con el historial incremental normal del proyecto), y los conteos de filas habían caído a valores mínimos (y finalmente a 0 en todas las tablas) tras las ejecuciones repetidas de `php artisan test` de esta sesión.
**Impacto real:** la base de datos de desarrollo local del usuario (`cactu`, descrita en hallazgos previos como "producción de referencia local") quedó completamente vacía — 0 usuarios, 0 niños, 0 cartas, 0 comunidades, 0 dispositivos — como efecto colateral de correr la suite de tests varias veces durante el cierre de `UX-002` y `UX-001`. **El usuario restauró un backup propio** y volvió a correr `php artisan migrate` (sin `:fresh`) para aplicar las 2 migraciones nuevas de Fase 7 (`jobs`, `dispositivos`) sobre los datos restaurados — la migración de `dispositivos` incluye el backfill de datos en su propio método `up()` (`migrarTokensExistentes()`), así que se reconstruyó sola desde `ninios.fcm_token` sin necesitar un script aparte. Verificado tras la restauración: 24 usuarios, 6527 niños, 6642 cartas, 28 comunidades, 2266 dispositivos, migraciones en 3 batches (historial real, no aplanado).
**Solución aplicada:** `php artisan config:clear` — elimina la config cacheada obsoleta. Verificado que, sin caché, `php artisan config:show database.default` respeta correctamente las variables de `phpunit.xml` (resuelve a `sqlite`). El archivo no estaba trackeado en git (`git ls-files` no lo lista), así que no fue necesario ningún cambio de código para esta parte — era un artefacto local desactualizado, no algo introducido por esta rama.
**Causa raíz de cómo se generó la config cacheada:** no determinada con certeza — el archivo (`bootstrap/cache/config.php`) tenía fecha 2026-08-08 20:12, anterior al inicio de esta sesión de Fase 3, y nadie en esta conversación ejecutó `php artisan config:cache`/`optimize`. Es un artefacto de una sesión o prueba anterior (posiblemente relacionada con preparar el entorno de cPanel/producción, donde sí corresponde cachear config) que quedó sin limpiar en el entorno local.
**Recomendación para que no se repita:** nunca dejar `bootstrap/cache/config.php` (ni `route.php`/`services.php`) cacheado en un entorno de desarrollo local donde también se corren tests — solo cachear config en el servidor de producción real, como parte de un despliegue explícito. Considerar agregar `/bootstrap/cache/*.php` a `.gitignore` si no está ya cubierto (hoy no lo está explícitamente, aunque el archivo tampoco estaba trackeado).
**Fase sugerida:** Transversal — resuelto de inmediato por ser crítico, no se esperó a una fase específica.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Causa raíz corregida (`config:clear`) y base de datos del usuario restaurada y verificada consistente. Detalle completo de la conversación con el usuario y la restauración en `docs/cambios/changelog.md`.

---

**ID:** SEG-015
**Módulo:** Responder Cartas (flujo público web)
**Problema:** el `numero_child` — única credencial para identificarse como un niño, tanto en la web como en la API móvil — viajaba en texto plano como parámetro de URL en `GET /cartas-ninio/{numero_child}`, sin límite de intentos, y los valores reales no son de alta entropía
**Tipo:** SEGURIDAD
**Severidad:** ALTO — explotable con evidencia concreta (no solo teórico), aunque comparte la raíz de una decisión de negocio ya aceptada (`SEG-012`)
**Origen del hallazgo:** pedido explícito del usuario ("si algún otro usuario tiene ese enlace puede responder intencionalmente"), investigado antes de proponer ninguna solución.
**Comportamiento actual (antes):**
- `ResponderCartaController::index($ninio_numero_child)` usaba el parámetro de ruta **sin cifrar** (`Ninio::where('numero_child', $ninio_numero_child)->firstOrFail()`) — de hecho tenía una línea de descifrado comentada (`// $ninio_decrypt = Crypt::decryptString(...)`), señal de que en algún momento se quitó esa protección.
- Cero `throttle` en ninguna ruta de `cartas-ninio.*`, ni en `POST /api/login` (el login de la app móvil, que resuelve exactamente el mismo tipo de credencial).
- Se verificó contra datos reales (solo lectura) que los valores de `numero_child` no son de alta entropía — son claramente secuenciales/estructurados (ej. `153055018, 153055022, 153055023, 153055024...`), un espacio de búsqueda chico y automatizable.
- La combinación (URL persistente y compartible + sin límite de intentos + valores adivinables) hacía posible enumerar `numero_child` reales y ver/responder cartas de otros niños.
**Comparación con la app móvil (corrigiendo una suposición inicial):** se investigó `routes/api.php` (`POST /login`) y se confirmó que la app móvil **usa exactamente la misma credencial débil** — `Ninio::where('numero_child', ...)->first()`, sin contraseña ni PIN (columna inexistente en la tabla `ninios`). Esto ya estaba documentado como `SEG-012` (informativo, decisión de negocio pendiente, fuera de alcance aquí). La diferencia real no es la fortaleza de la credencial (idéntica en ambos canales) sino la **exposición**: la app pide el número una vez por sesión sin dejarlo en ningún lugar visible; la web lo dejaba embebido en una URL de por vida, cacheable, reenviable, sin revocación posible.
**Bug funcional relacionado, encontrado al investigar (no se buscaba, apareció en el camino):** los 5 formularios de respuesta y el redirect de éxito de `procesarRespuesta()` generaban `route('cartas-ninio.index', Crypt::encryptString($ninio->numero_child))` — pasaban un valor **cifrado** a una ruta que esperaba el valor **plano**. Verificado con una petición HTTP real: `Ninio::where('numero_child', '<cifrado>')` nunca puede matchear, así que **toda familia que respondía una carta caía en un 404** en vez de ver la confirmación de envío. Bug preexistente, sin relación con este hallazgo de seguridad, pero en el mismo método que se estaba corrigiendo — se arregló en el mismo cambio.
**Solución implementada:**
1. **Rate limiting** (`RateLimiter::for('numero-child', ...)`, 5/min por IP, `RouteServiceProvider`): aplicado a `POST /cartas-ninio` (nuevo formulario de ingreso web) y a `POST /login` (API móvil, mismo problema).
2. **`index()` deja de aceptar `numero_child` como parámetro de URL.** Nuevo flujo: `GET /cartas-ninio` (formulario) → `POST /cartas-ninio` (`ingresar()`, valida y abre una ventana de sesión de 45 min, **no** la sesión HTTP completa) → `GET /cartas-ninio/mis-cartas` (`index()`, lee el niño de la sesión). El número ya no queda embebido en ninguna URL guardable.
3. `ver()` y el redirect de éxito de `procesarRespuesta()` **abren/renuevan la misma ventana de sesión** al validar la identidad (ya prueban la posesión del `numero_child` cifrado) — así que entrar por un link directo de notificación (sin pasar por el formulario) también deja navegar "mis cartas" sin pedir el número de nuevo. Esto de paso arregla el 404 del bug funcional (el redirect ya no lleva parámetro).
4. **No se tocó `SEG-012`**: `numero_child` sigue siendo la única credencial en ambos canales — esa decisión de negocio (agregar o no un segundo factor) sigue pendiente y fuera de este alcance, según indicó el usuario explícitamente.
**Archivos:** `app/Providers/RouteServiceProvider.php`, `routes/api.php`, `routes/web.php`, `app/Http/Controllers/ResponderCartaController.php`, `resources/views/responder-cartas/ingresar.blade.php` (nuevo), `resources/views/responder-cartas/principal.blade.php` (soporta render sin `$ninio` para la pantalla de ingreso), las 5 vistas de tipo de carta (enlaces "Regresar"/"Cancelar" corregidos), `tests/Feature/Seguridad/ResponderCartaSesionTest.php` (nuevo), `tests/Feature/Cartas/ResponderCartaWebTest.php` (test del redirect ampliado).
**Impacto Web:** el flujo público cambia de "un link con el número hace todo" a "ingresás el número una vez, navegás con sesión corta" — un paso adicional la primera vez, pero arregla el 404 posterior a responder. **Impacto API:** `POST /login` gana rate limiting; sin cambio de contrato (mismo request/response). **Impacto React Native:** ninguno — `CactuApp` no debería notar el rate limiting salvo en el caso extremo de más de 5 intentos de login por minuto desde la misma IP, comportamiento correcto y esperado.
**Pruebas:** verificado end-to-end contra el servidor local real con cookies reales (formulario → sesión → listado → apertura de carta), incluyendo el rate limiting (429 al superar el límite) y el caso de número inválido. El paso de "responder y confirmar" se verificó con `tests/Feature/Seguridad/ResponderCartaSesionTest.php` (7 casos nuevos: formulario accesible, sesión se abre/no se abre según validez, expiración, rate limiting) y una aserción nueva en `ResponderCartaWebTest.php` que seguía el redirect completo hasta 200 (antes daba 404) — no se completó por HTTP real porque el entorno sandbox no tiene salida SMTP hacia `mail.cactu.ec` (limitación del entorno, no del código; confirmado que la carta de prueba no quedó guardada a medias). Suite completa: 82/82 en verde. Base de datos real sin cambios propios (todos los datos de prueba creados durante la verificación se eliminaron).
**Fase sugerida:** Transversal, seguridad — resuelto de inmediato por severidad, con autorización explícita del usuario en cada paso (investigar primero, luego elegir el alcance de la mitigación).

**Parte 2 (mismo día, ampliación pedida por el usuario):** el cierre inicial dejaba un gap real — `ver()` (el link directo que manda `EnviarNotificacionCartaNueva` por notificación/correo) seguía funcionando **solo con el link cifrado**, sin exigir la sesión de `ingresar()`. El usuario lo señaló explícitamente: "ese enlace de la carta que se envía por correo se puede extraviar y puede quedar en caché [...] quería hacer una especie de login para esas cartas con el número del child [...] no presentar directamente esa carta por url encriptada". Se investigó y se confirmó que tenía razón: la app móvil no ofrecía nada "más seguro" que replicar (usa la misma credencial débil, `SEG-012`), pero el link cifrado web sí permitía que la sola posesión del link (sin demostrar independientemente conocer el `numero_child`) alcanzara para ver y **responder** la carta — justamente el escenario de "el correo se filtra/cachea/reenvía por error" que describió.
- **`ver()`** ahora exige además una sesión activa que coincida con el niño del link. Si no la hay (o es de otro niño), guarda la URL puntual como destino pendiente (`Session::put`) y redirige a `cartas-ninio.ingresar` — tras ingresar el número correcto, `ingresar()` redirige de vuelta **a esa carta específica**, no a la lista general (un solo paso extra, no dos).
- **`procesarRespuesta()`** (el método detrás de los 5 `guardarX()`, la acción que de verdad importa) gana el mismo chequeo de sesión, además del chequeo ya existente del `numero_child` cifrado en el formulario (`SEG-006`) — así, tener los campos cifrados capturados de un HTML viejo cacheado tampoco alcanza para responder sin la sesión.
- **`verArchivo()`** (foto/PDF adjunto del patrocinador) gana el mismo chequeo; al ser un recurso embebido (`<img>`/`<iframe>`), corta con `404` en vez de redirigir a una página de login que no tendría sentido ahí.
- Se evaluó agregar una contraseña/PIN nuevo en vez de esto, y se descartó: eso sería resolver `SEG-012` (decisión de negocio, dado que los niños patrocinados no manejan contraseñas), que el usuario no pidió ni autorizó tocar. La sesión corta cierra el problema real (el link ya no es autosuficiente) sin inventar una credencial nueva.
**Archivos (parte 2):** `app/Http/Controllers/ResponderCartaController.php` (`ver()`, `verArchivo()`, `procesarRespuesta()`, nueva constante `SESION_INTENDED`), `tests/Feature/Cartas/ResponderCartaWebTest.php` (3 tests nuevos: link sin sesión pide el número, vuelve directo a la carta tras ingresar, `verArchivo()` sin sesión da 404; 2 tests existentes actualizados para simular la sesión ya abierta).
**Pruebas (parte 2):** verificación HTTP real de punta a punta con una carta y niño desechables — se generó el link exacto que mandaría la notificación, se abrió sin sesión previa (cayó en el formulario, no en la carta), se ingresó el número real, y se confirmó que aterrizó **directo en esa carta puntual** (mismo URL de `ver()`, formulario de respuesta visible). Suite completa: 85/85 en verde (7 casos nuevos sobre el cierre anterior). Datos de prueba eliminados al terminar; base real sin cambios propios.
**Parte 3 (mismo día, el usuario señaló el gap):** "no olvides de cerrar sesión" — se había armado el "login" (`ingresar()`) pero no un "logout" explícito. En un dispositivo compartido (computadora de la comunidad, teléfono familiar), sin esto la próxima persona que entrara a `cartas-ninio` dentro de la ventana de 45 min vería las cartas del niño anterior sin volver a pedir el número. Se agregó `ResponderCartaController::salir()` (`POST /cartas-ninio/salir`), que limpia las 3 claves de sesión (`SESION_NINIO`, `SESION_NINIO_EXPIRA`, `SESION_INTENDED`) y redirige al formulario de ingreso; el botón "Cerrar sesión" se agregó a `responder-cartas/principal.blade.php` (visible en toda página donde ya hay un `$ninio` identificado — index, ver, y los 5 formularios de respuesta; ausente en el formulario de ingreso, donde todavía no hay sesión que cerrar).
**Archivos (parte 3):** `app/Http/Controllers/ResponderCartaController.php` (`salir()`), `routes/web.php`, `resources/views/responder-cartas/principal.blade.php`, `tests/Feature/Seguridad/ResponderCartaSesionTest.php` (2 tests nuevos).
**Pruebas (parte 3):** verificación HTTP real de punta a punta (ingresar con un número real → confirmar que se ve el nombre → cerrar sesión → confirmar que volver a `mis-cartas` rebota al formulario en vez de mostrar las cartas). Suite completa: 87/87 en verde.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`), las tres partes. Ver `docs/cambios/changelog.md` y `docs/cambios/decisiones-arquitectura.md` para el detalle de las decisiones de diseño.

---

**ID:** UX-006
**Módulo:** Cartas
**Problema:** `cartas/show.blade.php` conserva un bloque grande de HTML comentado, código muerto desde el cierre de UX-001
**Tipo:** DEUDA TÉCNICA
**Severidad:** BAJO
**Archivos afectados:** `resources/views/cartas/show.blade.php` (líneas 35–54)
**Comportamiento actual:** el bloque comentado replica el diseño de comparación v1/v2 con `@can('mostrarEnlaceVer', $carta)` que UX-001 volvió obsoleto al retirar la plantilla v2 completa. No tiene nada con qué comparar hoy. La vista además solo muestra el PDF embebido, sin ningún dato adicional de la carta (estado, fechas, gestor).
**Solución propuesta:** eliminar el bloque comentado; evaluar agregar una tarjeta de metadatos junto al PDF (estado, fecha de creación, fecha de respuesta, gestor).
**Fase sugerida:** Fase 3 — UX/UI. Ver `docs/auditoria/ux-ui-dashboard-vistas.md`, sección 2.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). Se eliminó el bloque de 20 líneas comentadas. Se agregó una segunda columna con una tarjeta de metadatos (tipo con badge/ícono, estado con badge de color, niño, comunidad, gestor, fecha de creación, fecha de respuesta o "Sin responder") junto al PDF, usando los accessors ya existentes en el modelo (`color`, `icono`, `estado_color`) y el mismo formato de fecha (`d/m/Y H:i`) que usa `CartaDataTable`. Verificado: `php artisan view:cache` sin errores, render directo de la vista contra una carta real respondida (confirmando ausencia del comentario viejo y presencia de los badges/metadatos) y contra una carta real sin responder (confirmando "Sin responder"). Suite completa: 87/87 en verde. Base real sin cambios (solo lecturas).

---

**ID:** UX-007
**Módulo:** Global (welcome / home)
**Problema:** `welcome.blade.php` (landing pública, sin autenticación) es literalmente `@include('home')` — el mismo contenido que el dashboard autenticado, sin diferenciación de propósito
**Tipo:** UX-UI
**Severidad:** BAJO
**Archivos afectados:** `resources/views/welcome.blade.php`, `resources/views/home.blade.php`
**Comportamiento actual:** un visitante sin cuenta que entra a `cactu.ec/` ve el layout completo del panel (sidebar, navbar con botón de login) con las mismas tarjetas estáticas de marketing que ve un usuario logueado. El banner interno de "Actualización Importante" (aviso de migración de formatos de carta, dirigido a usuarios internos) también se muestra a visitantes anónimos, sin sentido para ellos.
**Solución propuesta:** decidir con el usuario si se mantiene como alias deliberado de `home` (sin cambios) o se separa en una landing pública propia sin el layout de panel ni contenido interno.
**Decisión del usuario (2026-08-08):** separar en una landing pública propia.
**Ajuste de criterio al implementar (2026-08-08, dos rondas):** la primera implementación usó un layout raíz independiente (`<!DOCTYPE html>` propio, sin sidebar/navbar), calcado del patrón de `responder-cartas/principal.blade.php`. El usuario lo rechazó: rompía la consistencia visual con el resto del sistema, incluido `login`. Se corrigió a `@extends('layouts.app')` con una portada con degradado y ola SVG — el usuario volvió a pedir ajustes: el logo usado (`logo_icon.svg`, variante clara para navbar oscuro) no se veía bien en tema claro, y la portada + tarjetas + botón se leían como "tres páginas divididas horizontalmente". Versión final: sin portada, un único bloque centrado vertical y horizontalmente con el logo correcto para fondo claro (`logo_icon_azul.svg`), texto y las dos tarjetas.
**Fase sugerida:** Fase 3 — UX/UI. Ver `docs/auditoria/ux-ui-dashboard-vistas.md`, sección 3.
**Estado:** **Resuelto** (2026-08-08, rama `security/fase-2-hallazgos-criticos`). `welcome.blade.php` deja de ser `@include('home')` y pasa a ser su propio contenido dentro de `@extends('layouts.app')` (mismo layout que usa el resto del sistema, incluido `login`): un único contenedor centrado (`d-flex flex-column justify-content-center align-items-center`, `min-height: 60vh`, sin CSS custom) con logo (`logo_icon_azul.svg`), título "CACTU", descripción, las dos tarjetas de "Gestión de Comunidades"/"Correspondencia" ya existentes, y un botón "Iniciar Sesión". Sin el banner interno de "Actualización Importante" (ese sigue solo en `home.blade.php`, para usuarios logueados). La ruta `/` (`routes/web.php`) gana un chequeo `Auth::check()`: un usuario ya autenticado que visite `/` se redirige a `route('home')` en vez de ver la landing pública — antes no había redirección porque `welcome` y `home` eran el mismo contenido. `home.blade.php` (el dashboard real, ya con `@auth`/`@else`) queda sin cambios.
**Archivos:** `resources/views/welcome.blade.php`, `routes/web.php`, `tests/Feature/WelcomeTest.php` (nuevo).
**Impacto Web:** un visitante sin sesión ve una landing propia y simple, con el mismo navbar/layout que el resto del sistema; un usuario autenticado que visite `/` ahora aterriza en `/home` en vez de ver contenido de marketing. **Impacto API:** ninguno. **Impacto React Native:** ninguno.
**Pruebas:** `php artisan view:cache` sin errores tras el ajuste; verificación HTTP real contra el servidor local (`GET /` sin sesión → 200, contiene "Iniciar Sesión" y el link "Ingresar" del navbar de `layouts.app`, no contiene "Acceso Rápido" ni "Actualización Importante"); `tests/Feature/WelcomeTest.php` (2 casos, sin cambios tras el ajuste porque las aserciones ya eran sobre contenido, no sobre estructura de layout: landing pública para invitado, redirección a `/home` para usuario autenticado). Suite completa: 89/89 en verde. Base real sin cambios propios.

---

**ID:** UX-008
**Módulo:** Dashboard (home)
**Problema:** el dashboard post-login no muestra datos reales ni distingue por rol
**Tipo:** UX-UI
**Severidad:** BAJO
**Archivos afectados:** `app/Http/Controllers/HomeController.php` (`index()`), `resources/views/home.blade.php`
**Comportamiento actual:** `HomeController::index()` hace `return view('home')` sin pasar ningún dato. La vista es 100% estática (dos tarjetas genéricas "Cartas: Gestionar", "Niños: Administrar"), igual para `ADMINISTRADOR` y `GESTOR`, pese a que el resto del sistema (`CartaDataTable`, `NinioDataTable`, `CartaController`, las Policies) ya tiene lógica de alcance por rol implementada y probada.
**Solución propuesta:** agregar KPIs reales al dashboard, con alcance por rol igual que los listados. Requiere que el usuario defina primero qué métricas de negocio le interesan (no asumir — `CLAUDE.md` sección 3).
**Fase sugerida:** Fase 3 — UX/UI. Ver `docs/auditoria/ux-ui-dashboard-vistas.md`, secciones 4 y 5, para las preguntas pendientes.
**Estado:** Cerrado (2026-08-08). `HomeController::index()` ahora calcula cartas por responder/respondidas, total de niños y niños sin carta, con el mismo alcance por rol que `CartaDataTable`/`NinioDataTable`; `home.blade.php` se reescribió con 4 KPI cards, la tarjeta de "Manual de Usuario" y una tabla de niños por comunidad, eliminando el banner vencido y el contenido decorativo sin datos. Detalle en `docs/cambios/changelog.md` y `docs/cambios/decisiones-arquitectura.md`.

---

**ID:** UX-009
**Módulo:** Global (ninios, usuarios, comunidad)
**Problema:** filtros de búsqueda inconsistentes entre listados — solo `cartas/index` los tiene
**Tipo:** UX-UI
**Severidad:** BAJO
**Archivos afectados:** `resources/views/ninios/index.blade.php`, `resources/views/usuarios/index.blade.php`, `resources/views/comunidad/index.blade.php` (sin filtros) vs. `resources/views/cartas/index.blade.php` (patrón maduro: card colapsable, filtros por tipo/estado/niño/comunidad/rango de fechas/gestor, auto-aplicación AJAX con debounce, indicador de filtros activos, persistencia en URL)
**Comportamiento actual:** `ninios`, `usuarios` y `comunidad` solo tienen `{{ $dataTable->table() }}` con el buscador genérico de DataTables — sin ningún filtro propio del dominio.
**Solución propuesta:** replicar el patrón ya validado de `cartas/index` en los otros 3 listados, confirmando primero con el usuario qué campos filtrar en cada uno (ej. `ninios` por comunidad/estado; `usuarios` por rol; `comunidad` por gestor asignado).
**Fase sugerida:** Fase 3 — UX/UI. Ver `docs/auditoria/ux-ui-dashboard-vistas.md`, sección 6.
**Estado:** Cerrado (2026-08-08). Se replicó el patrón de `cartas/index` (card colapsable + filtros GET + AJAX con auto-aplicación) en los 3 listados: `ninios` (Comunidad, Estado, Género), `usuarios` (Rol, Estado, Provincia), `comunidad` (Gestor asignado). Detalle en `docs/cambios/changelog.md`.
