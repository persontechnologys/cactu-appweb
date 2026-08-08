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
**Estado:** Analizado, diferido — no corregido en esta fase para evitar romper enlaces ya enviados a patrocinadores/niños sin un rediseño validado. **Fase sugerida:** Fase 4/5 (junto a la unificación Web/API de cartas).

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
**Comportamiento actual:** la regla `'unique:users,email,'.$this->id` asume un parámetro de ruta llamado `id`, pero `Route::resource('usuarios', UserController::class)` genera el parámetro `usuario`. `$this->id` siempre resuelve a `null` (no está ni en el input ni en los parámetros de ruta), por lo que el "ignorar" del `unique` nunca apunta al registro correcto.
**Comportamiento esperado:** editar un usuario sin cambiar su email no debería fallar la validación de unicidad.
**Cómo se detectó:** al escribir pruebas de autorización para SEG-001 (Fase 2), reutilizar el email original de un usuario en `PUT /usuarios/{id}` produjo el error de validación "El valor del campo email ya está en uso." de forma reproducible.
**Solución propuesta:** usar `$this->route('usuario')` (o inyectar el modelo `User` en el Form Request) en vez de `$this->id`.
**Impacto Web:** Medio — cualquier edición de usuario desde `/usuarios` que no cambie el email falla con un mensaje de error engañoso. **Impacto API:** No aplica. **Impacto React Native:** No aplica.
**Estado:** Detectado, sin corregir (fuera de alcance de los hallazgos de seguridad autorizados para esta fase). **Fase sugerida:** próxima fase de mantenimiento/calidad, o junto a Fase 1 si se retoma.

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

---

**ID:** ARQ-003
**Módulo:** Global
**Problema:** Imports de controllers inexistentes (`GestorController`, `SensorDataController`)
**Tipo:** DEUDA TÉCNICA
**Severidad:** BAJO
**Archivos afectados:** `routes/web.php:5`, `routes/api.php:4`
**Solución propuesta:** Eliminar los `use` no utilizados, o si representan funcionalidad planificada, documentarla explícitamente en el roadmap.
**Fase sugerida:** Cualquier fase de limpieza menor (bajo riesgo, alto valor de claridad).

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

---

**ID:** TEST-001
**Módulo:** Global
**Problema:** Ausencia total de pruebas del dominio
**Tipo:** DEUDA TÉCNICA
**Severidad:** ALTO
**Archivos afectados:** `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` (boilerplate sin relación con CACTU)
**Comportamiento actual:** No existe ninguna prueba de autorización, de flujo de cartas, ni de los endpoints API.
**Riesgo:** Los hallazgos SEG-001 a SEG-005 podrían haberse detectado automáticamente con pruebas de autorización básicas; su ausencia facilita regresiones futuras.
**Fase sugerida:** Transversal — comenzar por pruebas de autorización (Fase 2) y continuar en Fase 8 — Performance + Calidad.

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

---

**ID:** DOC-001
**Módulo:** Global
**Problema:** Sin documentación de arquitectura, API ni integración móvil previa a esta auditoría
**Tipo:** DOCUMENTACIÓN
**Severidad:** MEDIO
**Solución propuesta:** Este mismo directorio `docs/` es el punto de partida; continuar completando `docs/api/` y `docs/movil/` en fases posteriores conforme se confirme el contrato con el repositorio React Native.
**Fase sugerida:** Transversal.
