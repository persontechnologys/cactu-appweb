# Manejo de errores en CactuApp

> Ninguna pantalla inspecciona `res.status`/`response.status` de forma explícita. Todo el manejo de errores depende de la **forma del JSON** devuelto, con `try/catch` alrededor de cada `fetch` que captura errores de red (no de HTTP).

## Patrón general observado

```js
try {
  const res = await fetch(...);
  const data = await res.json();
  if (data.errors) { /* objeto de validación 422 → toast por cada valor */ }
  if (data.error)  { /* string, p.ej. 403 "No autorizado" → toast */ }
  if (data.info)   { /* p.ej. "Carta ya fue respondida" → toast */ }
  if (data.success) { /* toast + navegación */ }
} catch (error) {
  toast(error.toString()); // solo errores de red/parseo, no HTTP
} finally {
  setcargando(false);
}
```

**No las 6 pantallas que usan este patrón implementan las 4 ramas.** Verificado línea por línea contra el código real (Fase 5, ARQ-006 Paso B):

| Pantalla | `errors` | `error` | `info` | `success` — efecto |
|---|---|---|---|---|
| `Contestacion.js`, `Agradecimiento.js`, `Iniciada.js` | ✓ | ✓ | ✓ (solo toast) | navega a `Inicio` + limpia caché de borrador |
| `PresentacionMayores.js`, `PresentacionMenores.js` | ✓ | **✗ (no revisada)** | ✓ (toast **+ `navigation.goBack()`**, distinto a las 3 de arriba) | navega a `Inicio` + limpia caché de borrador |
| `DescargarEliminar.js` (`eliminar-carta`) | ✓ | **✗ (no revisada)** | n/a (el backend no devuelve `info` para este endpoint) | navega a `Inicio` |

**Ninguna pantalla compara el *valor* (texto) de `error`/`info`/`success`** — siempre se muestra tal cual en un toast (`result.error`, `.toString()`, etc.). Cambiar el texto de un mensaje (p. ej. corregir el typo "resgitrado") es seguro del lado móvil. **Cambiar o quitar una *clave*** (`error`/`info`/`success`/`errors`) sí rompería la pantalla que la revisa — confirmado, no supuesto.

## Qué pasa realmente en cada código HTTP

| Código | Comportamiento real |
|---|---|
| `200`/`201` | Se interpreta vía `data.success`/`data.message === 'ok'` según la pantalla. |
| `401` (Sanctum, token inválido/ausente) | Respuesta estándar de Laravel `{"message":"Unauthenticated."}` — **no coincide con ninguna de las claves que el cliente revisa** (`errors`/`error`/`info`/`success`). El `try` no lanza excepción (la petición HTTP en sí no falla), así que el usuario **no ve ningún mensaje ni es deslogueado automáticamente** — la pantalla simplemente no hace nada visible. |
| `403` (autorización, p.ej. SEG-005) | El backend responde `{"error": "No autorizado"}`. **Verificado pantalla por pantalla (Fase 5, ARQ-006 Paso B, 2026-08-08) — no es uniforme como decía esta fila antes:** `Contestacion.js`, `Agradecimiento.js`, `Iniciada.js` **sí** revisan `if (result.error)` y muestran un toast (caso manejado). `PresentacionMayores.js`, `PresentacionMenores.js` y `DescargarEliminar.js` (`eliminar-carta`) **no revisan `result.error` en absoluto** — un 403 en esas 3 pantallas es indistinguible de una respuesta vacía: el spinner se detiene y no pasa nada visible, igual que un 401. Ver `cambios-requeridos.md` #18. |
| `404` (p.ej. carta inexistente tras `findOrFail`) | Laravel devuelve HTML/JSON de error estándar de framework, no la forma `{errors/error/info/success}` esperada — comportamiento no verificado en UI, probablemente silencioso igual que el 401. |
| `422` (validación) | `{"errors": {...}}` — manejado explícitamente, se muestra cada mensaje. |
| `500` | Si el backend responde JSON con `error` (como en `guardarCarta`, que captura excepciones y responde `{'error': '...'}` con status 200 implícito de `response()->json()`), se muestra igual que un 403. Si el 500 no trae esa forma exacta, silencioso. |

## Consecuencia práctica más importante

**No hay logout automático en 401.** Si el token se invalida manualmente en el backend (o en el futuro se agrega expiración), el niño queda con una sesión "zombie": la app sigue mostrando `TabHome` (porque `userToken` sigue en `AsyncStorage`), pero ninguna pantalla mostrará datos ni errores explicables — parecerá que la app "no hace nada" al presionar los botones.

## Antes de tocar cualquier pantalla

No se debe asumir que agregar un `if (res.status === 401) signOut()` genérico es un cambio trivial y aislado — like cualquier cambio de UX de error, conviene decidirlo como parte de una mejora explícita (ver `cambios-requeridos.md`, categoría UX/API), no como un parche puntual en una sola pantalla, para mantener el comportamiento consistente entre las 8 llamadas protegidas por `auth:sanctum`.
