# Autenticación móvil

> Flujo real, verificado contra el código de `CactuApp` y `cactu-appweb`. Para el análisis de riesgo asociado, ver `docs/auditoria/seguridad.md` SEG-012 (informativo) en el repositorio backend.

## Identidad

El "usuario" autenticado por la API móvil es el **niño patrocinado** (`App\Models\Ninio`), no un `User` administrativo. Se identifica con `numero_child` — **sin contraseña ni PIN**. Es una decisión de producto (el público objetivo son niños), documentada como riesgo aceptado, no como descuido.

## Flujo

```text
1. Usuario teclea numero_child en un teclado numérico en pantalla (Login.js)
2. App lee AsyncStorage['fcmtoken'] (si ya existe uno cacheado)
3. POST /api/login { numero_child, fcm_token }
4. Backend valida que numero_child exista en Ninio; si no, 422 con {errors:{email:[...]}}
   (nota: la clave de error es "email" aunque el campo es numero_child — heredado, no lo "corrijas"
   sin confirmar antes que el cliente no depende de esa clave exacta)
5. Backend crea token Sanctum (createToken); si fcm_token viene en el body, registra/reasigna
   un dispositivo en la tabla `dispositivos` (Fase 7, FIREBASE-002 — ya no una columna simple
   en Ninio); responde:
   { message: "ok", id, numero_child, nombres, token, roles_permisos: [] }
6. App: signIn(data) → guarda token/nombres/id/numero_child/roles_permisos en AsyncStorage
   y en AuthContext vía useReducer (App.tsx)
7. Cada pantalla protegida agrega manualmente: Authorization: Bearer <token>
8. Logout (Perfil.js → signOut): borra las claves de AsyncStorage y limpia AuthContext.
   NO llama a ningún endpoint del backend — no existe /api/logout.
```

## Dónde se guarda cada dato

| Dato | Clave en `AsyncStorage` | Notas |
|---|---|---|
| Token Sanctum | `userToken` | Texto plano, sin cifrar, sin expiración del lado backend (`config/sanctum.php: 'expiration' => null`) |
| Nombre del niño | `userNombres` | — |
| Número child | `userNumeroChild` | **Bug conocido:** `App.tsx` guarda ahí el valor de `nombres`, no de `numero_child` (ver `App.tsx:137`). No se usa en ninguna pantalla actualmente, así que no ha causado incidentes visibles, pero corregirlo es trivial — ver `cambios-requeridos.md`. |
| ID del niño | `userId` | Se envía como `userId`/`ninio_id` en varios formularios; el backend ya no lo usa para autorizar nada (ver `api.md`) |
| Roles/permisos | `userRolesPermisos` | Siempre `[]` — el niño no tiene roles en este sistema. Confirmar con negocio si el campo es vestigial antes de construir cualquier UI condicionada a él. |

## Qué ocurre cuando el token expira o es inválido

- El backend nunca expira el token automáticamente (`expiration => null`), así que en la práctica esto solo ocurre si el token fue revocado manualmente en base de datos o el `numero_child` fue eliminado.
- Una respuesta `401 Unauthenticated` de Sanctum **no dispara logout automático** en la app — ninguna pantalla revisa `res.status`. Ver `manejo-errores.md`.

## Qué NO hacer sin coordinar

- No cambiar la forma del login (`numero_child`/`fcm_token` → `message`/`id`/`numero_child`/`nombres`/`token`/`roles_permisos`) sin antes confirmar que ninguna versión de producción de la app depende de un campo exacto — es un contrato activo (`CLAUDE.md` sección 22).
- No asumir que agregar contraseña/PIN (mejora sugerida en SEG-012) es solo un cambio de backend: la pantalla `Login.js` tendría que rediseñarse (hoy es un teclado numérico de un solo campo).
