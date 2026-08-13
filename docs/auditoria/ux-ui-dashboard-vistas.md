# Auditoría UX/UI — Parte 0: carta individual, landing, dashboard y filtros (Fase 3, continuación)

**Fecha:** 2026-08-08
**Rama:** `security/fase-2-hallazgos-criticos`
**Alcance:** `resources/views/cartas/show.blade.php`, `resources/views/welcome.blade.php`, `resources/views/home.blade.php` + `HomeController`, y comparación de filtros entre los 4 listados server-side (`cartas`, `ninios`, `usuarios`, `comunidad`). No repite lo ya auditado y cerrado en `docs/auditoria/ux-ui-actual.md` (UX-001 a UX-005) ni en `SEG-015`.
**Método:** lectura directa del código (Blade, controllers, rutas, modelos, DataTables). Sin asumir nada no confirmado contra el repositorio real (`CLAUDE.md` sección 3).

---

## 1. Resumen ejecutivo

Los 4 puntos auditados tienen un patrón común: **son vistas que quedaron "mínimas" o genéricas y nunca se les agregó contenido de negocio real**, a diferencia de los listados (`cartas/index`, etc.) que sí evolucionaron. Ninguno es un bug ni un problema de seguridad — son oportunidades de UX concretas:

1. **`cartas/show.blade.php`** quedó con un bloque grande de HTML comentado (el diseño viejo de comparación v1/v2 que UX-001 ya volvió obsoleto) y muestra solo el PDF embebido, sin ningún dato adicional de la carta (estado, fechas, gestor, historial).
2. **`welcome.blade.php` es literalmente `@include('home')`** — la landing pública (para visitantes sin sesión) y el dashboard autenticado son el mismo archivo. Hoy ambos muestran las mismas tarjetas estáticas de marketing ("Gestión de Comunidades", "Correspondencia"), sin datos reales para el usuario logueado.
3. **`HomeController::index()` no pasa ningún dato a la vista** (`return view('home')`, sin argumentos). El dashboard no tiene KPIs, contadores ni nada dinámico, pese a que el resto del sistema sí tiene lógica de alcance por rol (`ADMINISTRADOR` ve todo, `GESTOR` ve solo lo suyo) implementada y probada en `CartaDataTable`, `NinioDataTable`, `CartaController`, las Policies, etc.
4. **Los filtros de listados son inconsistentes entre módulos**: `cartas/index.blade.php` tiene un formulario de filtros completo (tipo, estado, niño, comunidad, rango de fechas, gestor) con auto-aplicación AJAX — es el patrón más maduro del sistema. `ninios/index.blade.php`, `usuarios/index.blade.php` y `comunidad/index.blade.php` no tienen ningún filtro: solo `{{ $dataTable->table() }}` y el buscador genérico que trae DataTables por defecto.

Antes de proponer un diseño para el dashboard con KPIs, **hace falta que el usuario defina qué métricas de negocio le interesan** — no se debe asumir (sección 3 y 5 de `CLAUDE.md`). Se listan preguntas concretas en la sección 5.

---

## 2. `cartas/show.blade.php` — estado actual

Vista de una sola carta (`GET /cartas/{carta}`). Contenido real: una tarjeta con el PDF embebido en un iframe (`ratio-16x9`) y el enlace "Editar" en el breadcrumb. Nada más.

**Lo que quedó tras UX-001:** 20 líneas comentadas (`{{-- ... --}}`, líneas 35–54) con el diseño anterior que comparaba v1/v2 con un `@can('mostrarEnlaceVer', $carta)`. Es código muerto — UX-001 ya retiró la plantilla v2 completa, y esta comparación ya no tiene sentido ni con qué comparar.

**Lo que no muestra hoy, y que sí está disponible en el modelo `Carta`** (confirmado por los filtros de `cartas/index`): tipo, estado, fecha de creación, fecha de respuesta, comunidad, gestor asignado. La vista de detalle es más pobre en información que la fila de la tabla de la que viene.

---

## 3. `welcome.blade.php` — landing pública

Ruta `GET /` (sin autenticación, nombre `welcome`). El archivo completo es:

```blade
@include('home')
```

`home.blade.php` extiende `layouts.app` (el layout del panel, con sidebar/navbar) y usa `@auth`/`@else` para mostrar un bloque de "Manual de usuario + Acceso rápido" si hay sesión, o un botón de "Iniciar sesión" si no la hay. El resto (logo, descripción de CACTU, tarjetas de "Gestión de Comunidades"/"Correspondencia", banner de "Actualización Importante") es idéntico para ambos casos.

**Consecuencia concreta:** un visitante sin cuenta que entra a `cactu.ec/` ve el layout completo del panel administrativo (sidebar, navbar con botón de login) en vez de una landing pública propia. Y el banner de "Actualización Importante" (aviso interno sobre migración de formatos de carta, con botón "Contactar Person Technology") se le muestra también a visitantes anónimos, para quienes no tiene ningún sentido.

No es necesariamente un error — puede ser una decisión deliberada de no invertir en una landing de marketing separada — pero es una duplicación de propósito (página pública vs. dashboard privado) resuelta con un solo archivo, que vale la pena decidir explícitamente si se mantiene o se separa.

---

## 4. `home.blade.php` + `HomeController` — dashboard post-login

`HomeController::index()`:

```php
public function index()
{
    return view('home');
}
```

Sin query, sin datos, sin diferenciación por rol. La vista es 100% estática (salvo el `@auth`/`@else` ya descrito). Roles confirmados en el sistema (`Spatie\Permission`, usados activamente en `CartaController`, `CartaDataTable`, `NinioDataTable`, las Policies): `ADMINISTRADOR` y `GESTOR`. Hoy el dashboard no distingue entre ellos — ambos ven exactamente las mismas dos tarjetas ("Cartas: Gestionar", "Niños: Administrar") sin ningún número real detrás.

---

## 5. KPIs / widgets — preguntas pendientes antes de diseñar

No se propone un diseño todavía porque depende de negocio, no de código. Antes de la Parte "dashboard + KPIs" del plan, se necesita que el usuario indique:

- ¿Qué métricas le importan de verdad? (ejemplos posibles con los datos ya disponibles en `Carta`/`Ninio`/`Comunidad`: cartas pendientes de responder, cartas respondidas este mes, tiempo promedio de respuesta, niños por comunidad, niños sin carta reciente.)
- ¿Los KPIs deben ser distintos para `ADMINISTRADOR` (vista global) vs. `GESTOR` (vista de su propio alcance, como ya hacen los listados)?
- ¿Alguna métrica requiere un cálculo pesado (ej. tiempo promedio de respuesta agregado sobre miles de cartas) que convenga cachear en vez de calcular en cada carga del dashboard?

---

## 6. Filtros en listados — comparación

| Listado | Filtros propios | Patrón |
|---|---|---|
| `cartas/index.blade.php` | Tipo, Estado, Niño (texto libre), Comunidad, rango de fecha de creación, rango de fecha de respuesta, Gestor (si hay más de uno) | Card colapsable + formulario GET + AJAX contra el DataTable, auto-aplica al cambiar cualquier campo (debounce 500ms), URL se actualiza con `history.pushState`, badge de "N de M registros" cuando hay filtro activo |
| `ninios/index.blade.php` | Ninguno | Solo `{{ $dataTable->table() }}` — el buscador genérico de DataTables |
| `usuarios/index.blade.php` | Ninguno | Igual que `ninios` |
| `comunidad/index.blade.php` | Ninguno | Igual que `ninios` |

El patrón de `cartas/index` está maduro y probado (auto-aplicación, indicador de filtros activos, persistencia en URL) — es el candidato natural a replicar, no a reinventar, si se decide agregar filtros a los otros 3 listados. Queda pendiente confirmar con el usuario qué campos tendría sentido filtrar en cada uno (ej. `ninios` por comunidad/estado; `usuarios` por rol; `comunidad` por gestor asignado) antes de implementar.

---

## 7. Hallazgos registrados

Se registraron en `docs/auditoria/problemas-detectados.md` como pendientes (sin tocar código todavía):

- **UX-006** — código muerto comentado en `cartas/show.blade.php`.
- **UX-007** — `welcome.blade.php` y `home.blade.php` comparten el mismo contenido sin diferenciación de propósito.
- **UX-008** — dashboard sin datos ni diferenciación por rol.
- **UX-009** — filtros inconsistentes entre `cartas/index` y los otros 3 listados.

---

## 8. Plan propuesto (un paso a la vez, con autorización explícita antes de cada uno)

1. **`cartas.show`** — limpiar el código comentado muerto (UX-006) y evaluar agregar una tarjeta de metadatos de la carta (estado, fechas, gestor) junto al PDF. Cambio chico, bajo riesgo, no depende de decisiones de negocio pendientes.
2. **`welcome`** — decidir con el usuario si se mantiene como alias de `home` o se separa en una landing pública propia (sin sidebar de panel, sin el banner interno). Depende de una decisión de producto, no solo de código.
3. **Dashboard + KPIs** — depende de las respuestas de la sección 5. Es la parte de mayor alcance del plan.
4. **Filtros en `ninios`/`usuarios`/`comunidad`** — replicar el patrón de `cartas/index`, confirmando primero qué campos filtrar en cada listado.

Se recomienda ese orden: del cambio más chico y menos dependiente de decisiones externas, al más grande.
