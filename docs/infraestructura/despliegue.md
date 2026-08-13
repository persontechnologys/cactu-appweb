# Checklist de valores para despliegue a producción

> Generado 2026-08-12, rama `security/fase-2-hallazgos-criticos`, a partir de comparar `.env` local vs `.env.example`, `config/firebase.php`, `config/snappy.php`, `app/Console/Kernel.php` y `docs/auditoria/infraestructura-actual.md`. Ninguno de estos valores vive en este repositorio (son `.env`/panel de producción) — este documento es la guía para setearlos antes del despliegue, conforme a `CLAUDE.md` sección 17 y 51.

## Cómo usar este documento

Cada ítem indica: qué variable/config revisar, el valor correcto esperado en producción, y por qué importa. Marcar cada uno al confirmarlo en el servidor real. No tachar un ítem solo porque "debería estar bien" — confirmar directamente en cPanel/`.env` de producción.

## 🔴 Crítico

- [ ] **`PASSWORD_ADMIN`** — en el `.env` local está seteado igual a `EMAIL_ADMIN` (mismo valor para usuario y contraseña). El seeder (`database/seeders/DatabaseSeeder.php`) lo usa tal cual para crear/actualizar la cuenta admin. **Generar una contraseña fuerte y distinta, exclusiva de producción**, antes de correr el seeder ahí.
- [ ] **`APP_DEBUG=false`** — local está en `true`. Con `true` en producción, cualquier excepción no capturada expone stack traces, rutas de servidor y variables de entorno al usuario final.
- [ ] **`APP_ENV=production`** — local: `local`.
- [ ] **`APP_URL=https://cactu.ec`** — local: `http://localhost`.

## 🟠 Alto — funcionalidad que depende de esto

- [ ] **`QUEUE_CONNECTION=database`** — local está deliberadamente en `sync` (decisión documentada, no es un olvido). En producción debe ser `database`, porque `app/Console/Kernel.php` agenda `queue:work --stop-when-empty` cada minuto; con `sync` ese comando no hace nada y las notificaciones (`EnviarCartaRespondidaGestor`, `EnviarNotificacionCartaNueva`, ahora `ShouldQueue`) correrían síncronas igual.
  - [ ] Requiere `php artisan migrate` en producción para publicar la tabla `jobs` (antes solo existía `failed_jobs`).
- [ ] **Entrada de Cron en cPanel** para el Scheduler:
  ```
  * * * * * php /home/usuario/cactu-appweb/artisan schedule:run >> /dev/null 2>&1
  ```
  Sin esto, los jobs de la tabla `jobs` quedan encolados para siempre y `temp:limpiar` nunca corre. Verificar/crear en cPanel > Cron Jobs (no auditable desde este repo).
- [ ] **`FIREBASE_CREDENTIALS`** — apunta a `storage/app/firebase/service-account.json` (ruta relativa, gitignoreada desde este cambio). Confirmar que el JSON real de la cuenta de servicio de Firebase esté copiado manualmente a esa ruta en el servidor. Sin él, cualquier notificación por el canal `firebase` (`FirebaseChannel`) falla. Ver `FIREBASE-001` en `docs/auditoria/problemas-detectados.md`.
- [ ] **`config/snappy.php`** — el binario ahora apunta a `vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64` (antes ruta Windows hardcodeada). El audit indica que ya coincide con lo activo en el servidor real (`PDF-001`), pero confirmar permisos de ejecución (`chmod +x`) tras el `composer install` en producción.
- [ ] **Versión de PHP CLI** — confirmar que el PHP que ejecuta el cron (CLI) es la misma versión que PHP-FPM/Apache. Es común que difieran en cPanel compartido, y eso rompería el Scheduler en silencio.

## 🟡 Medio

- [ ] **`MAIL_*`** — local usa `mail.cactu.ec` con `MAIL_SCHEME=null`, pero `.env.example` todavía trae `MAIL_ENCRYPTION` (clave distinta). Confirmar cuál lee realmente `config/mail.php` en esta versión de Laravel, para no dejar credenciales SMTP de producción sin cifrado por usar la variable equivocada.
- [ ] **`LOG_LEVEL`** — `.env.example` trae `debug`. En producción usar `error` o `warning`, para no llenar la cuota de disco del hosting compartido.
- [ ] **Migración `create_dispositivos_table`** — mueve datos reales: por cada `fcm_token` duplicado en `ninios` (144 grupos detectados en la auditoría), asigna el dispositivo al niño con `updated_at` más reciente. Es no destructiva (no borra `ninios.fcm_token`), pero es la única migración de esta tanda que toca datos existentes en vez de solo estructura. Correr `migrate` en un momento de bajo tráfico, con backup reciente confirmado.
- [ ] **`storage:link`** — confirmar que esté corrido en producción para los recursos que deben servirse públicamente.
- [ ] **Paquete Firebase** — `composer.json` cambió `kutia-software-company/larafirebase` → `kreait/laravel-firebase`. Verificar que `composer install` en producción no falle por versión de PHP/extensiones; el paquete viejo ya no está en `vendor/` como fallback.

## Confirmado que NO requiere cambio

`SESSION_DRIVER=file`, `CACHE_DRIVER=file`, `FILESYSTEM_DISK=local`, `DB_CONNECTION=mysql` — compatibles con cPanel compartido tal como están (ver `docs/auditoria/infraestructura-actual.md`).

## Referencias

- `docs/auditoria/infraestructura-actual.md` — estado de infraestructura detectado en el repo.
- `docs/auditoria/problemas-detectados.md` — fichas `PDF-001`, `PERF-001`, `INFRA-001`, `FIREBASE-001`, `FIREBASE-002`.
- `docs/fases/roadmap.md` — Fase 6 y 7, de donde salieron varios de estos cambios.
- `docs/cambios/changelog.md` / `docs/cambios/decisiones-arquitectura.md` — motivo y fecha de cada cambio de configuración.
