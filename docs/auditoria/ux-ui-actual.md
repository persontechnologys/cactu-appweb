# Auditoría UX/UI actual — CACTU (Fase 3, punto 1)

**Fecha:** 2026-08-08
**Rama:** `security/fase-2-hallazgos-criticos`
**Alcance:** frontend web (`resources/views`, `public/assets`, `resources/js`, `resources/sass`, `package.json`). No cubre `CactuApp` (React Native), que tiene su propio stack de UI independiente.
**Método:** lectura directa del código (Blade, rutas, controllers) y `git log`/`git diff` sobre los archivos involucrados. No se asumió nada de auditorías o documentación previas sin confirmarlo contra el repositorio real, según exige `CLAUDE.md` sección 3.

---

## 1. Resumen ejecutivo

El hallazgo más importante de esta auditoría **cambia el marco de la Fase 3**: CACTU **ya usa el template Limitless** como base visual del panel administrativo. No es una migración por hacer — está integrado desde el primer commit del repositorio (`d176b8a`), vía los assets estáticos comprados en `public/assets/` (Bootstrap 5, iconos Phosphor, JS del tema) cargados directamente en `resources/views/layouts/app.blade.php`. El layout ya tiene sidebar, navbar, breadcrumbs, selector de tema claro/oscuro/auto y el patrón de formularios `form-floating` de Limitless, y los 5 módulos de gestión (cartas, niños, usuarios, comunidad, mis-niños) ya lo usan de forma consistente.

Lo que sí falta, y es lo que da contenido real a "modernizar con Limitless", es:

1. **Una segunda copia del layout completo** (`responder-cartas/principal.blade.php`) que duplica ~90 líneas de `<head>` del layout principal en vez de reutilizarlo — con divergencias que ya causaron una inconsistencia real (carga jQuery desde un CDN público en vez del archivo local).
2. **El flujo público de "responder carta"** (el que de verdad usan los patrocinadores, sin login) tiene visiblemente menos pulido que el panel admin: HTML sin estructurar (`<h1>`, `<h3>` sueltos), `<label>` sin asociar a `form-floating`, y una clase CSS inexistente (`class="form-control input "`) que no hace nada.
3. **Un patrón de "acciones por fila" idéntico duplicado 4 veces** (`cartas/action.blade.php`, `usuarios/action.blade.php`, `comunidad/action.blade.php`, `mis-ninios/action.blade.php`) sin extraer a un componente Blade — de hecho, **no existe un solo Blade Component (`<x-...>`) en todo el proyecto**, `app/View/Components` ni siquiera existe.
4. **`mis-ninios/index.blade.php` rompe el patrón de los otros 4 listados**: usa una tabla HTML cruda + DataTables client-side con el idioma español hardcodeado inline, mientras `cartas`, `ninios`, `usuarios` y `comunidad` usan Yajra DataTables server-side (`$dataTable->table()`).
5. **UX-001 confirmado y precisado**: la plantilla PDF "v2" no está en ningún camino de producción real (ni la descarga oficial ni el enlace público que reciben los patrocinadores la usan) — es un intento de rediseño visual que quedó a medio camino, con al menos un archivo huérfano (`cartas/v2/pdf-presentacion-fixed.blade.php`, nunca referenciado por nada).

Ninguno de estos puntos es urgente ni bloquea nada — son deuda de consistencia, no bugs. Se detallan con evidencia de código en las secciones siguientes.

---

## 2. Stack CSS/JS realmente en uso

**Lo declarado en `package.json` (Vite) no es lo que se sirve al navegador.** `package.json` declara `bootstrap ^5.2.3`, `sass`, `laravel-vite-plugin`, y existen `resources/sass/app.scss` y `resources/js/app.js`, pero la línea `@vite(['resources/sass/app.scss', 'resources/js/app.js'])` está **comentada** en los dos layouts que existen (`layouts/app.blade.php:47`, `responder-cartas/principal.blade.php:47`). El pipeline de Vite es código muerto en la práctica — no se confirmó que `npm run build` se ejecute en ningún flujo de despliegue.

Lo que realmente se carga (vía `<link>`/`<script>` directos a `public/assets/`, el template Limitless comprado):

| Librería | Origen | Uso real |
|---|---|---|
| Bootstrap 5 (bundle compilado del tema) | `assets/js/bootstrap/bootstrap.bundle.min.js` | Base de todo el layout, grid, modales, dropdowns, offcanvas |
| Iconos Phosphor | `assets/icons/phosphor/styles.min.css` | Set de iconos principal — **179 usos** de clases `ph-*` en las vistas propias |
| Font Awesome | `assets/icons/fontawesome/css/all.min.css` | Cargado globalmente pero casi sin uso — **11 usos** de `fa-*`, la mayoría el ícono del spinner de carga (`fas fa-spinner fa-spin`) en los diálogos de `jquery-confirm` |
| jQuery | `assets/js/jquery/jquery.min.js` (local) en el layout principal; **CDN público** `code.jquery.com/jquery-3.7.0...` en `responder-cartas/principal.blade.php:28` | Base de toda la interacción (validación, DataTables, confirm, AJAX) |
| DataTables (Yajra + `datatables.min.js`) | `assets/js/vendor/tables/datatables/datatables.min.js` | Listados server-side en cartas/ninios/usuarios/comunidad |
| jquery-validate + mensajes ES | `assets/js/vendor/validate/` | Validación de formularios en cliente, configurado globalmente en el layout (`submitHandler`, clases `is-invalid`/`is-valid`) |
| jquery-confirm | `assets/js/vendor/jquery-confirm/` | Diálogo de "¿Está seguro de eliminar?" y confirmaciones de envío, usado en casi todos los formularios |

**Hallazgo secundario:** `responder-cartas/principal.blade.php` carga jQuery desde un CDN público en vez del archivo local que usa el resto del sistema — una dependencia de red externa evitable (y potencialmente rompible si el CDN cambia o no hay conexión) en el único flujo verdaderamente público (sin autenticación) de toda la aplicación.

---

## 3. Layout(s) existentes

**Hay exactamente dos layouts "raíz" y son casi idénticos por copia, no por herencia.**

### 3.1 `resources/views/layouts/app.blade.php` — layout del panel (admin/gestor)

Usado por `@extends('layouts.app')` en 15 vistas (cartas, ninios, usuarios, comunidad, mis-ninios, perfil, home, manual, privacidad). Estructura Limitless completa:
- Navbar superior con logo, menú de usuario (`sections/menu-header-auth.blade.php`) y botón de login si no hay sesión.
- Sidebar (`sections/menu.blade.php`) con navegación por rol (`@role('ADMINISTRADOR')` oculta Usuarios/Comunidades/Niños a gestores — **solo visual**, la protección real vive en las Policies, ya auditado en Fase 2).
- Header de página con breadcrumbs (`diglactic/laravel-breadcrumbs`) vía `@yield('breadcrumbs')`/`@yield('breadcrumb_elements')`.
- Alertas de sesión y errores de validación centralizadas (`sections/errors-alert.blade.php`).
- Footer (`sections/footer.blade.php`) con el enlace de descarga del APK de `CactuApp`.
- Offcanvas de configuración (`sections/config.blade.php`): tema claro/oscuro/auto y toggle de sidebar redimensionado (persistido por usuario vía `perfil.menu_responsivo`).
- Bloque JS global: configuración de `jquery-confirm`, `jquery-validate` (con `submitHandler` que bloquea el botón y muestra un spinner de "Procesando solicitud"), `$.ajaxSetup` con el token CSRF, y la función `eliminar()` reutilizada por **todas** las acciones de borrado del sistema.

**`sections/menu-header-auth.blade.php` tiene más de la mitad de sus 424 líneas comentadas** (bloques `{{-- ... --}}` de markup del demo original de Limitless: buscador, dropdown de mensajes, dropdown de apps, notificaciones) — funcionalidad del tema que nunca se activó ni se limpió. No es un problema funcional, pero es ruido para cualquiera que edite ese archivo, y es UI ya construida y disponible si en algún momento se decide usar notificaciones en la web (hoy sólo existen en la app móvil vía Firebase).

### 3.2 `resources/views/responder-cartas/principal.blade.php` — layout del flujo público

Usado por `@extends('responder-cartas.principal')` en `responder-cartas/index.blade.php` y `responder-cartas/todas-cartas.blade.php` — el flujo sin autenticación donde un niño/gestor entra con un enlace cifrado (`Crypt::encryptString`) para ver y responder cartas de sus patrocinadores.

**No hereda de `layouts/app.blade.php`.** Es un archivo separado que copia el `<head>` completo (fuentes, CSS, scripts core, el mismo bloque de configuración de `jquery-validate`/`jquery-confirm`/CSRF) y cambia solo el `<body>`: sin sidebar ni navbar, una tarjeta centrada con logo y el nombre del niño. Diffs concretos frente al layout principal:
- `jquery.min.js` local → CDN público (ver sección 2).
- `body class="bg-dark"` (el layout admin no la tiene).
- Sin `sections/menu`, sin `sections/menu-header-auth`, sin `sections/footer` (usa un footer propio de una línea).

Es un caso legítimo de necesitar una estructura visual distinta (página pública centrada vs. panel con sidebar), pero la manera en que está resuelto —copiar 90 líneas de boilerplate en vez de un layout base compartido con una sección de `<body>` intercambiable— es exactamente la duplicación que `CLAUDE.md` sección 30 pide evitar. Cualquier cambio futuro al `<head>` común (agregar una librería, cambiar una fuente, ajustar el CSRF setup) hay que aplicarlo dos veces a mano, y ya se desincronizó una vez (el jQuery del CDN).

### 3.3 Otros "layouts"

- `resources/views/errors/layout.blade.php`: layout minimalista propio para páginas de error HTTP (401/403/404/419/429/500/503), con CSS inline embebido, totalmente independiente de Limitless — correcto para su propósito (debe funcionar incluso si algo del layout principal falla).
- `resources/views/vendor/mail/*`: plantillas de correo publicadas de Laravel (Markdown mail), sin relación con Limitless.

---

## 4. Componentes/secciones reutilizables (`resources/views/sections/`)

| Archivo | Rol | Usado por |
|---|---|---|
| `menu.blade.php` | Sidebar de navegación, ítems condicionados por rol | `layouts/app.blade.php` (dentro de `@auth`) |
| `menu-header-auth.blade.php` | Contenido de la navbar cuando hay sesión (menú de usuario, logout) | `layouts/app.blade.php` |
| `footer.blade.php` | Pie de página con enlace a Privacidad y descarga del APK | `layouts/app.blade.php` |
| `config.blade.php` | Offcanvas de tema y configuración de sidebar + enlace al manual de usuario | `layouts/app.blade.php` y `responder-cartas/principal.blade.php` |
| `errors-alert.blade.php` | Alertas de sesión (`success`/`danger`/`info`/`primary`/`warning`/`dark`) + lista de errores de validación | `layouts/app.blade.php` (todas las vistas del panel la heredan automáticamente) |
| `alert.blade.php` | Variante de alerta suelta con `type`/`msg` como parámetros | `usuarios/create.blade.php` y `usuarios/edit.blade.php`, para avisar "No existe roles para crear usuario" cuando no hay roles cargados |
| `notifications.blade.php` | Notificaciones vía offcanvas | Referenciado en `layouts/app.blade.php:296` pero **comentado** (`{{-- @include('sections.notifications') --}}`) — no está activo |

**No existe ningún Blade Component (`<x-nombre>` / `App\View\Components`) en el proyecto.** Toda la reutilización se hace con `@include`, lo cual es consistente en sí mismo, pero significa que patrones que se repiten literalmente entre módulos (ver sección 5.2) no tienen ningún punto único de edición.

---

## 5. Vistas por módulo

### 5.1 Patrón de listados (index)

4 de 5 módulos de gestión usan **Yajra DataTables server-side**, con el mismo esqueleto:

```blade
@extends('layouts.app')
@section('breadcrumb_elements') ... enlace "Nuevo" ... @endsection
@section('content')
<div class="card"><div class="card-body"><div class="table-responsive">
    {{ $dataTable->table() }}
</div></div></div>
@push('scripts') {{ $dataTable->scripts() }} @endpush
```

Confirmado en `cartas/index.blade.php`, `ninios/index.blade.php`, `usuarios/index.blade.php`, `comunidad/index.blade.php`, respaldados por clases en `app/DataTables/` (`CartaDataTable`, `NinioDataTable`, `UserDataTable`, `ComunidadDataTable`).

**Excepción: `mis-ninios/index.blade.php`** no usa `$dataTable` de Yajra — usa una tabla HTML cruda (`<table class="table datatable-basic" id="ninio">`) inicializada con el plugin DataTables directamente en JS del lado cliente, y **hardcodea el diccionario de idioma español completo inline** (`"emptyTable": "No hay información"`, etc. — ~20 líneas) en vez de reutilizar una configuración compartida. Es el único listado del sistema con este patrón; funcionalmente equivalente mostrando los niños del gestor autenticado, pero técnicamente inconsistente con el resto.

**Excepción: `cartas/index.blade.php`** es notablemente más elaborado que los demás — agrega una tarjeta de filtros avanzados (tipo, estado, niño, comunidad, rango de fechas, gestor) con toggle, ~140 líneas de CSS inline en un `@push('styles')` y JS propio para recargar el DataTable por AJAX al cambiar los filtros. Es la única vista con esta funcionalidad; ninguno de los otros 3 listados (niños, usuarios, comunidades) tiene filtros más allá del buscador nativo de DataTables. El CSS inline redefine colores en hexadecimal (`#dee2e6`, `#0d6efd`, `#495057`) en vez de usar las variables CSS que el propio tema Limitless ya expone (`--bs-border-color`, `--bs-primary`, etc., visibles en `public/assets/css/all.min.css`) — funciona, pero no respeta el sistema de theming del tema (por ejemplo, no se probó cómo se ve con el tema oscuro que el propio layout ya soporta).

### 5.2 Patrón de "acciones por fila"

Cada listado incluye un partial `*/action.blade.php` como columna de la tabla, con un dropdown idéntico:

```blade
<div class="d-inline-flex">
    <div class="dropdown">
        <a href="#" class="text-body" data-bs-toggle="dropdown"><i class="ph-list"></i></a>
        <div class="dropdown-menu dropdown-menu-end">
            <a href="{{ route('...edit', $x->id) }}" class="dropdown-item"><i class="ph ph-pencil-simple me-2"></i>Editar</a>
            <a href="{{ route('...destroy', $x->id) }}" data-msg="..." onclick="event.preventDefault(); eliminar(this)" class="dropdown-item"><i class="ph ph-trash me-2"></i>Eliminar</a>
        </div>
    </div>
</div>
```

Presente, casi carácter por carácter, en `ninios/action.blade.php`, `usuarios/action.blade.php`, `comunidad/action.blade.php`, `mis-ninios/action.blade.php`. `cartas/action.blade.php` extiende el mismo esqueleto con más opciones (Ver, Descargar PDF, Documentos) más un popover de información con ~150 líneas de CSS propio. Es la duplicación más clara y de menor riesgo de extraer (candidato natural a un Blade Component `<x-tabla-acciones>` con slot para acciones extra), pero no es información nueva más allá de confirmar que el patrón se repite 4 veces exactas y no está roto en ningún módulo.

### 5.3 Patrón de formularios (create/edit)

Muy consistente entre `ninios`, `usuarios`, `comunidad`, `mis-ninios` y `cartas` (verificado en detalle sobre `ninios/create.blade.php`): tarjeta (`card`) con `form-floating` + `form-control-feedback-start` (icono Phosphor a la izquierda de cada campo) + manejo de errores por campo con `@error`/`is-invalid` + `jquery.validate` inicializado con `$('#form...').validate()`. Este es el patrón "de facto" del panel administrativo y es el más maduro del sistema — cualquier nueva vista de panel debería seguirlo, no reinventar.

### 5.4 Flujo público `responder-cartas/` (sin autenticación)

Layout propio (sección 3.2) + 5 vistas de "tipo de carta" (`agradecimineto.blade.php` [sic, con el typo de origen], `contestacion.blade.php`, `iniciada.blade.php`, `presentacion-mayores.blade.php`, `presentacion-menores.blade.php`) incluidas condicionalmente según `$carta->tipo`, más `index.blade.php` (bandeja de cartas pendientes) y `todas-cartas.blade.php`.

Es el único flujo de toda la aplicación donde interactúa directamente el destinatario final (el niño/familia, sin ser un usuario del sistema). Comparado con el panel administrativo, el nivel de pulido visual es notablemente menor:
- Usa etiquetas de bloque sueltas (`<h1>`, `<h3>`) fuera de cualquier estructura de `card`, en vez del patrón de tarjetas del resto del sistema.
- Los `<label>` no están asociados al patrón `form-floating` que sí se usa en el panel — son etiquetas planas sobre inputs simples.
- `class="form-control input "` — la clase `input` no existe en Bootstrap ni en el tema, es un resto sin efecto (verificado: no hay ningún selector `.input` en `public/assets/css/all.min.css` ni en `resources/sass`).
- Sí reutiliza correctamente el modal de Bootstrap y el flujo de `jquery.validate` + `jquery-confirm` del resto del sistema (captura de foto vía cámara del dispositivo con `jslib-html5-camera-photo`, confirmación antes de enviar).

Este es probablemente el módulo con mayor impacto real si se prioriza por "a quién se le muestra" más que por "quién lo pidió" — lo usan los niños/familias patrocinadas, no el staff.

---

## 6. UX-001 — Plantillas PDF "v1" vs "v2": estado real confirmado en código

Se verificó el código real (controllers, rutas, `git log`) en vez de asumir el enunciado ya registrado en `problemas-detectados.md`. **Confirma el hallazgo existente y agrega precisión sobre el alcance real de "v2":**

- `CartaController::verPDF($id, $version=null)` (única puerta de entrada a ambas versiones, ruta `cartas-ver-carta-pdf/{id}/{version?}`) usa `cartas.v2.verpdf` solo si se pasa explícitamente `version=v2`; por defecto (sin parámetro) usa `cartas.verpdf` (v1).
- `CartaController::descargarPdf()` — la descarga oficial, enlazada desde `cartas/action.blade.php` en cada fila del listado — **usa v1 sin ninguna opción de v2** (`app/Http/Controllers/CartaController.php:237`).
- `WelcomeController::descargarCartaPdf()` — el enlace público (ID cifrado, `Crypt::decryptString`) que reciben los patrocinadores fuera del panel — **también usa v1 exclusivamente** (`app/Http/Controllers/WelcomeController.php:34`).
- La **única** vista donde "v2" es alcanzable es `cartas/show.blade.php`, que muestra **las dos versiones lado a lado en dos iframes**, explícitamente rotuladas "(v1)" y "(v2)" (líneas 23 y 36) — es una vista de comparación interna para quien administra el sistema, no una elección que vea el patrocinador ni el niño.
- `resources/views/cartas/v2/pdf-presentacion-fixed.blade.php` **no está referenciado por ningún controller ni `@include`** (confirmado con `grep` en todo `app/` y `resources/`) — código muerto dentro del propio experimento "v2", creado en el mismo commit (`a93f366 "cartas v2"`) que el resto de la carpeta `v2/`.

**Conclusión:** "v2" no es una versión en proceso de reemplazar gradualmente a "v1" con evidencia de que cubra todos los casos — es un intento de rediseño visual (CSS más elaborado: gradientes, `@page`/`@media print`, ver diff en `pdf-contestacion.blade.php`) que **nunca se conectó a ningún flujo real** y quedó solo como comparación visual interna, con al menos un archivo abandonado a medio terminar. No hay evidencia de que deba completarse — la decisión pendiente (documentar el criterio de uso, o retirar v2 y quedarse solo con v1, o terminar v2 y migrar) sigue siendo una decisión de negocio/diseño del usuario, no algo que se pueda inferir del código. Este documento deja la evidencia lista para que esa decisión se tome con información completa.

---

## 7. Convenciones ya establecidas que cualquier trabajo de Fase 3 debería respetar

Para no "sobrearquitecturar" (`CLAUDE.md` sección 56) ni reinventar lo que ya funciona:

- **Layout único de panel:** `layouts.app`, con sidebar/navbar/breadcrumbs ya resueltos — no hay necesidad de crear un segundo layout de panel.
- **Patrón de formulario:** `card` + `form-floating` + `form-control-feedback-start` + `jquery.validate` (sección 5.3) es el estándar de facto, ya usado en 5 módulos.
- **Patrón de listado:** Yajra DataTables server-side + `$dataTable->table()`/`scripts()` es el estándar en 4 de 5 módulos; `mis-ninios` es la excepción a corregir si se toca ese módulo.
- **Confirmación de borrado:** función global `eliminar()` (definida una sola vez en `layouts/app.blade.php`) + atributo `data-msg` — no crear una nueva cada vez.
- **Iconos:** Phosphor (`ph-*`) es el set dominante (179 usos) — Font Awesome está cargado pero prácticamente sin uso (11, casi todos el spinner). Cualquier ícono nuevo debería ser Phosphor por consistencia, no reintroducir Font Awesome.
- **Tema claro/oscuro:** ya existe un selector funcional (`sections/config.blade.php`) — cualquier CSS custom nuevo (como el de `cartas/index.blade.php`, sección 5.1) debería probarse en ambos modos o usar las variables CSS del tema en vez de colores hardcodeados, para no romper el modo oscuro silenciosamente.

---

## 8. Qué NO se hizo en esta auditoría

- No se leyó cada línea de las 5 vistas de tipo de carta de `responder-cartas/` (`contestacion`, `iniciada`, `presentacion-mayores`, `presentacion-menores`) — se confirmó el patrón general sobre `agradecimineto.blade.php` y se asume (no verificado línea por línea) que las otras 4 comparten el mismo nivel de pulido, al ser del mismo commit/autor y mismo layout. Si se decide tocar ese módulo, conviene releerlas todas antes de tocar cualquiera.
- No se probó nada en navegador todavía — este documento es solo de lectura de código, según lo pedido explícitamente antes de proponer ningún cambio.
- No se tocó ningún archivo de `public/assets/` (el tema Limitless comprado) para entender su estructura interna completa (SCSS fuente, build propio del tema) — se confirmó que es un asset ya compilado/estático servido tal cual, sin necesidad de profundizar más para esta fase.
- No se investigó `resources/lang/es` en relación a UI (traducciones) — no apareció evidencia de que se use para las vistas del panel (los textos están hardcodeados en español directamente en los `.blade.php`).

---

## 9. Siguiente paso

Con esta auditoría cerrada, corresponde presentar un plan acotado (no la Fase 3 completa) para el primer cambio concreto a implementar, con autorización explícita antes de tocar código — ver mensaje de seguimiento.
