# Informe general de auditoría — CACTU (Fase 0)

**Fecha:** 2026-08-08
**Rama:** `main` — **Commit:** `a93f366` ("cartas v2")
**Alcance:** repositorio `persontechnology/cactu-appweb` (backend Laravel + vistas Blade). La app React Native no forma parte de este repositorio y no fue auditada directamente.

> **Nota de actualización (auditoría cruzada de documentación, 2026-08-08):** este es el informe de cierre de la Fase 0 y **no se reescribió** para no perder el registro histórico de cómo estaba el sistema antes de intervenir. Desde entonces se cerraron las Fases 1, 2, 4, 5, 6 y 7 completas (ver `docs/fases/roadmap.md` y `docs/cambios/changelog.md`) — casi todo lo descrito como "hallazgo" o "no implementado" en este informe ya fue corregido. Las secciones más afectadas por el paso del tiempo (arquitectura, conteo de rutas, Firebase) tienen su propia nota puntual más abajo. Para el estado real y actual, la fuente de verdad es `docs/auditoria/problemas-detectados.md` y `docs/cambios/changelog.md`, no este documento.

---

## 1. Resumen ejecutivo

CACTU es una plataforma Laravel 10 / PHP 8.4 que gestiona el programa de "cartas" entre niños patrocinados (ChildFund/CACTU) y sus patrocinadores, operada por gestores comunitarios y administradores. El sistema es pequeño y concentrado (17 controllers, 5 modelos, ~25 rutas web y ~10 rutas API) pero **funcional y en producción**. La arquitectura es la típica de un proyecto Laravel "MVC clásico" sin capas de Actions/Services/Jobs, con toda la lógica de negocio en los Controllers.

El hallazgo más relevante de esta auditoría es un **patrón sistemático de autorización incompleta en el backend**: varias rutas administrativas (`usuarios`, `ninios`) y un endpoint público de la API móvil confían en que la interfaz oculte opciones o en identificadores enviados por el cliente, en lugar de verificar permisos en el servidor. Esto contradice directamente la "Regla de autorización" del propio `CLAUDE.md` (sección 20) y constituye el riesgo más urgente a resolver, por encima de cualquier trabajo de arquitectura o UX.

El resto del sistema (módulo de Cartas para el flujo principal gestor→niño, `MisNiniosController`, `CartaDataTable`) sí sigue buenas prácticas de acotamiento por comunidad/usuario, lo cual demuestra que el equipo conoce el patrón correcto — el problema es de **consistencia**, no de desconocimiento.

## 2. Arquitectura actual

MVC clásico de Laravel 10: Controllers → Eloquent Models → Blade Views, con Yajra DataTables para listados server-side y Spatie Permission para roles (`ADMINISTRADOR`, `GESTOR`). ~~**No implementado actualmente:** Actions, Services, Repositories, Jobs, Events/Listeners, módulos (`app/Modulos/`).~~ **Actualizado (2026-08-08):** existen hoy 9 `Actions` (`app/Actions/`) y un `Service` (`app/Services/FirebaseService.php`). `Repositories`, `Jobs` dedicados, `Events`/`Listeners` y módulos siguen sin implementarse, sin necesidad concreta detectada todavía. Ver `deuda-tecnica.md` para el detalle actualizado.

## 3. Módulos

Usuarios, Comunidades, Niños (admin), Mis Niños (gestor), Cartas, Responder Carta (web pública + API móvil), Perfil, Home/Institucional. Detalle completo con controllers/models/views por módulo en `inventario.md` sección 4.

## 4. Web

25 rutas efectivas en `routes/web.php`. El flujo principal (gestor autenticado gestiona sus niños y les envía cartas) funciona correctamente. ~~Las rutas pensadas para ADMINISTRADOR (`usuarios`, `ninios`, `comunidad`) carecen de protección real de backend.~~ **Resuelto (`SEG-001`/`SEG-002`, Fase 2).** Detalle en `rutas-web.md`.

## 5. API

~~10 rutas efectivas en `routes/api.php`~~ — **hoy son 14** (se agregaron `POST /actualizar-token-fcm` y `POST /eliminar-token-fcm` en Fase 7, y existe un alias `/api/v1/...` para todo el grupo, `ARQ-005`), consumidas por la app móvil del niño (autenticación Sanctum sobre el modelo `Ninio`, no `User`). ~~Un endpoint de descarga de PDF es completamente público y usa IDs secuenciales sin cifrar. Un endpoint de listado de cartas confía en un `userId` enviado por el cliente en vez del usuario autenticado por token.~~ **Ambos resueltos (`SEG-003`, `SEG-004`, Fase 2).** Detalle en `rutas-api.md`.

## 6. React Native

No auditado directamente (repositorio externo). Se documentó el contrato inferido desde el backend en `integracion-movil.md`, incluyendo preguntas pendientes para el equipo móvil antes de tocar cualquier endpoint (especialmente `listar-mis-cartas`, cuyo fix de seguridad SEG-004 debe coordinarse).

## 7. Seguridad

12 hallazgos documentados en `seguridad.md`, de los cuales **4 son CRÍTICOS**:

- **SEG-001:** cualquier usuario autenticado puede auto-asignarse el rol ADMINISTRADOR (`/usuarios`) — no hay middleware de rol registrado pese a tener `spatie/laravel-permission` instalado.
- **SEG-002:** cualquier usuario autenticado puede ver/editar/eliminar niños de cualquier comunidad (`/ninios`), exponiendo PII de menores.
- **SEG-003:** `GET /api/dcpdf/{id}` es público, sin autenticación, con ID secuencial — cualquiera puede descargar cartas de niños ajenos iterando el ID.
- **SEG-004:** `listar-mis-cartas` confía en un `userId` enviado por el cliente en lugar del niño autenticado por token Sanctum.

Los 4 comparten la misma causa raíz: **el frontend/menú oculta opciones que el backend no verifica**, el error explícito que `CLAUDE.md` sección 20 advierte evitar. Se documentaron además 1 hallazgo ALTO, 3 MEDIOS, 3 BAJOS y 1 informativo. Ningún hallazgo fue explotado ni corregido durante esta auditoría, conforme a las instrucciones recibidas.

## 8. UX/UI

No se realizó un análisis exhaustivo de UX/UI en esta fase (fuera del foco de auditoría de backend). Se detectó que existen dos generaciones de plantillas PDF de cartas ("v1" y "v2") coexistiendo sin criterio documentado de cuándo usar cada una (`UX-001`). Se recomienda un análisis dedicado antes de introducir el template Limitless (Fase 3 del roadmap).

## 9. PDF

Generación vía `barryvdh/laravel-snappy` + `wkhtmltopdf`. ~~Hallazgo relevante: `config/snappy.php` apunta a un binario de **Windows**...~~ **Resuelto (`PDF-001`, Fase 6).** Se confirmó contra producción real (cPanel) y se corrigió el repo para que coincida con lo ya funcional ahí.

## 10. Email

Vía `Notification::toMail` + SMTP, sin cola (envío síncrono dentro del request HTTP). Sigue así — no se tocó en fases posteriores.

## 11. Firebase

~~Vía `kutia-software-company/larafirebase`, usado únicamente por `EnviarNotificacionCartaNueva::toFirebase()`. Un solo token FCM por niño (columna `text`, no tabla de dispositivos) — **implementación parcial** respecto al modelo objetivo de `CLAUDE.md` (Usuario → múltiples Dispositivos → Token).~~ **Completamente reescrito (Fase 7, 2026-08-08).** El paquete original usaba la API legacy de FCM, decomisionada por Google desde 2024 — las notificaciones muy probablemente llevaban ~2 años sin llegar a nadie (`FIREBASE-001`). Se migró a `kreait/laravel-firebase` (FCM HTTP v1), verificado end-to-end con un envío real recibido en un dispositivo. Además, ahora soporta múltiples dispositivos por niño vía la tabla `dispositivos` (`FIREBASE-002`), cerrando también la brecha con el modelo objetivo de `CLAUDE.md` sección 25. Ver `docs/auditoria/problemas-detectados.md`.

## 12. Queue

~~`QUEUE_CONNECTION=sync`. Las notificaciones importan `ShouldQueue` pero no lo implementan...~~ **Resuelto (`PERF-001`, Fase 6).** `QUEUE_CONNECTION=database`, notificaciones implementan `ShouldQueue` de verdad, `queue:work --stop-when-empty` vía Scheduler.

## 13. Cron

`app/Console/Kernel.php::schedule()` está vacío. No hay evidencia en el repositorio de una entrada de Cron en cPanel para `schedule:run` — debe verificarse directamente en el panel (no auditable desde el código). Ver `infraestructura-actual.md`.

## 14. Performance

Generación de PDF siempre síncrona (mitigada parcialmente con `set_time_limit(120)` en `CartaController::store`). Re-evaluado en Fase 6: no es deuda accionable, los 3 usos de `PDF::loadView` son endpoints GET donde el PDF es la respuesta HTTP misma, no hay nada job-ificable ahí. ~~Carpeta temporal de imágenes base64 (`storage/app/temp`) sin limpieza automática...~~ **Resuelto (`INFRA-001`, Fase 6).**

## 15. Testing

~~Cobertura real del dominio: **0%**. Solo existen los archivos de ejemplo por defecto de Laravel.~~ **Mejorado a 75 tests reales** (autorización, validación, Firebase, tokens FCM, PDF, etc.), pero `TEST-001` (suite sistemática diseñada para el dominio completo, no reactiva por hallazgo) sigue abierto — pendiente de Fase 8.

## 16. Deuda técnica

Detalle completo y actualizado en `deuda-tecnica.md` — la mayoría de lo listado originalmente aquí ya se resolvió; quedan abiertos: `TEST-001` (suite sistemática), N+1 no auditados en detalle, y decisiones de negocio pendientes (`SEG-010`, `SEG-012`).

## 17. Código duplicado

El caso más significativo: la construcción del texto `detalle` de cada tipo de carta (Contestación/Agradecimiento/Iniciada/Presentación mayor/menor) está **duplicada casi íntegramente** entre `ResponderCartaController` (web) y `Api\ResponderCarta` (móvil) — 5 métodos duplicados en cada lado, ~250 líneas replicadas. Esta duplicación ya causó divergencia real: la versión web valida autorización (débilmente) y la versión API no valida nada (`ARQ-001`, relacionado con SEG-005/SEG-006).

## 18. Riesgos

| Riesgo | Probabilidad | Impacto |
|---|---|---|
| Explotación de SEG-001/SEG-002 por un gestor malintencionado o cuenta comprometida | Media | Muy alto (control total del sistema / PII de menores) |
| Explotación de SEG-003 por terceros externos (scraping de PDFs) | Alta (no requiere cuenta) | Alto (exposición de datos de menores) |
| Fallo de generación de PDF en producción si `config/snappy.php` no está sobreescrito en el servidor | Baja (el sistema funciona hoy) pero **debe verificarse antes de cualquier despliegue relacionado** | Alto (rompe la función núcleo del sistema) |
| Regresiones futuras por falta de pruebas al corregir los hallazgos de seguridad | Media | Medio |

## 19. Recomendaciones

1. Priorizar la Fase 2 (Seguridad) del roadmap antes que cualquier trabajo de UX/UI o nuevas funcionalidades.
2. Antes de tocar `config/snappy.php`, confirmar la configuración real de producción en cPanel — no asumir que está roto solo porque el repositorio lo sugiere.
3. Coordinar con el equipo/repositorio de React Native antes de modificar `listar-mis-cartas` (SEG-004) y `dcpdf` (SEG-003), ya que son parte del contrato API activo.
4. Introducir pruebas de autorización (Feature tests) como parte de la propia corrección de cada hallazgo de seguridad, no como tarea separada posterior.
5. No iniciar la extracción masiva de Actions/Services (Fase 1/4) hasta cerrar los hallazgos críticos de seguridad.

## 20. Roadmap

Ver `docs/fases/roadmap.md` para el detalle completo de las 8 fases propuestas y su priorización.

---

## Archivos de documentación creados en esta auditoría

```text
docs/
├── auditoria/
│   ├── inventario.md
│   ├── rutas-web.md
│   ├── rutas-api.md
│   ├── seguridad.md
│   ├── problemas-detectados.md
│   ├── deuda-tecnica.md
│   ├── integracion-movil.md
│   ├── infraestructura-actual.md
│   └── informe-general.md   (este archivo)
└── fases/
    └── roadmap.md
```

## Cambios de código realizados

**Ninguno.** Conforme a las instrucciones de `CLAUDE.md` (Fase 0) y de la tarea recibida, esta auditoría es exclusivamente de análisis y documentación. No se modificó, refactorizó ni corrigió ningún archivo de `app/`, `routes/`, `resources/`, `config/` ni `database/`.

## Pendientes

- Completar `docs/arquitectura/`, `docs/api/`, `docs/movil/`, `docs/ux-ui/`, `docs/seguridad/` (estructura completa de `CLAUDE.md` sección 33) en fases posteriores, conforme se tomen decisiones concretas.
- Confirmar con el responsable de infraestructura el estado real de cPanel (Cron, versión de PHP CLI, `.env` de producción, `config/snappy.php`).
- Confirmar con el equipo móvil los puntos abiertos listados en `integracion-movil.md` sección 7.

## Próximo paso recomendado

~~Revisar este informe y `seguridad.md` con el responsable del proyecto, y autorizar explícitamente el inicio de la **Fase 2 — Seguridad**...~~ **Desactualizado — Fase 2 ya se ejecutó y cerró (2026-08-08).** De hecho, todas las fases del roadmap excepto la 3 (UX/UI) y la 8 (Performance + Calidad) están cerradas. Ver `docs/fases/roadmap.md` para el estado y priorización vigentes.
