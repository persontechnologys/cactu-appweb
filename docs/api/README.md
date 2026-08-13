# API de CACTU

> Referencia formal del contrato API, escrita desde el lado backend (`routes/api.php` + Controllers reales), en el formato que pide `CLAUDE.md` sección 23. Es el complemento de `docs/movil/api.md`, que documenta el mismo contrato desde el lado del consumidor (`CactuApp`) con las particularidades y "gotchas" reales de cada pantalla. Ante cualquier discrepancia entre ambos documentos, `docs/movil/api.md` es la fuente más reciente sobre lo que el móvil realmente envía/consume — repórtala para corregir este documento.

Único consumidor real de esta API hoy: la app móvil `CactuApp` (React Native). No hay más clientes.

---

## Base URL y versionado

| Entorno | Base URL |
|---|---|
| Producción | `https://cactu.ec/api/` |
| Local (LAN, para probar desde dispositivo/emulador) | `http://<IP-LAN-del-host>:8000/api/` — ver `docs/movil/README.md` |

**Versionado (ARQ-005, Fase 5, 2026-08-08):** cada ruta documentada abajo existe bajo dos prefijos equivalentes:

```text
/api/...      ← legado, el único que consume CactuApp hoy
/api/v1/...   ← alias idéntico, disponible para cuando el móvil migre o para futuros endpoints versionados
```

Es un alias aditivo (mismo `routes/api.php`, montado dos veces en `RouteServiceProvider::boot()`), no una copia — no pueden divergir en comportamiento por descuido. Detalle de la decisión y de un riesgo de colisión de nombres de ruta detectado y mitigado: `docs/cambios/decisiones-arquitectura.md` (entrada "ARQ-005").

**Excepción a conocer:** las URLs de descarga de PDF que llegan en la respuesta de `listar-mis-cartas` (campos `archivo`/`carta`) siempre se generan como `/api/...` (sin `v1`), sin importar qué prefijo usó el cliente para pedir la lista. Es un efecto colateral aceptado, no un bug — ver `documentos.md`.

---

## Autenticación

Ver `autenticacion.md` para el detalle completo. Resumen:

- El "usuario" autenticado por esta API es el **niño patrocinado** (`App\Models\Ninio`), no un `User` administrativo del panel web. No existe superposición entre ambos sistemas de autenticación.
- Mecanismo: Laravel Sanctum, token tipo *personal access token* (no cookies/SPA). Se envía como `Authorization: Bearer <token>` en cada petición protegida.
- El login (`POST /login`) no exige contraseña ni PIN, solo `numero_child` — riesgo informativo documentado como `SEG-012` (`docs/auditoria/seguridad.md`), decisión de producto pendiente de revisión con negocio, no un descuido de implementación.
- El token no expira del lado backend (`config/sanctum.php: 'expiration' => null`). No existe endpoint de logout — la app solo borra el token localmente.

---

## Middleware global

| Middleware | Alcance | Efecto |
|---|---|---|
| `throttle:api` | Todas las rutas de `routes/api.php` (grupo `api` en `Kernel.php`) | 60 peticiones/minuto, por usuario autenticado o por IP si no hay usuario (`RouteServiceProvider::boot()`, `RateLimiter::for('api', ...)`) |
| `auth:sanctum` | Solo las rutas dentro del grupo `Route::middleware(['auth:sanctum'])` de `routes/api.php` | Exige `Authorization: Bearer <token>` válido; responde `401` si falta o es inválido |

---

## Convención de respuestas (estado real, no aspiracional)

**No hay un envelope JSON consistente.** Cada endpoint devuelve una forma distinta, confirmada endpoint por endpoint en `cartas.md`/`autenticacion.md`:

- Éxito de login/listado: `{message, id, ...}` o `{data: [...]}`.
- Éxito de escritura (responder/eliminar carta): `{success: "texto en español, con al menos un typo real"}`.
- Error de autorización: `{error: "No autorizado"}` (403).
- Error de validación: `{errors: {campo: [...]}}` (422, formato estándar de Laravel).
- Caso "ya procesado": `{info: "..."}` (200, no es un error).

Esta inconsistencia (`errors`/`error`/`info`/`success` sin un envelope común) es exactamente lo que el roadmap de Fase 5 pide evaluar estandarizar con Laravel API Resources — ver `docs/auditoria/evaluacion-api-resources.md`. **No se ha estandarizado todavía**: este documento describe el contrato tal como existe hoy, no cómo debería ser.

---

## Índice de endpoints

| Dominio | Documento |
|---|---|
| Login, sesión del niño, `/user` | `autenticacion.md` |
| Listado y respuesta de cartas | `cartas.md` |
| Descarga de PDF | `documentos.md` |

Para la comparación fila por fila contra lo que `CactuApp` realmente invoca (qué pantalla, qué campos ignora el backend, endpoints no consumidos), ver `docs/auditoria/matriz-api-movil.md`.
