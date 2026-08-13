# Decisiones de arquitectura

> Formato según `CLAUDE.md` sección 42. Registra el *por qué* de decisiones importantes, no el *qué* (eso está en `changelog.md`).

---

## 2026-08-08 — Convención de nombres para Policies

**Contexto:** Fase 1 del roadmap (`docs/fases/roadmap.md`) pide "definir convención de nombres de Policies alineada a Laravel (`view`, `update`, `delete`) para permitir `authorizeResource()` a futuro".

**Decisión:** todo método de Policy **nuevo** debe usar los verbos estándar de Laravel: `viewAny`, `view`, `create`, `update`, `delete`. Esto ya se aplicó de facto en `UserPolicy` y `NinioPolicy` (creadas en Fase 2, SEG-001/SEG-002), y en los métodos `view`/`update` añadidos a `CartaPolicy` en Fase 2 (SEG-007). Es la convención que Laravel espera para poder usar `$this->authorizeResource(Modelo::class)` en el constructor de un Controller en vez de llamadas manuales a `$this->authorize()`.

**Por qué:** reduce código repetitivo en los Controllers y hace explícito, por convención, qué método de Policy protege cada acción del resource — sin tener que leer cada `$this->authorize('verbo', ...)` para saberlo.

**Qué NO se hizo:** no se renombraron los métodos legacy en español que ya existían en `CartaPolicy` (`ver`, `eliminar`). Al revisar el código real (regla "primero comprender, después modificar" de `CLAUDE.md` sección 3) se encontró que `ver()` y el nuevo `view()` **no son alias** — tienen lógica de negocio distinta (`ver()` exige `estado === 'Respondida'` además de dueño/admin; `view()` no tiene esa restricción). Colapsarlos a ciegas habría cambiado comportamiento observable en `resources/views/cartas/action.blade.php` y `cartas/show.blade.php`. Se documenta la divergencia como `ARQ-004` en `docs/auditoria/problemas-detectados.md`, pendiente de una decisión de negocio (no solo de naming) antes de tocar esos métodos.

**Impacto:** ninguno sobre comportamiento actual. Aplica hacia adelante, para Policies o métodos nuevos.

---

## 2026-08-08 — Extracción de la primera Action (`RegistrarCartaAction`)

**Contexto:** `CartaController::store` (ARQ-002) concentraba validación, transacción manual, guardado de archivos y notificación en un solo método de ~80 líneas, sin ninguna Action existente en el proyecto como precedente.

**Decisión:** se creó `app/Actions/RegistrarCartaAction.php` con un único método público `registrar()`, que recibe primitivos (`int`, `string`, `UploadedFile`) en vez de un objeto `Request`, para que sea invocable y testeable sin depender del ciclo HTTP (relevante si en el futuro la API móvil necesita registrar cartas con la misma regla de negocio, `CLAUDE.md` sección 11).

**Por qué:** es el patrón que pide `CLAUDE.md` sección 14 (Actions para operaciones concretas del negocio) y sección 12 (Controllers delgados). Se usó `DB::transaction(closure)` en vez de `beginTransaction/commit/rollBack` manual porque es equivalente en comportamiento (revierte y relanza la excepción igual) pero más difícil de dejar sin `rollBack` por un `return` temprano olvidado.

**Qué NO se hizo:** no se creó el `Service` de almacenamiento de archivos que la ficha original de ARQ-002 sugería como idea — el roadmap de Fase 1 solo pedía la Action, y no había una segunda necesidad concreta (otro flujo que reutilice ese guardado de archivos) que justificara la abstracción adicional (`CLAUDE.md` sección 56, no sobrearquitecturar). Tampoco se convirtió la validación inline de `store()` a un Form Request — fuera del alcance que el roadmap definió para esta fase.

**Impacto:** ninguno sobre comportamiento observable. Cubierto por `tests/Feature/Cartas/RegistrarCartaTest.php`, verificado en verde contra el código antes y después del refactor.

---

## 2026-08-08 — Actions únicas para responder carta (ARQ-001, Fase 4)

**Contexto:** `ResponderCartaController` (web) y `Api\ResponderCarta` (móvil) construían el HTML de `detalle` de los 5 tipos de carta por concatenación de strings, duplicado carácter por carácter (confirmado con `diff`, no por inspección). El roadmap (Fase 4) ya proponía "extraer Actions únicas para cada tipo de respuesta de carta".

**Decisión — qué se unificó:** 6 Actions en `app/Actions/`: una por tipo de carta (`ResponderCartaContestacionAction`, `ResponderCartaAgradecimientoAction`, `ResponderCartaIniciadaAction`, `ResponderCartaPresentacionMayorAction`, `ResponderCartaPresentacionMenorAction`) que arman el `detalle` a partir de primitivos, y `GuardarRespuestaCartaAction` compartida por las 5 (guarda imagen/foto_familia con la extensión del `UploadedFile` recibido, marca `estado='Respondida'`, fija `fecha_respondida`, notifica al gestor, todo dentro de `DB::transaction()`). Mismo patrón que `RegistrarCartaAction`: primitivos en la firma, sin depender de `Request`, para que sean invocables y testeables sin ciclo HTTP.

**Decisión — qué se dejó explícitamente sin unificar (por canal):**
- **Resolución de la carta:** web descifra `id` (`Crypt::decryptString`) porque el niño accede por link sin sesión; API resuelve `id` plano contra el niño autenticado por Sanctum. Son modelos de acceso distintos, no duplicación.
- **Autorización:** web usa `CartaPolicy::responderCartaXninio` (ya existente); API compara `carta->ninio_id === $request->user()->id` (SEG-005). Unificar habría exigido inventar una autenticación de niño en web que hoy no existe — fuera de alcance de esta fase.
- **Carta ya respondida:** web sigue lanzando `AuthorizationException` (403) vía la Policy; API sigue devolviendo `200 {info: 'Carta ya fue respondida'}` sin persistir. Cambiar cualquiera de los dos altera el contrato observable de un canal (la vista web ya maneja el 403, la app móvil ya maneja el `info`) sin que el roadmap pidiera ese cambio.
- **Forma de la imagen recibida:** web recibe base64 (canvas del formulario) y se convierte a un `UploadedFile` temporal antes de llamar a la Action (mismo nombre `id.png` que generaba `guardarImagenBase64`, se borra el temporal en un `finally`); API recibe `UploadedFile` real de multipart. `GuardarRespuestaCartaAction` solo conoce `UploadedFile`, nunca base64 — no tiene que saber de dónde vino.
- **Respuesta HTTP:** web sigue con redirect + `Session::flash`; API sigue con JSON.

**Qué se simplificó como efecto colateral (sin cambiar comportamiento observable):** `Api\ResponderCarta::guardarCarta` abría una transacción (`DB::beginTransaction()`) que envolvía también la verificación de dueño y el corte por "ya respondida" — pasos que no escriben en base de datos — y además esa transacción quedaba **sin cerrar** (ni commit ni rollback) en el camino de "ya respondida" (bug preexistente de fuga de transacción). Al mover el `DB::transaction()` a `GuardarRespuestaCartaAction` (que solo envuelve el guardado real), ese caso ya no abre ninguna transacción, así que la fuga desaparece por construcción; no se registra como hallazgo aparte por ser un efecto colateral directo de este refactor, no un hallazgo nuevo buscado a propósito.

**Qué NO se hizo:** no se tocó `ver()`/`index()`/`verArchivo()` del controller web (fuera del alcance de ARQ-001, no tienen duplicación). No se introdujo un Form Request para las validaciones (siguen siendo `$request->validate([...])` inline en ambos controladores) — el roadmap de Fase 4 solo pedía las Actions, y ARQ-002 (form requests) es un hallazgo separado no priorizado para esta fase.

**Impacto:** ninguno sobre comportamiento observable en Web, API o React Native. Cubierto por `tests/Unit/Actions/ResponderCartaAccionesTest.php`, `tests/Feature/Cartas/ResponderCartaWebTest.php` y `tests/Feature/Api/ResponderCartaTest.php`; suite completa (40 tests) verificada en verde, incluyendo los tests de SEG-005 y SEG-008 ya existentes sobre este mismo código.

---

## 2026-08-08 — Form Requests para responder-carta (Fase 4)

**Contexto:** `docs/auditoria/funcionalidades-compartidas.md` (auditoría cruzada Fase 0.1) confirmó que la lista de campos obligatorios por tipo de carta vivía triplicada: Blade, `$request->validate([...])` inline en `ResponderCartaController` (web) y en `Api\ResponderCarta` (móvil), y `validar()` en cada pantalla de `CactuApp`. Se verificó contra el código real, campo por campo, que las dos piernas backend (web y API) eran idénticas.

**Decisión — un `FormRequest` por tipo, no uno genérico parametrizado:** se creó `app/Http/Requests/Cartas/ResponderCarta{Tipo}Request.php` (5 clases) en vez de un único `FormRequest` con `rules()` condicional según la ruta. Mismo criterio 1:1 ya usado para las Actions de ARQ-001 (`ResponderCarta{Tipo}Action`): cada clase es trivial (un array de reglas, sin lógica), más simple de leer que una clase con un `switch`/`match` interno, y evita acoplar las 5 rutas a una sola clase que tendría que crecer cada vez que un tipo de carta cambie sus campos.

**Decisión — `authorize()` fijo en `true`:** la autorización de "quién puede responder esta carta" no se movió al `FormRequest`. Sigue exactamente donde ARQ-001 la dejó (Policy `responderCartaXninio` en web, comparación `ninio_id === $request->user()->id` en API) porque **necesita la carta ya resuelta** (descifrado de `id` en web, `Carta::findOrFail` en ambos) y ese paso ocurre dentro de `procesarRespuesta()`, después de que el `FormRequest` ya validó. Poner autorización en el `FormRequest` habría exigido duplicar ahí la resolución de la carta — el problema que ARQ-001 ya resolvió centralizando en la Action, no en la validación de entrada.

**Decisión — se preserva el typo `mifamila`:** el campo de Presentación Menor se llama `mifamila` (sin "i") tanto en el `FormRequest` nuevo como en el array que reemplaza. Es el nombre real que envía `CactuApp` (`PresentacionMenores.js`, campo del `FormData`, aunque la variable local del componente se llame `mifamilia`). Corregir el typo aquí rompería el contrato con el móvil sin coordinación previa — fuera de alcance de este cambio, que es sobre *dónde* vive la regla, no sobre *qué* dice la regla.

**Qué NO se hizo:** no se tocó el repositorio `CactuApp` — su `validar()` es la tercera pierna de la triplicación, pero no puede compartir código PHP con Laravel; queda como validación de UX (`CLAUDE.md` sección 20: la interfaz nunca es la única protección), ya no como fuente de la regla. No se tocó el umbral `edad > 5` (mayor/menor) — confirmado igual en los tres puntos donde se bifurca, pero es una decisión de qué formulario mostrar, no de validación de campos, y no formaba parte de este hallazgo.

**Impacto:** ninguno sobre comportamiento observable — mismo orden validación→autorización, mismos campos, mismos códigos de error. Cubierto por dos tests nuevos (uno por canal) que fijan el caso "falta un campo obligatorio", antes no probado; suite completa (42 tests) verificada en verde.

---

## 2026-08-08 — ARQ-004: rename sin cambio de comportamiento

**Contexto:** `ARQ-004` (`docs/auditoria/problemas-detectados.md`) quedó documentado en Fase 1 como una decisión de negocio pendiente: ¿debe la restricción "solo si Respondida" de `CartaPolicy::ver()` aplicarse también a `show`/`edit`/`update` (fusionando con `view()`/`update()`), o debe el link de las vistas dejar de depender de un método de Policy separado?

**Decisión (del usuario):** ninguna de las dos — se mantiene la separación conceptual (regla de UI para mostrar el link "Ver" vs. autorización real de backend), solo se renombra `ver()` a `mostrarEnlaceVer()` para que el nombre no sugiera que es alias de `view()`. Es el cambio de menor riesgo: cero comportamiento nuevo, solo elimina la ambigüedad de nombres que originó el hallazgo.

**Por qué no se fusionaron:** al revisar los 2 lugares que consumen cada método se confirmó que resuelven necesidades distintas — `mostrarEnlaceVer()` decide si mostrar un link a un PDF que, si la carta no está `Respondida`, ni siquiera existe todavía; `view()`/`update()` protegen el acceso al `detalle` administrativo de la carta independientemente de si el niño ya respondió. Fusionarlas en cualquier dirección habría sido un cambio de comportamiento no solicitado.

---

## 2026-08-08 — ARQ-005: versionado `/api/v1/` como alias aditivo (Fase 5)

**Contexto:** el roadmap (Fase 5, `docs/fases/roadmap.md`) pide "introducir versionado (`/api/v1/`) antes de cualquier cambio de forma de respuesta". Se confirmó contra `routes/api.php` que las 12 rutas del API cuelgan hoy directo de `/api/`, sin ningún prefijo de versión.

**Decisión — alias aditivo, no reemplazo:** en `app/Providers/RouteServiceProvider.php::boot()` se añadió un segundo montaje del mismo `routes/api.php`, esta vez bajo `->prefix('api/v1')->name('v1.')`, junto al montaje original en `/api/`. No se creó un `routes/api_v1.php` separado ni se movió/copió ninguna definición de ruta: es literalmente el mismo archivo cargado dos veces con distinto prefijo, así que `/api/...` y `/api/v1/...` no pueden divergir en comportamiento por descuido — cualquier cambio futuro a una ruta se refleja automáticamente en ambos prefijos.

**Riesgo detectado y mitigado — colisión de nombres de ruta:** `routes/api.php` nombra dos rutas GET (`->name('verarchivopdfporninio')`, `->name('descargarCartaPdf')`), y `Api\ResponderCarta::listaCartas` genera las URLs de descarga de PDF de la respuesta con `route('verarchivopdfporninio', ...)` / `route('descargarCartaPdf', ...)`. Registrar el archivo dos veces sin aislar los nombres habría hecho que el segundo montaje pisara al primero, y **todas las respuestas de `listar-mis-cartas` (en ambos prefijos) habrían empezado a devolver URLs `/api/v1/...`** — un cambio de contrato no solicitado. El prefijo `name('v1.')` en el segundo montaje evita la colisión: quedan registrados `verarchivopdfporninio`/`descargarCartaPdf` (legado) y `v1.verarchivopdfporninio`/`v1.descargarCartaPdf` (nuevo) como nombres independientes. Verificado con `php artisan route:list` (24 rutas, sin warnings) y fijado en `tests/Feature/Api/VersionadoApiV1Test.php::test_las_rutas_con_nombre_no_colisionan_entre_api_y_api_v1`.

**Efecto colateral aceptado, no corregido:** como `listaCartas` sigue llamando a `route('verarchivopdfporninio', ...)` (sin el prefijo `v1.`), las URLs de descarga de PDF embebidas en la respuesta siguen apuntando a `/api/dcpdf/...` y `/api/ver-archivo-pdf-por-ninio/...` **incluso cuando el cliente pidió la lista vía `/api/v1/listar-mis-cartas`**. No se resolvió (por ejemplo, resolviendo el nombre dinámicamente según el prefijo de la petición actual) porque añadía complejidad no pedida por el roadmap para un caso ya seguro: esas dos rutas de descarga están protegidas por ID cifrado (SEG-003) y sirven igual desde cualquier prefijo. Documentado en `docs/movil/api.md` y `docs/auditoria/matriz-api-movil.md` para que no se lea como bug si alguien lo nota más adelante.

**Qué NO se hizo:** no se tocó `routes/api.php` (cero cambio de comportamiento en los 12 endpoints). No se migró `CactuApp` a `/api/v1/` — el roadmap solo pide que el prefijo exista como alias disponible; la migración del cliente móvil es una decisión aparte, no forzada por este cambio. No se introdujo todavía ningún endpoint que solo exista bajo `v1` (eso vendría cuando de verdad se necesite cambiar una forma de respuesta).

**Impacto:** ninguno sobre comportamiento observable en Web, API o React Native. `CactuApp` sigue apuntando a `/api/...` sin cambios. Cubierto por `tests/Feature/Api/VersionadoApiV1Test.php` (3 tests nuevos: paridad de `/login`, paridad de `/listar-mis-cartas`, no colisión de nombres); suite completa (48 tests) verificada en verde.

**Hallazgo adicional:** la referencia a `ver()` en `resources/views/cartas/show.blade.php:58` que documentaba la auditoría original resultó estar dentro de un bloque Blade comentado (`{{-- ... --}}`) — código muerto. Se corrigió el nombre ahí también por consistencia, sin que afecte ejecución alguna.

**Impacto:** ninguno. Suite completa en verde tras el cambio.

---

## 2026-08-08 — SEG-006: por qué ahora sí era seguro cerrarlo

**Contexto:** SEG-006 quedó diferido en Fase 2 con la nota explícita: "no corregido en esta fase para evitar romper enlaces ya enviados a patrocinadores/niños sin un rediseño validado". El flujo `cartas-ninio.*` no tiene sesión de niño (rutas fuera de `auth`); la única protección era el `id` de carta cifrado.

**Decisión:** replicar el patrón ya existente en `ResponderCartaController::ver()` (verificar `numero_child` cifrado contra el dueño real) en vez de diseñar un mecanismo nuevo (token de sesión temporal, mencionado como alternativa en la ficha original). Se prefirió por consistencia — ya es el patrón que usa el propio controller para el mismo problema en otra ruta — y por ser la opción de menor superficie de cambio.

**Por qué ya no aplica el riesgo que motivó diferirlo:** el análisis de qué es un "enlace ya enviado" separó dos cosas que la ficha original no distinguía — los enlaces `GET` (`cartas-ninio.index/{numero_child}`, `carta-ninio-ver/{idcarta}/{numero_child}`), que sí pueden estar en bandejas de entrada de patrocinadores/niños y que este cambio **no toca**, y el formulario `POST` de cada respuesta, que Laravel renderiza de nuevo cada vez que alguien abre la carta desde el índice — no es un artefacto que pueda quedar "viejo" en manos de un usuario. Agregar un campo `hidden` a ese formulario no rompe ningún enlace existente.

**Dónde se decidió NO poner la verificación:** se consideró agregar `'ninio' => 'required'` a los `FormRequest` creados en el cambio anterior de esta misma fase (`app/Http/Requests/Cartas/`), pero esos `FormRequest` los comparte también `Api\ResponderCarta` — el canal móvil nunca envía ese campo porque su identidad ya la resuelve el token Sanctum (SEG-005). Ponerlo ahí habría exigido que `CactuApp` empezara a enviar un campo que no tiene forma de construir, rompiendo el contrato sin necesidad: la verificación de identidad del niño en web no es una regla de negocio compartida con la API, es un mecanismo de autenticación específico de un canal que no tiene sesión — por eso vive en `ResponderCartaController::procesarRespuesta()`, no en la capa de validación compartida.

**Impacto:** cierra un hallazgo de seguridad MEDIO. Ningún endpoint ni formato de respuesta cambia; solo se exige un campo adicional en el `POST` del formulario web, que la propia vista ya provee. Cubierto por 2 tests nuevos (suplantación con `numero_child` de otro niño, y campo `ninio` ausente); suite completa (44 tests) en verde.

## 2026-08-08 — SEG-013: validate() fuera del try/catch en `/login`

**Contexto:** al documentar `docs/api/autenticacion.md` (Fase 5) se encontró que el `catch (\Throwable $th)` del closure `POST /login` (`routes/api.php`) envolvía también `$request->validate([...])`. Antes de escribir "esto es un problema" se verificó el comportamiento real con `php artisan tinker` (`CLAUDE.md` sección 3, no asumir): una petición sin `numero_child` devolvía HTTP `200` con el string `"El campo numero child es obligatorio."`, no el `422` estándar de Laravel — el `catch` interceptaba también la `ValidationException`. El mismo `catch` exponía `$th->getMessage()` para cualquier excepción real no prevista (mismo patrón que `SEG-008`, no cubierto en ese cierre porque `SEG-008` solo revisó Controllers, y este es un closure inline en el archivo de rutas).

**Decisión:** mover `$request->validate(...)` fuera del `try`, antes de él. Así Laravel maneja la `ValidationException` con su mecanismo estándar (respuesta `422` automática), sin que el `catch(\Throwable)` de más abajo la intercepte. El `catch` que queda solo cubre excepciones genuinas no previstas (p. ej. fallo de BD), y ahora hace `Log::error()` + responde un mensaje genérico, igual que los 6 puntos ya corregidos en `SEG-008`.

**Por qué no se cambió el código HTTP del `catch` genérico a `500`:** se consideró, pero el resto de la API (`procesarRespuesta` en `Api\ResponderCarta`, ya corregido en `SEG-008`) mantiene el mismo patrón de responder `200` con `{"error": "..."}` para este tipo de excepción — es una inconsistencia real de la API (registrada como `ARQ-006`, "Paso B" pendiente de autorizar), pero cambiar el código HTTP de un solo endpoint sin tocar los demás no resuelve el problema de fondo y sí introduce un comportamiento distinto entre endpoints sin una razón que lo justifique. Se deja como parte del Paso B, no de esta corrección puntual.

**Impacto:** el caso "genuino error de BD" (poco frecuente) deja de filtrar detalle interno. El caso "falta `numero_child`" pasa de `200` + string a `422` + `{"errors": {...}}` — es la corrección de un bug real (la app nunca podía distinguir programáticamente este caso de un login exitoso por código HTTP), no un cambio de contrato deliberado; en la práctica `Login.js` usa un teclado numérico que no permite enviar el campo vacío, así que este camino no debería dispararse desde la app real hoy. Cubierto por `tests/Feature/Seguridad/LoginTest.php` (2 tests nuevos, uno fuerza una excepción real vía `Schema::drop('personal_access_tokens')`, mismo patrón que `MensajesErrorTest` de `SEG-008`, no un mock). Suite completa (50 tests) en verde.

---

## 2026-08-08 — ARQ-006 (Paso A): `CartaListaResource` para `listar-mis-cartas`

**Contexto:** `docs/auditoria/evaluacion-api-resources.md` (Fase 5) evaluó introducir Laravel API Resources para estandarizar las respuestas JSON de la API. Conclusión del diagnóstico: de los 12 endpoints, solo `listar-mis-cartas` tiene forma "de modelo" (una lista de objetos con los mismos 9 campos por `Carta`); los 8 endpoints de escritura devuelven mensajes de estado fijos, donde una Resource no aporta nada — necesitan un patrón distinto (envelope común), de mayor riesgo, que queda fuera de esta decisión.

**Decisión:** se crea `app/Http/Resources/CartaListaResource.php` y `Api\ResponderCarta::listaCartas` delega en `CartaListaResource::collection($cartas)` en vez de construir el array a mano dentro de un `foreach`. Mismos 9 campos, misma lógica de cada uno (incluida la generación de URLs cifradas de PDF), sin cambio de contrato.

**Cómo se garantizó "sin cambio de contrato" (no solo se afirma, se verificó):** se escribió `tests/Feature/Api/ListarMisCartasTest.php::test_la_forma_de_cada_item_no_cambia_con_o_sin_pdf_adjunto` **antes** del refactor, cubriendo los dos casos que ramifican en el código real (carta sin archivo/sin responder, y carta con archivo adjunto + respondida). Al ejecutarlo contra el código original se detectaron dos suposiciones incorrectas en el primer borrador del test — `edad` depende de la fecha actual (no es un valor fijo) y `eliminado` se serializa como entero `0`/`1` de SQLite, no como `true`/`false` (el modelo `Carta` no tiene cast a boolean para ese campo) — corregidas contra el comportamiento real antes de tocar el Controller. Con el test en verde contra el código viejo, se hizo el refactor y se volvió a correr: mismo resultado, sin diferencias.

**Qué NO se hizo:** no se tocaron los otros 11 endpoints ni se introdujo un envelope común — es exactamente el "Paso B" que `docs/auditoria/evaluacion-api-resources.md` deja explícitamente sin autorizar hasta verificar contra el código real de `CactuApp` si alguna pantalla compara claves/strings exactos de las respuestas de escritura.

**Impacto:** ninguno sobre comportamiento observable. Cubierto por el test descrito arriba (verificado en rojo-imposible/verde antes y después, no solo "verde después"). Suite completa (51 tests) en verde.

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>

---

## 2026-08-08 — PERF-001: `database` + cron en vez de Redis/Supervisor para colas

**Contexto:** Fase 6 del roadmap (`docs/fases/roadmap.md`) pide mover procesos pesados fuera del ciclo HTTP. Primer paso elegido (de las opciones presentadas y autorizadas por el usuario): dejar la infraestructura de colas realmente funcional, antes de tocar `config/snappy.php` (bloqueado por falta de verificación contra producción, ver `PDF-001`) o mover la generación de PDF a un Job.

**Decisión:** `EnviarCartaRespondidaGestor` y `EnviarNotificacionCartaNueva` pasan a `implements ShouldQueue` (antes solo importaban la interfaz, sin implementarla — inofensivo mientras el driver era `sync`, pero bloqueaba cualquier beneficio real de encolar). Se publica la migración de la tabla `jobs` (`php artisan queue:table`, no existía — solo `failed_jobs`) y se aplica. `.env.example` pasa `QUEUE_CONNECTION` de `sync` a `database`. `app/Console/Kernel.php::schedule()` registra `queue:work --stop-when-empty` cada minuto con `withoutOverlapping()`.

**Por qué `database` + cron y no Redis/Supervisor:** confirmado en `docs/auditoria/infraestructura-actual.md` que la producción es cPanel compartido, sin acceso root/Supervisor/systemd (`CLAUDE.md` sección 17). El patrón cron-driven (`schedule:run` cada minuto dispara `queue:work --stop-when-empty`, que procesa lo pendiente y termina solo) es el único compatible sin cambiar de hosting, y es exactamente el que ya proponía `infraestructura-actual.md` antes de este cambio.

**Qué NO se hizo:** no se tocó `config/snappy.php` (`PDF-001`) ni se movió la generación de PDF a un Job — ambos quedan para los siguientes pasos de Fase 6, deliberadamente separados porque `PDF-001` requiere verificar contra el servidor de producción real antes de tocarlo (no se asume, `CLAUDE.md` sección 3/17), y no depende de que la infraestructura de colas ya exista. Tampoco se cambió el `QUEUE_CONNECTION` del `.env` local de desarrollo (se deja en `sync`) — cambiarlo exigiría correr `queue:work`/`schedule:work` en paralelo durante las pruebas manuales con el móvil, y no se pidió explícitamente.

**Impacto:** ninguno sobre comportamiento observable en local/tests — `phpunit.xml` fuerza `QUEUE_CONNECTION=sync`, así que `ShouldQueue` no cambia cómo corren los tests (sync sigue ejecutando inline). En producción, una vez que `.env` real adopte `database` y se confirme la entrada de Cron para `schedule:run` (pendiente de verificar directamente en cPanel, no auditable desde este repositorio), el envío de correo/Firebase dejará de bloquear la respuesta HTTP de `CartaController::store` y de los flujos de `ResponderCartaController`/`Api\ResponderCarta`. Test de regresión: `tests/Unit/Notifications/NotificacionesShouldQueueTest.php`. Suite completa (53 tests) en verde.

---

## 2026-08-08 — PDF-001: `config/snappy.php` corregido tras verificar producción real

**Contexto:** `PDF-001` (severidad ALTO) estaba explícitamente bloqueado hasta verificar contra el servidor real, según `CLAUDE.md` sección 3/17 y el propio roadmap ("verificar primero contra el servidor/hosting real, no asumir nada sobre cPanel sin comprobarlo"). El repo tenía el binario de wkhtmltopdf hardcodeado a una ruta de Windows, pero el usuario reportó que la generación de PDF **funciona hoy** en `cactu.ec`.

**Verificación realizada:** el usuario (acceso File Manager de cPanel, sin SSH) confirmó tres datos puntuales pedidos explícitamente: (1) `config/snappy.php` en producción ya tiene activa la línea `'binary' => base_path('vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64')`, no la ruta de Windows; (2) no existe ninguna variable `WKHTMLTOPDF`/`SNAPPY`/`PDF_BINARY` en el `.env` de producción; (3) `vendor/h4cc/wkhtmltopdf-amd64/bin/` existe con el binario. Conclusión: producción tiene un `config/snappy.php` editado manualmente en el servidor y nunca commiteado de vuelta al repo — el repo había divergido silenciosamente de lo que realmente corre en producción.

**Decisión:** corregir el repo para que coincida exactamente con lo ya verificado como funcional en producción — `'binary' => base_path('vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64')` en la sección `pdf`, y el mismo patrón (`wkhtmltoimage-amd64`) en la sección `image` (no usada actualmente en el código de la app, pero con el mismo bug — se corrige por consistencia, cambio de una línea sin riesgo). **No se usó `env('WKHTMLTOPDF_BINARY', base_path(...))`** como sugería la ficha original de `PDF-001` — al confirmar que nada en producción sobreescribe este valor, agregar una capa de configuración vía variable de entorno que nadie usaría habría sido sobrearquitecturar sin necesidad concreta (`CLAUDE.md` sección 56).

**Cómo se verificó localmente (no solo se asumió tras el cambio):** `php artisan tinker` generando un PDF real vía `PDF::loadHTML(...)->output()` — el archivo resultante trae `Creator: wkhtmltopdf 0.12.4` en sus metadatos, confirmando que el binario efectivamente se ejecutó (no un fallback silencioso ni un error tragado). `tests/Feature/Seguridad/DescargaPdfCartaTest` (que ejercita la descarga real de PDF de una carta) sigue en verde.

**Qué NO se hizo:** no se tocó el hallazgo de "mover generación de PDF pesada a un Job" del roadmap de Fase 6 — al revisar el código real, los 3 usos de `PDF::loadView` (`WelcomeController`, `CartaController::verPDF` x2 versiones, `CartaController::descargarPdf`) son endpoints GET donde la respuesta HTTP es directamente el PDF (vista inline o descarga); no hay generación de PDF como efecto secundario de otra operación que pueda moverse a background. Se documenta esta corrección al roadmap en `docs/auditoria/problemas-detectados.md` (ficha `PERF-001`).

**Impacto:** corrige un riesgo real (ALTO) de que un futuro deploy desde git sobreescribiera el `config/snappy.php` divergente de producción con la versión rota del repo, rompiendo la función núcleo de Cartas. Sin cambio de comportamiento en producción (el repo ahora coincide con lo que ya corría ahí). Suite completa (53 tests) en verde.

---

## 2026-08-08 — INFRA-001: limpieza programada de `storage/app/temp`

**Contexto:** último ítem de Fase 6. `ResponderCartaController::crearArchivoTemporalDesdeBase64` ya borra cada archivo apenas lo usa (`finally`, desde el refactor de Fase 4/ARQ-001), pero eso no cubre el caso en que el proceso PHP muere antes de llegar ahí — cliente que corta la conexión a mitad de request (PHP aborta el script por defecto al detectar la desconexión), timeout de ejecución, u OOM del hosting. Sin ninguna tarea periódica, esos huérfanos se acumulan indefinidamente en un disco de cuota limitada (cPanel compartido) que además comparten `storage/logs`, la caché de archivos y las sesiones (`SESSION_DRIVER=file`) — el riesgo no es solo perder la función de subir fotos, es potencialmente romper login/caché/logs si se agota la cuota.

**Decisión:** se crea `app/Console/Commands/LimpiarArchivosTemporalesCommand.php` (`php artisan temp:limpiar {--horas=24}`), que recorre `storage/app/temp` con `Storage::files()`/`Storage::lastModified()` y borra lo más viejo que el umbral. Se registra en `app/Console/Kernel.php` vía `$schedule->command('temp:limpiar')->daily()->withoutOverlapping()`, reutilizando el mismo cron de `schedule:run` que ya requiere `PERF-001` — no hace falta una segunda entrada de Cron en cPanel.

**Por qué 24 horas de umbral:** el borrado normal tarda milisegundos (ocurre en el mismo request). 24 horas es un margen generoso que garantiza no tocar nunca un archivo legítimamente en uso, mientras sigue acotando la acumulación a, como mucho, un día de huérfanos.

**Qué NO se hizo:** no se investigó ni se intentó prevenir la causa raíz (por ejemplo, forzando `ignore_user_abort(true)` para que el `finally` siempre corra pese a una desconexión del cliente) — cambiar ese comportamiento global de PHP tiene otras implicancias (requests que el cliente ya abandonó siguen consumiendo un worker de PHP-FPM hasta terminar) que no se evaluaron y exceden el alcance de este hallazgo puntual.

---

## 2026-08-08 — Fase 7 (paso 1): arreglo puntual de `fcm_token` antes de la migración a tabla de dispositivos

**Contexto:** antes de iniciar Fase 7 (Firebase + Notificaciones) se auditó su estado real contra el código, sin asumir nada de lo que decía `docs/fases/roadmap.md`. Se confirmaron sus 3 puntos como vigentes y, de paso, se detectó un bug no documentado (`SEG-014`): el login sobrescribía `fcm_token` con `null` si el request no lo incluía. Se presentaron al usuario varias opciones de por dónde empezar Fase 7 (arreglo puntual vs. migración completa a tabla de dispositivos) y se autorizó explícitamente la más chica primero, siguiendo el mismo patrón de "un paso a la vez" usado en Fase 6.

**Decisión 1 — corregir `SEG-014` antes que cualquier otra cosa de Fase 7:** es el bug de mayor impacto real inmediato (pérdida silenciosa de la capacidad de notificar a un niño) y el de menor riesgo de corregir (una condición, sin tocar schema ni contrato).

**Decisión 2 — agregar `POST /actualizar-token-fcm` y `POST /eliminar-token-fcm` ya, sin esperar a la tabla de dispositivos:** el punto 3 del roadmap ("endpoint explícito para actualizar/eliminar el token fuera del login") no depende de la migración a tabla de dispositivos (punto 1) — ambos endpoints operan igual de bien sobre la columna `fcm_token` actual que sobre una futura relación uno-a-muchos (en ese caso, el mismo endpoint simplemente pasaría a upsertear/borrar la fila del dispositivo correspondiente en vez de un campo). No tiene sentido esperar a un cambio de schema mayor para resolver un gap que ya se puede cerrar hoy.

**Decisión 3 — convención de rutas `POST` en vez de verbos REST (`PATCH`/`DELETE`):** toda la API existente (`routes/api.php`) usa exclusivamente `POST` para escritura, incluyendo eliminaciones (`POST /eliminar-carta`). Se mantiene la misma convención por consistencia del contrato — introducir verbos REST solo para estos 2 endpoints nuevos habría sido inconsistente sin aportar valor real, y el roadmap no pidió estandarizar verbos HTTP.

**Decisión 4 — no crear una tabla de dispositivos todavía:** aunque el roadmap la pide como punto 1 de Fase 7, es un cambio de schema con coordinación necesaria con el equipo móvil (la app tendría que empezar a manejar múltiples tokens por niño) y no estaba autorizado en este paso. Se deja explícitamente pendiente en `docs/fases/roadmap.md` y `problemas-detectados.md` (`SEG-014`), para decidir por separado si el negocio realmente necesita soporte multi-dispositivo antes de construirlo (`CLAUDE.md` sección 56, no sobrearquitecturar).

**Impacto:** aditivo — no cambia la forma de ninguna respuesta existente. Suite completa (63 tests) en verde. Ver `docs/cambios/changelog.md` para el detalle de archivos.

**Impacto:** ninguno sobre comportamiento observable de las respuestas de carta. Cierra el último ítem de Fase 6. Test de regresión: `tests/Feature/Console/LimpiarArchivosTemporalesCommandTest.php` (verifica que borra solo lo viejo, nunca lo reciente, y que no falla si `storage/app/temp` no existe todavía). Suite completa (55 tests) en verde.

---

## 2026-08-08 — `FIREBASE-001`: migración a FCM HTTP v1 con `kreait/laravel-firebase`

**Contexto:** siguiendo la autorización de continuar con los 2 puntos pendientes de Fase 7 ("manejo de tokens inválidos y reintentos" + tabla de dispositivos), al investigar el código real antes de escribir el `try/catch` de reintentos se encontró que el envío usaba `kutia-software-company/larafirebase`, cuyo único transporte es la API legacy de FCM (`fcm.googleapis.com/fcm/send`, autenticada con server key) — **decomisionada por Google desde 2024** (verificado contra la documentación oficial de Firebase vía búsqueda externa, no asumido de memoria, ver fuentes en `problemas-detectados.md` `FIREBASE-001`). Sin ningún `try/catch` alrededor del envío, esto significaba que las notificaciones push probablemente llevaban ~2 años fallando en silencio. Se presentó el hallazgo al usuario antes de escribir código de reintentos (no tenía sentido reintentar contra un endpoint muerto) y se autorizó explícitamente migrar ya a HTTP v1.

**Decisión 1 — `kreait/laravel-firebase` en vez de `gg-innovative/larafirebase` (sucesor directo del paquete retirado) o una implementación manual:** de las 3 opciones presentadas, el usuario eligió `kreait/firebase-php` (vía su wrapper Laravel `kreait/laravel-firebase`). Es la librería más madura y activamente mantenida del ecosistema PHP para Firebase Admin SDK, con excepciones tipadas por tipo de error (`NotFound`, `ServerError`, `QuotaExceeded`, etc.) — precisamente lo que permite distinguir "token inválido, no reintentar" de "error transitorio, sí reintentar" sin tener que parsear respuestas HTTP a mano.

**Decisión 2 — `app/Services/FirebaseService.php` como primer Service del proyecto:** `CLAUDE.md` sección 15 lista literalmente `FirebaseService` como ejemplo de Service válido (integración externa compleja y reutilizable). Se prefirió esto a resolver `Kreait\Firebase\Contract\Messaging` directamente dentro de la Notification, porque el manejo de "token inválido → limpiar" es una regla de negocio (qué hacer cuando Firebase rechaza un token) independiente del mecanismo de Notification/canal de Laravel, y previsiblemente reutilizable cuando exista la tabla de dispositivos (punto 1 de Fase 7, aún pendiente) o si en el futuro otro flujo necesita notificar por push.

**Decisión 3 — canal de Notification propio (`app/Notifications/Channels/FirebaseChannel.php`) en vez de seguir con el nombre de canal `'firebase'` que aportaba el paquete retirado:** se mantiene el mismo nombre de canal string (`'firebase'`, usado en `EnviarNotificacionCartaNueva::via()`) para no tocar esa parte del código, pero el driver detrás se reescribe por completo y se registra a mano en `AppServiceProvider::boot()` (mismo patrón que usaba `LarafirebaseServiceProvider::register()` del paquete viejo, `ChannelManager::extend()`). Evita depender de que un paquete de terceros exponga el canal.

**Decisión 4 — `NotFound` es la única excepción que se captura y no relanza:** es la excepción específica que el SDK de kreait lanza cuando Firebase confirma que el token ya no está registrado (`Kreait\Firebase\Exception\Messaging\NotFound::becauseTokenNotFound()`, confirmado leyendo `vendor/kreait/firebase-php/src/Firebase/Messaging.php:211`). Cualquier otra excepción (`ServerError`, `QuotaExceeded`, `ApiConnectionFailed`, etc.) se deja propagar deliberadamente — capturarlas todas habría silenciado errores que sí ameritan reintento.

**Decisión 5 — reintentos vía `$tries`/`$backoff` en la Notification, no un bucle manual dentro de `FirebaseService`:** Laravel ya propaga estas propiedades del `Notification` al job `SendQueuedNotifications` que lo envuelve (confirmado leyendo `vendor/laravel/framework/src/Illuminate/Notifications/SendQueuedNotifications.php:81-83`), y el patrón cron-driven de `queue:work --stop-when-empty` ya establecido en `PERF-001` ya sabe reprocesar jobs fallidos. Reimplementar reintentos a mano dentro del Service habría sido duplicar un mecanismo que el framework ya resuelve (`CLAUDE.md` sección 56, no sobrearquitecturar).

**Qué NO se hizo:** no se migró `Ninio.fcm_token` a una tabla de dispositivos — sigue siendo el único punto abierto de Fase 7, con la misma razón que `SEG-014`: cambia schema y requiere coordinar con el equipo móvil, decisión de negocio aparte (`CLAUDE.md` sección 56).

**Actualización (mismo día) — verificación end-to-end realizada:** el usuario cargó sus credenciales reales de cuenta de servicio en el entorno local (`storage/app/firebase/service-account.json`, no versionado) y se completó la verificación en 3 pasos crecientes: (1) `validate_only` con token inventado → confirma autenticación aceptada; (2) `validate_only` con un token real pero desactualizado → `NotFound`, confirma conectividad real y que `FirebaseService` lo habría limpiado; (3) envío real (sin `validate_only`) a un `fcm_token` fresco de un dispositivo con `CactuApp` real, con confirmación del usuario de haber recibido la notificación en el teléfono. No fue necesario relajar ninguna de las decisiones de arriba para lograrlo — el código escrito bajo esas decisiones funcionó sin cambios contra Firebase real.

**Impacto:** ninguno en el contrato Web/API/React Native — es un cambio interno de cómo el backend envía pushes, no de qué recibe el móvil. Suite completa (68 tests) en verde. Ver `docs/cambios/changelog.md` para el detalle de archivos y `docs/auditoria/problemas-detectados.md` (`FIREBASE-001`) para el hallazgo completo con fuentes.

---

## 2026-08-08 — `FIREBASE-002`: tabla de dispositivos para multi-dispositivo por niño

**Contexto:** único punto pendiente de Fase 7. Antes de diseñar nada se le preguntó explícitamente al usuario si el negocio realmente necesitaba soportar multi-dispositivo por niño, o si era solo el objetivo aspiracional de `CLAUDE.md` sección 25 sin caso de uso real (`CLAUDE.md` sección 56, no sobrearquitecturar). El usuario confirmó que sí hace falta. Se inspeccionó también `CactuApp` (repo móvil) para no asumir nada: la app no tiene ningún concepto de dispositivo hoy (sin device id, sin `onTokenRefresh`, sin multi-sesión) — el diseño del backend tuvo que funcionar sin depender de que el móvil aporte algo que no tiene.

**Decisión 1 — el `fcm_token` mismo es la clave del dispositivo, no se inventa un `device_id`:** dado que `CactuApp` no provee ningún identificador de instalación propio, y el token FCM ya es efectivamente único por instalación de la app, agregar un `device_id` generado por el backend habría sido una capa sin ningún consumidor real que la llenara con información útil. `Dispositivo::fcm_token` es `unique`.

**Decisión 2 — `RegistrarDispositivoAction` con upsert por token, no solo un `create`:** al diseñar la migración de datos se corrió una consulta contra la base real y se encontraron **144 grupos de `fcm_token` duplicados entre 843 filas de `ninios`** — hermanos patrocinados que comparten un único teléfono familiar, cada uno con su propio `numero_child`. Esto confirmó con evidencia concreta (no una hipótesis) que un simple `create()` habría violado la unicidad del token en el primer login real después de desplegar. `Dispositivo::updateOrCreate(['fcm_token' => $token], ['ninio_id' => $ninio->id])` resuelve el caso reasignando el dispositivo al niño que se logueó más recientemente desde ese teléfono — el mismo comportamiento que ya tenía el sistema con la columna simple (el último login "ganaba"), pero ahora explícito y sin perder al niño que sí sigue usando otro dispositivo.

**Decisión 3 — la migración de datos backfillea 1 dispositivo por token distinto, no por fila de `Ninio`:** de 2138 registros de `Ninio` con `fcm_token` no nulo, solo 1439 valores eran distintos. Para cada grupo duplicado se asigna el dispositivo al `Ninio` con `updated_at` más reciente (login más reciente = dueño actual del teléfono), replicando exactamente el criterio de "último login gana" que el sistema ya tenía, en vez de inventar un criterio nuevo al migrar datos históricos.

**Decisión 4 — no se dropea `ninios.fcm_token` en esta rama:** el entorno de producción real es cPanel compartido con deploy manual, sin blue/green ni rollback de base de datos sofisticado (`CLAUDE.md` sección 50). Un `DROP COLUMN` es difícil de revertir a mano si algo sale mal con el flujo nuevo recién desplegado. Se prefirió separar en dos decisiones de riesgo distinto (`CLAUDE.md` sección 48): esta rama es aditiva y reversible por código (`git revert` sin tocar la BD); el drop queda para una migración separada, con autorización explícita nueva, después de confirmar estabilidad en producción. Mientras tanto, `GET /user` y `POST /login` no cambian de forma en ningún byte de su respuesta — cero riesgo de romper algo que ya esté leyendo ese campo.

**Decisión 5 — `POST /eliminar-token-fcm` cambia de contrato sin ceremonia adicional:** ahora exige `fcm_token` en el body (antes borraba "el único que había", sin body). Se aceptó el cambio de contrato sin versionar ni mantener compatibilidad hacia atrás porque el endpoint sigue sin consumidor móvil real (confirmado de nuevo al inspeccionar `CactuApp`) — no hay a quién romper. Se documenta el contrato nuevo en `docs/movil/*` para cuando el equipo móvil lo implemente.

**Decisión 6 — sin columnas de metadata de dispositivo (`plataforma`, `nombre`, `last_seen_at`):** `CactuApp` no manda nada de eso hoy y no hay ningún caso de uso concreto que lo necesite todavía (`CLAUDE.md` sección 56, YAGNI). El `updated_at` de `dispositivos` ya cumple el rol de "última vez visto" a través del upsert. Si en el futuro hace falta, se agrega en una migración separada cuando exista el caso de uso real.

**Qué NO se hizo:** no se tocó el repositorio móvil `CactuApp` — queda fuera de alcance, es trabajo pendiente y con gobernanza propia de ese repo (implementar `onTokenRefresh`, llamar `eliminar-token-fcm` en un futuro logout). No se dropeó la columna vieja (Decisión 4). No se agregaron metadatos de dispositivo (Decisión 6).

**Impacto:** ninguno en el contrato Web/API observable (`GET /user`, `POST /login` sin cambios de forma). `POST /eliminar-token-fcm` cambia de contrato sin consumidor real. Suite completa: 75/75 tests en verde. Ver `docs/cambios/changelog.md` para el detalle de archivos y `docs/auditoria/problemas-detectados.md` (`FIREBASE-002`) para el hallazgo completo.

---

## 2026-08-08 — `UX-002`: por qué un partial de `<head>` compartido y no una reescritura de layouts

**Contexto:** la auditoría de Fase 3 (`docs/auditoria/ux-ui-actual.md`) encontró que `layouts/app.blade.php` (panel) y `responder-cartas/principal.blade.php` (flujo público sin autenticación) duplicaban ~90 líneas de `<head>` casi idénticas, con una divergencia real ya producida (jQuery cargado desde un CDN público solo en el segundo).

**Decisión — extraer un `@include('sections.head')` compartido, no fusionar ambos layouts en uno solo con `@yield`/secciones intercambiables de `<body>`:** se evaluó la alternativa de un único layout base del que ambos heredaran (patrón más "correcto" en abstracto), pero se descartó por desproporcionada para el problema real (`CLAUDE.md` sección 56, no sobrearquitecturar): el `<body>` de ambos layouts es genuinamente distinto (panel con sidebar/navbar vs. tarjeta pública centrada), no hay nada que ganar unificándolos más allá del `<head>`, y forzar una jerarquía de herencia nueva habría tocado más superficie de código (y por lo tanto más riesgo de romper algo visible) para resolver exactamente el mismo problema de duplicación que un `@include` resuelve con dos líneas de cambio por archivo.

**Decisión — se corrige el jQuery del CDN como parte del mismo cambio, no se documenta como pendiente aparte:** al construir el partial había que elegir una sola fuente de jQuery para los dos layouts. Mantener el CDN habría significado introducir esa misma dependencia externa en el panel administrativo (regresión), y mantener dos `<script src>` de jQuery distintos dentro de un único partial compartido no tiene sentido. Usar el archivo local en ambos (`assets/js/jquery/jquery.min.js`) es el único resultado consistente con el objetivo de "un solo `<head>`", y de paso resuelve la dependencia de red evitable en el flujo público real de sponsors.

**Qué NO se hizo:** no se tocó el `<body>` de ningún layout — cero cambio visual esperado en ninguno de los dos. No se investigaron los 16 tests que fallan en el árbol de trabajo actual (`TEST-002`, confirmado con `git stash` que no los causa este cambio) — queda fuera del alcance de UX/UI autorizado para esta sesión.

**Impacto:** ninguno en el contrato Web/API/móvil — son vistas Blade internas, sin consumidores de API ni de `CactuApp`. Ver `docs/cambios/changelog.md` para el detalle de archivos y pruebas, y `docs/auditoria/problemas-detectados.md` (`UX-002`) para el hallazgo completo.

---

## 2026-08-08 — `UX-001`: por qué se retira "v2" en vez de completarla o documentar su coexistencia

**Contexto:** la plantilla PDF "v2" (`resources/views/cartas/v2/`) llevaba tiempo coexistiendo con "v1" sin un criterio documentado de cuándo usar cada una (`CLAUDE.md` sección 3 exige entender el comportamiento actual antes de decidir). La auditoría de Fase 3 confirmó en código que la decisión de negocio nunca se había tomado en la práctica: v2 no estaba conectada a ningún flujo real (ni la descarga oficial ni el enlace público que reciben los patrocinadores la usaban), solo era visible en una comparación interna lado a lado en `cartas/show.blade.php`.

**Decisión — retirar v2 en vez de terminarla o mantener la coexistencia documentada:** de las tres soluciones que planteaba el hallazgo original (documentar el criterio de uso, completar v2 y migrar, o retirar v2), el usuario decidió directamente la tercera: el cliente no aprobó el diseño de v2. No había ambigüedad que resolver con más análisis — era una decisión de negocio pendiente, no técnica, y una vez tomada no había motivo para mantener el código muerto ni la comparación interna.

**Decisión — se simplifica la ruta y se quita el parámetro `$version` en vez de dejarlo "por si acaso":** `CartaController::verPDF()` tenía un parámetro opcional exclusivamente para elegir entre v1/v2. Sin v2, mantenerlo habría sido una rama muerta esperando un valor que ya nunca llega (`CLAUDE.md` sección 56, no sobrearquitecturar / YAGNI). Se retira el parámetro de la firma del método y del segmento `{version?}` de la ruta, no solo el código interno de la rama `if`.

**Qué NO se hizo:** no se tocó `CartaController::descargarPdf()` ni `WelcomeController::descargarCartaPdf()` — ya usaban v1 exclusivamente antes de este cambio, sin ninguna referencia a v2 que limpiar. No se dejó ningún alias ni redirección desde la ruta vieja con `{version}` — no tenía consumidores externos (confirmado en la auditoría: solo la comparación interna en `cartas.show` la usaba), así que no aplica la regla de compatibilidad hacia atrás de `CLAUDE.md` sección 38.

**Impacto:** ninguno para el usuario final (patrocinadores/niños) — el flujo que ya veían (v1) no cambió en nada. El único cambio visible es interno: `cartas.show` ya no ofrece la comparación v1/v2. Ver `docs/cambios/changelog.md` para el detalle de archivos y pruebas, y `docs/auditoria/problemas-detectados.md` (`UX-001`) para el hallazgo completo.

---

## 2026-08-08 — `UX-005`: por qué `form-label` y no `form-floating` en el flujo público de responder-cartas

**Contexto:** el panel administrativo (`ninios/create.blade.php` y equivalentes) usa consistentemente `form-floating` de Bootstrap — la etiqueta "flota" dentro del campo y se reduce arriba cuando el campo tiene foco o contenido. La auditoría de Fase 3 sugería alinear el flujo público de `responder-cartas` a ese mismo patrón, por ser el estándar ya maduro del sistema.

**Decisión — usar `form-label` (etiqueta fija arriba del campo) en vez de `form-floating`:** al releer las 5 vistas completas antes de tocar código, se encontró que varias etiquetas de estos formularios públicos son oraciones completas, no palabras o frases cortas: "Cuéntale por qué quisieras que te conteste tu patrocinador, envíale un mensaje de despedida", "Lo que me gustaría aprender en el programa de ChildFund es", etc. `form-floating` de Bootstrap está diseñado específicamente para etiquetas cortas tipo "Email" o "Nombre" — con una oración larga, el texto se corta o se ve mal dentro del campo reducido. Replicar mecánicamente el patrón del panel admin sin adaptarlo al contenido real habría sido peor UX que el problema original, no mejor (`CLAUDE.md` sección 3: entender el comportamiento actual antes de decidir, no asumir que "más parecido al panel" es automáticamente "mejor").

**Decisión — preservar el typo `mifamila` y la diferencia real entre archivos (iframe de PDF activo solo en `contestacion`):** ambos eran candidatos "obvios" a corregir al ver el código, pero investigar antes de tocar (`CLAUDE.md` sección 54, código ambiguo) reveló que `mifamila` está documentado en el propio `ResponderCartaPresentacionMenorRequest` como un contrato compartido con `CactuApp` — corregirlo rompería el móvil sin coordinación previa. La diferencia del iframe (activo en `contestacion`, comentado en `agradecimineto`/`iniciada`) no tenía ninguna explicación documentada de si era intencional o un descuido; ante la duda, se preservó el comportamiento existente de cada archivo tal cual, sin decidir por el usuario algo que no se pidió decidir.

**Qué NO se hizo:** no se agregaron iconos (`form-control-feedback-icon`) como sí tiene el panel admin en varios campos — con ~90 campos en total entre las 5 vistas, elegir un ícono representativo para cada uno habría sido trabajo de diseño no pedido y de bajo valor relativo (`CLAUDE.md` sección 56, no sobrearquitecturar). Se prefirió consistencia simple (label + control) sobre paridad visual exacta con el panel.

**Impacto:** ninguno en el contrato Web/API/móvil — son vistas Blade del canal Web público, sin relación con `Api\ResponderCarta` ni con la UI nativa de `CactuApp`. Ver `docs/cambios/changelog.md` para el detalle de archivos y pruebas, y `docs/auditoria/problemas-detectados.md` (`UX-005`) para el hallazgo completo.

---

## 2026-08-08 — `SEG-015`: por qué sesión corta + rate limiting, y no una contraseña nueva

**Contexto:** el usuario reportó, correctamente, que cualquiera con el enlace de `responder-cartas` podía potencialmente responder en nombre de un niño. Antes de diseñar nada se investigó cómo protege este mismo tipo de acceso la app móvil (`CactuApp`), para no asumir que "hacer como el móvil" automáticamente arreglaba algo.

**Decisión 1 — no agregar una contraseña/PIN nuevo, y no tocar `SEG-012`:** la investigación reveló que la app móvil usa exactamente la misma credencial débil que la web (`numero_child` sin segundo factor) — no había nada "más seguro" del lado móvil que replicar. Agregar una contraseña habría sido resolver `SEG-012` (ya documentado como decisión de negocio pendiente, dado el público objetivo: niños patrocinados sin capacidad de gestionar contraseñas) sin que el usuario lo pidiera ni lo autorizara explícitamente. Se presentó esta distinción con evidencia antes de proponer soluciones, y el usuario confirmó no tocar `SEG-012`.

**Decisión 2 — sesión corta (45 min) en vez de dejar `numero_child` en la URL, sin inventar un token nuevo:** la vulnerabilidad real no era la fortaleza de la credencial (idéntica en ambos canales) sino que la web la dejaba embebida en una URL persistente, cacheable y reenviable indefinidamente, sin ninguna noción de expiración o revocación. Mover el número a una ventana de sesión del servidor (en vez de, por ejemplo, generar un JWT o un link firmado de un solo uso) fue la solución más simple que cierra ese problema específico: no agrega infraestructura nueva (usa el sistema de sesiones que Laravel ya provee), y sigue siendo consistente con el modelo de "solo `numero_child` como credencial" que `SEG-012` dejó como aceptado.

**Decisión 3 — `ver()` y el redirect de éxito abren/renuevan la sesión, no solo `ingresar()`:** un niño puede llegar a responder una carta por dos caminos distintos: (a) entrando manualmente por el formulario nuevo, o (b) tocando el link directo de una notificación push (`EnviarNotificacionCartaNueva`, que usa `cartas-ninio.ver` con `idcarta`+`numero_child` cifrados, sin pasar por `index()`/`ingresar()`). Ambos caminos ya demuestran la identidad del niño mediante el `numero_child` cifrado — exigir que también hubieran pasado por el formulario habría sido pedir una prueba redundante de algo que el sistema ya verificó, y habría roto la experiencia de "tocás la notificación y ves/respondés directamente" que la app ya ofrece. Se optó por reutilizar esa prueba ya hecha para poblar la sesión, no por debilitar el chequeo.

**Decisión 4 — rate limiting de 5/min por IP, no por número o por sesión:** limitar por IP (y no, por ejemplo, por `numero_child` intentado) es lo que realmente frena la enumeración — un atacante que prueba miles de números desde una sola IP se topa con el límite sin importar qué número pruebe. Limitar por sesión/cookie habría sido trivial de esquivar (borrar cookies y seguir probando).

**Bug encontrado al investigar, no buscado — corregido en el mismo cambio:** los enlaces "Regresar a mis cartas" y el redirect de éxito de responder pasaban un `numero_child` **cifrado** a una ruta que esperaba el valor **plano** — un family que respondía una carta caía en un 404 real en producción en vez de ver la confirmación. Se decidió arreglarlo junto con `SEG-015` en vez de registrarlo aparte y diferirlo, porque es el mismo método (`procesarRespuesta()`) que ya se estaba modificando para la sesión, y dejarlo roto un tiempo más no tenía ninguna ventaja.

**Qué NO se hizo:** no se agregó throttle a `ver()`, `verArchivo()` ni a los `guardarX()` — esos endpoints ya exigen un token cifrado (`Crypt::encryptString`) no adivinable por fuerza bruta, un tipo de protección distinto y ya adecuado; agregarles rate limiting no habría cerrado ninguna brecha real. No se tocó el repositorio `CactuApp` — el único cambio del lado API es el rate limit de `/login`, sin alterar el contrato request/response.

**Impacto:** ninguno en el contrato de datos observable — mismas rutas de negocio (`guardarContestacion`, etc.), mismos campos, misma autorización (`CartaPolicy::responderCartaXninio`, `SEG-006`). El único cambio de comportamiento visible es el paso adicional de "ingresar tu número" antes de ver las cartas, y que responder una carta ya no termina en un error. Ver `docs/cambios/changelog.md` para el detalle de archivos y pruebas, y `docs/auditoria/problemas-detectados.md` (`SEG-015`) para el hallazgo completo.

---

## 2026-08-08 — `SEG-015` (parte 2): por qué el link cifrado directo también pasa a exigir sesión

**Contexto:** el usuario revisó el cierre inicial de `SEG-015` y señaló, con razón, que el link **directo** de una carta puntual (el que manda `EnviarNotificacionCartaNueva` por notificación/correo) seguía funcionando solo con el link cifrado — sin pasar por la sesión de `ingresar()`. Su ejemplo concreto: el correo se extravía, queda cacheado, o se reenvía por error, y quien lo tenga puede responder sin demostrar conocer el número.

**Decisión — extender el mismo mecanismo de sesión de la parte 1, no crear uno nuevo:** en vez de diseñar una protección distinta para `ver()`, se reutilizó exactamente `ninioDeSesion()` (la misma ventana de 45 min). Esto mantiene un solo concepto de "sesión de este flujo" en todo el controller, en vez de dos mecanismos de expiración/almacenamiento distintos a mantener.

**Decisión — redirigir a `ingresar()` con destino recordado, no simplemente romper el link:** la alternativa más simple habría sido que `ver()` sin sesión devolviera un 404 directo (igual que ante un link inválido) — pero eso trata "el link es legítimo pero falta la sesión" igual que "el link es falso", cuando son casos distintos: en el primero, la familia sí tiene un link real, solo le falta un paso. Guardar la URL como destino pendiente (`SESION_INTENDED`) y redirigir de vuelta a esa carta puntual tras `ingresar()` mantiene la experiencia de "un click desde la notificación" con un solo paso intermedio, en vez de mandar a la familia a buscar la carta de nuevo en una lista.

**Decisión — `procesarRespuesta()` (la acción real) también exige sesión, no solo `ver()` (la página):** proteger únicamente la vista y dejar la acción de guardar sin el mismo chequeo habría sido resolver el problema solo a medias — alguien con los campos cifrados capturados de un HTML viejo (cacheado, guardado, inspeccionado) podría haber seguido enviando la respuesta por POST directo sin pasar nunca por `ver()`. El chequeo se agregó en el mismo lugar que ya validaba `SEG-006` (el `numero_child` cifrado del formulario), como una capa adicional, no un reemplazo.

**Decisión — `verArchivo()` corta con 404, no redirige:** a diferencia de `ver()` (una página completa que un humano navega), `verArchivo()` es un recurso embebido dentro de un `<img>`/`<iframe>` de esa misma página. Redirigirlo a una página HTML de login no tendría ningún efecto útil ahí (se rompería la imagen/iframe igual). Un `404` directo es el comportamiento correcto para un sub-recurso protegido.

**Decisión — no agregar una contraseña/PIN nuevo (de nuevo, como en la parte 1):** se volvió a evaluar y descartar por la misma razón — sería resolver `SEG-012` (decisión de negocio pendiente sobre si `numero_child` debe dejar de ser la única credencial, dado que los niños patrocinados no manejan contraseñas), no autorizado por el usuario. La sesión corta cierra el problema real (el link deja de ser autosuficiente) reutilizando la credencial ya aceptada, sin agregar fricción desproporcionada a un flujo que de por sí ya requiere un adulto ayudando (formularios de 18-25 campos).

**Impacto:** ninguno en el contrato de datos — mismas rutas, mismos campos, misma autorización de negocio. El cambio de comportamiento es exclusivamente que el link directo de una notificación, sin sesión previa, pide el número una vez antes de mostrar la carta. Ver `docs/cambios/changelog.md` para el detalle de archivos y pruebas, y `docs/auditoria/problemas-detectados.md` (`SEG-015`, parte 2) para el hallazgo completo.

---

## 2026-08-08 — `UX-007`: `welcome` se separa de `home` en vez de quedar como alias

**Contexto:** la auditoría Parte 0 (`docs/auditoria/ux-ui-dashboard-vistas.md`, sección 3) encontró que `welcome.blade.php` (la ruta pública `/`, sin autenticación) era literalmente `@include('home')` — el mismo contenido, layout de panel incluido, que ve un usuario logueado en su dashboard. Se le presentaron al usuario tres opciones (mantener el alias con limpieza menor, separar en landing propia, o dejarlo como decisión consciente de no tocar) sin asumir cuál preferiría, porque es una decisión de producto, no de código (`CLAUDE.md` sección 3).

**Decisión del usuario:** separar `welcome` en una landing pública propia.

**Decisión de implementación — reutilizar `sections/head.blade.php` en vez de duplicar el `<head>` de nuevo:** `UX-002` ya había cerrado exactamente este problema (dos layouts raíz con el `<head>` copiado a mano, que se desincronizó una vez). Crear un tercer archivo raíz (`welcome.blade.php`, junto a `layouts/app.blade.php` y `responder-cartas/principal.blade.php`) sin reutilizar ese partial habría reintroducido el mismo defecto que ya se corrigió. Los tres layouts raíz ahora comparten el mismo `<head>`.

**Decisión de implementación — agregar redirección `Auth::check()` en la ruta `/`:** antes no hacía falta, porque `welcome` y `home` eran el mismo contenido — daba igual a cuál "aterrizara" un usuario logueado. Al separarlos, sin la redirección un usuario autenticado que visite `/` (ej. por un link viejo, un favorito) vería la landing de marketing en vez de su dashboard, una regresión de UX. Se agregó el chequeo directamente en el closure de la ruta (`routes/web.php`), sin mover la lógica a un controller nuevo — es una condición de una línea, no justifica una capa adicional (`CLAUDE.md` sección 56).

**Qué NO se hizo:** no se tocó `home.blade.php` ni `HomeController` — el dashboard real para usuarios autenticados queda exactamente igual (eso es alcance de `UX-008`, pendiente de que el usuario termine de definir los KPIs).

**Corrección tras revisión del usuario — se descarta el layout raíz independiente, se vuelve a `@extends('layouts.app')`:** el usuario vio el resultado (`<!DOCTYPE html>` propio, sin sidebar/navbar, calcado del patrón de `responder-cartas/principal.blade.php`) y lo rechazó: "se pierde la consistencia con las otras vistas como login". Es un punto correcto y más importante que la decisión inicial — `responder-cartas` es un flujo deliberadamente distinto (sin sesión de panel, un niño/familia respondiendo una carta desde un link), mientras que `welcome` es la puerta de entrada al mismo sistema que `login`, que sí usa `@extends('layouts.app')`. Tratarlos igual fue un error de criterio: se copió el patrón "página pública" equivocado. Se corrigió a `welcome.blade.php` con `@extends('layouts.app')`, igual que `login`, conservando contenido propio (no vuelve a ser `@include('home')`) pero simplificado a pedido del usuario: una portada con degradado CSS y una ola en SVG inline (`currentColor`, sin depender de una imagen de cover real que no existe en el proyecto) en vez de las tarjetas de texto más densas de la primera versión.

**Lección para decisiones de layout futuras:** antes de crear o reutilizar un layout raíz distinto para una vista pública, verificar primero si esa vista es conceptualmente "el sistema, sin sesión todavía" (como `welcome`/`login`, que deben verse como el resto del panel) o "un flujo deliberadamente aparte" (como `responder-cartas`, pensado para un usuario externo sin cuenta que nunca verá el panel). Solo el segundo caso justifica un layout raíz propio.

**Segunda corrección — la portada con degradado y ola SVG también se descarta, contenido reducido a texto + cards centrados:** ya dentro de `layouts.app`, el usuario señaló dos problemas más sobre la portada (`cactu-cover`) que se había agregado: (1) el logo `logo_icon.svg` es la variante clara pensada para el navbar oscuro — sobre el degradado se veía mal en tema claro (invisible/desentonado), y (2) la sucesión vertical de tres bloques visualmente distintos (portada con degradado → fila de tarjetas → botón) se leía como "tres páginas divididas horizontalmente" en vez de un solo bloque de contenido. Se quitó la portada por completo (degradado, ola SVG, `logo_icon.svg`) y se dejó solo: logo (`logo_icon_azul.svg`, la variante correcta para fondo claro, ya usada en el `home.blade.php` original), título, descripción, las dos tarjetas con ícono, y el botón de login — todo dentro de un único contenedor `d-flex flex-column` centrado vertical y horizontalmente (`justify-content-center align-items-center`, `min-height: 60vh`). Cero CSS custom nuevo: todo con utilidades de Bootstrap ya usadas en el resto del proyecto, así el resultado respeta automáticamente el tema claro/oscuro sin tener que mantener colores a mano.

**Impacto:** un visitante sin sesión ve una landing más simple, sin el layout de panel ni el banner interno de "Actualización Importante" (que no tenía sentido mostrarle). Un usuario autenticado que visite `/` ahora se redirige a `/home` en vez de ver la landing pública — antes no ocurría porque eran la misma vista. Sin impacto en API ni en `CactuApp`. Ver `docs/cambios/changelog.md` y `docs/auditoria/problemas-detectados.md` (`UX-007`) para el detalle de archivos y pruebas.

---

## 2026-08-08 — `UX-008`: KPIs del dashboard — alcance por rol y definición de "niño sin carta"

**Contexto:** la auditoría Parte 0 (`docs/auditoria/ux-ui-dashboard-vistas.md`, secciones 4 y 5) dejó explícitamente pendientes tres preguntas de negocio antes de diseñar el dashboard nuevo: qué métricas mostrar, si debían diferenciarse por rol, y si algún cálculo requería cachearse. El usuario ya había definido las métricas en una sesión anterior (cartas pendientes/enviadas por responder, niños por comunidad/sin carta reciente); en esta sesión se confirmaron los tres puntos que quedaban abiertos.

**Decisión — "niño sin carta reciente" se define como "nunca ha recibido ninguna carta", no un umbral temporal (6 meses / 1 año):** se plantearon las tres opciones al usuario y eligió la más simple. Un umbral temporal (ej. "sin carta en los últimos 6 meses") es ambiguo sin una regla de negocio real detrás de por qué 6 meses y no otro número, y ese número no existe hoy en ningún lugar del sistema — inventarlo habría sido una regla de negocio no autorizada (`CLAUDE.md` sección 3). "Nunca ha recibido carta" es una condición binaria y verificable directamente (`whereDoesntHave('cartas')`), sin parámetros que mantener.

**Decisión — reusar las relaciones ya existentes en `User` (`cartas()`, `ninios()`, `comunidades()`) para el alcance de `GESTOR`, en vez de reconstruir las queries manuales que usa `CartaDataTable`/`NinioDataTable`:** esas relaciones ya existían en `app/Models/User.php` desde antes de esta tarea pero no se usaban en el dashboard. Usarlas directamente (`$user->cartas()->where(...)`, `$user->ninios()->count()`) es más corto y menos propenso a divergir del alcance real que reescribir el mismo filtro `whereIn('comunidad_id', ...)` una tercera vez (ya está en `NinioDataTable` y, de otra forma, en `CartaDataTable`).

**Decisión — agregar `Comunidad::ninios()` (hasMany) en vez de armar el desglose por comunidad con una query manual agrupada:** `Ninio::comunidad()` (belongsTo) ya existía; la inversa nunca se había necesitado hasta este KPI. Agregarla es una relación estándar de Eloquent, de una línea, que permite `withCount('ninios')` tanto para el `ADMINISTRADOR` (`Comunidad::query()`) como para el `GESTOR` (`$user->comunidades()`) con el mismo código — la alternativa (un `groupBy` manual con `join`) habría sido más código para el mismo resultado y no habría quedado disponible para reutilizarse en otro lugar del sistema.

**Decisión — sin cache para estos cálculos:** la pregunta original de la auditoría (sección 5) planteaba si algún KPI requeriría cachearse por ser pesado. Los cuatro son `COUNT` simples sobre columnas indexadas (`estado`, `comunidad_id`, `ninio_id`) sin agregaciones cruzadas costosas; no hay evidencia de que sean lentos en producción. Se deja sin cache — agregar uno sin medir primero un problema real de performance habría sido complejidad no justificada (`CLAUDE.md` sección 56).

**Decisión — se elimina la tarjeta de logo/descripción de CACTU y la rama `@else`/`@guest` del dashboard, no solo el banner y las tarjetas decorativas:** el usuario pidió borrar "todo el contenido actual" para reemplazarlo por KPIs; al revisar qué quedaba después de sacar el banner y las tarjetas de "Acceso Rápido", la tarjeta de logo/descripción resultó redundante con la landing pública que ya cumple ese rol (cerrada en `UX-007`), y la rama `@else` nunca se ejecuta en la práctica porque la ruta `home` está dentro del grupo de middleware `auth` (`routes/web.php`) — quedaba de cuando esta vista se compartía con `welcome`. Se sacaron ambas para no dejar código muerto ni contenido duplicado con otra vista ya resuelta.

**Impacto:** el dashboard post-login pasa a mostrar KPIs reales con el mismo alcance por rol que el resto del sistema, sin agregar mecanismos nuevos (cache, columnas, tablas) — todo se calcula con relaciones y columnas que ya existían. Sin impacto en API ni en `CactuApp`. Ver `docs/cambios/changelog.md` y `docs/auditoria/problemas-detectados.md` (`UX-008`) para el detalle de archivos y pruebas.
