# Evaluación: Laravel API Resources para estandarizar respuestas JSON

> Fase 5 (`docs/fases/roadmap.md`) pide "evaluar Laravel API Resources para estandarizar respuestas JSON". Este documento es el diagnóstico (`CLAUDE.md` sección 9: nunca mezclar diagnóstico con implementación) previo a implementar nada.
>
> **Actualización (2026-08-08):** el Paso A recomendado más abajo **ya se implementó** — existe `app/Http/Resources/CartaListaResource.php`, usado por `listar-mis-cartas` (ver `ARQ-006` en `docs/auditoria/problemas-detectados.md`). El Paso B se evaluó y **no se recomendó** (ver la misma ficha). El conteo de "12 endpoints" de la sección 2 quedó en 14 tras agregarse `/actualizar-token-fcm` y `/eliminar-token-fcm` en Fase 7 — ninguno de los dos cambia el análisis (son endpoints de escritura con `{success}`/`{error}` fijo, mismo caso que los demás).

---

## 1. Qué son y qué NO resuelven por sí solas

Una `JsonResource` de Laravel transforma **un modelo Eloquent (o colección)** en una forma JSON consistente y reutilizable. Son la herramienta correcta para respuestas que representan datos de un modelo — como `listar-mis-cartas`, que hoy arma un array a mano por cada `Carta`.

**No son la herramienta para todo lo que está inconsistente en esta API.** Los 8 endpoints de escritura (`responder-carta-*`, `eliminar-carta`, `enviar-mensaje`) no devuelven datos de un modelo — devuelven mensajes de estado fijos (`{"success": "..."}`, `{"error": "..."}`, `{"info": "..."}`). Envolver un string fijo en una Resource no aporta nada; lo que falta ahí es una **convención de envelope de respuesta** (p. ej. un trait/helper `RespuestaApi::exito()`/`RespuestaApi::error()`), que es un patrón distinto y más simple que Resources.

Separar esto en dos piezas evita el error de "meter Resources en todos lados porque el roadmap las menciona" cuando en la mitad de los casos no es la herramienta adecuada (`CLAUDE.md` sección 56, no sobrearquitecturar).

---

## 2. Estado real confirmado

- **Cero uso de `JsonResource`/`ResourceCollection`** en el proyecto (`find app -iname "*Resource*"` vacío).
- **12 endpoints, 12 formas de respuesta distintas**, documentadas endpoint por endpoint en `docs/api/`. No hay un envelope común (`{success, message, data}` o similar).
- **Inconsistencia real confirmada** (no hipotética): la misma familia de resultado usa claves distintas según el endpoint —
  - éxito de escritura: `success`
  - error de autorización: `error`
  - "ya procesado": `info`
  - error de validación: `errors` (estándar Laravel, no elegido a propósito, es el default del framework)
- **3 casos de código HTTP incorrecto para un error**, confirmados leyendo el código, no solo el roadmap:
  - `POST /login`, excepción no prevista → `200` con un string plano (`response()->json($th->getMessage())`) — además filtra el mensaje interno de la excepción, ver `SEG-013` (hallazgo nuevo, registrado en `problemas-detectados.md`).
  - `Api\ResponderCarta::procesarRespuesta`, excepción no prevista → `200` con `{"error": "..."}` en vez de `4xx/5xx`.
  - `WelcomeController::pdfninio`, `{id}` inválido → `200` con un mensaje de error que además es literalmente el texto de éxito de otro endpoint (bug de copy-paste, ver `docs/api/documentos.md`).
- **Un solo endpoint (`listar-mis-cartas`) tiene forma "de modelo"** (lista de objetos con los mismos campos por cada `Carta`) y es candidato natural a una Resource clásica.

---

## 3. Riesgo de tocar esto: no es solo código, es contrato con `CactuApp`

`docs/movil/api.md` ya documenta, con evidencia contra el código real de `CactuApp`, que **no se sabe con certeza si el cliente compara literalmente alguna de estas claves/strings** (p. ej. el typo `"Carta resgitrado exitosamente"` — nota explícita de "no corregir sin confirmar que el cliente no lo compara literal"). Cambiar la forma de cualquiera de estas respuestas sin verificar contra el código fuente de `CactuApp` es exactamente el escenario que `CLAUDE.md` sección 22 prohíbe.

Esto no se puede verificar solo leyendo `docs/movil/`: hay que abrir el repositorio móvil real (`/home/persontech/workspace/CactuApp`) y confirmar, pantalla por pantalla, si el `.then()`/`.catch()` de cada llamada compara claves o strings exactos, o si solo revisa "hubo error sí/no" de forma genérica.

---

## 4. Recomendación de alcance (para autorizar, no implementado)

**Paso A — bajo riesgo, se puede hacer ahora sin tocar el contrato:** introducir `CartaResource` (o `CartaListItemResource`) solo para `listar-mis-cartas`, preservando **exactamente** la misma forma de salida actual (mismos campos, mismo orden, mismos valores — verificado con un test que compara el JSON completo antes/después, mismo patrón usado en `VersionadoApiV1Test`). Beneficio: una sola clase con los 9 campos de la carta en un lugar, en vez de un array armado a mano dentro del Controller; útil también si en el futuro se quiere reutilizar esa misma forma en otro endpoint. Cero cambio de contrato.

**Paso B — VERIFICADO (2026-08-08) contra el código real de `CactuApp` (`/home/persontech/workspace/CactuApp`, rama `main`). Conclusión: NO recomendado como cambio de solo-backend.**

Se leyeron las 6 pantallas que consumen el patrón `errors`/`error`/`info`/`success` (`Contestacion.js`, `Agradecimiento.js`, `Iniciada.js`, `PresentacionMayores.js`, `PresentacionMenores.js`, `DescargarEliminar.js`) más `Login.js`. Detalle completo y tabla por pantalla en `docs/movil/manejo-errores.md`. Hallazgos:

1. **El *texto* del mensaje nunca se compara** — cada pantalla hace `toast.show({description: result.error})` (o `.toString()` sobre el valor), mostrando lo que sea literalmente. Corregir textos (p. ej. el typo "resgitrado") es seguro y no requiere coordinar con móvil.
2. **Las *claves* sí son estructurales, no cosméticas** — cada pantalla revisa `if (result.error)`, `if (result.info)`, `if (result.success)` como ramas independientes con **efectos distintos** (`success` navega y limpia caché local; `info` en 3 de las 6 pantallas también navega hacia atrás, en las otras 3 solo muestra un toast; `error` no se revisa en absoluto en 3 de las 6 pantallas). Colapsar estas claves en un único envelope (`{status, message}` o similar) **rompería la distinción de comportamiento que el cliente ya implementa**, no sería un cambio neutro de forma.
3. **`Login.js` depende del valor exacto `data.message === 'ok'`** para llamar `signIn(data)` — no es solo una clave, es un string literal comparado con `===`. Cualquier envelope nuevo para `/login` tendría que preservar ese valor exacto o coordinar el cambio con el móvil.
4. **Hallazgo adicional, no buscado a propósito:** `PresentacionMayores.js`, `PresentacionMenores.js` y `DescargarEliminar.js` nunca revisan `result.error` — un `403 "No autorizado"` en esos 3 endpoints falla en silencio en la app (sin toast, sin navegación). Es un bug preexistente del lado móvil, no introducido por nada de esta fase; registrado como `#18` en `docs/movil/cambios-requeridos.md`, corrección propuesta ahí (solo móvil, no requiere tocar el backend).

**Conclusión:** introducir un envelope común para los 8 endpoints de escritura **no es seguro como cambio de solo-backend bajo `/api/`** (rompería `success`/`info`/navegación diferenciada) y, aunque se hiciera bajo el alias `/api/v1/` sin tocar `/api/` legado, solo tendría valor una vez que el equipo móvil publique una versión de `CactuApp` que consuma ese envelope nuevo — es decir, es un cambio que requiere **coordinar un release doble** (backend + móvil), no una tarea de una sola sesión de backend. Se recomienda **no implementarlo ahora**; queda documentado para cuando exista ese contexto de coordinación.

**Paso C — fuera de alcance de "evaluar Resources":** `SEG-013` (fuga de mensaje de excepción en `/login`) es un hallazgo de seguridad independiente del envelope — se puede corregir solo, sin esperar a ninguna decisión sobre Resources, aplicando el mismo patrón ya usado en `SEG-008` (`Log::error()` + mensaje genérico). Es la pieza de menor riesgo y mayor urgencia de las tres.

---

## 5. Qué NO se hizo en esta evaluación

No se implementó ningún envelope común ni se tocó ninguna de las 6 pantallas de `CactuApp` (la verificación del Paso B fue de solo lectura). No se corrigió el hallazgo `#18` (silencio en 403) — es un cambio de solo móvil, fuera del alcance de este repositorio backend, pendiente de autorización propia. `SEG-013` (fuga de mensaje en `/login`) y `ARQ-006` Paso A (`CartaListaResource`) **sí se implementaron**, autorizados por el usuario por separado — ver `docs/cambios/changelog.md`.
