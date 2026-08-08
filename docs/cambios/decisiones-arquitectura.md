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

Co-Authored-By: Claude Sonnet 5 <noreply@anthropic.com>
