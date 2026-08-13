# Infraestructura actual y disponible en cPanel

> No se asume disponibilidad de Supervisor, Redis, Docker, acceso root ni systemd, conforme a `CLAUDE.md` sección 17. Este documento describe lo que el propio repositorio indica que está configurado, y lo que debería verificarse directamente en el panel de cPanel de producción (fuera del alcance de este repositorio).

## 1. Configuración actual (según `.env.example` y `config/`)

| Aspecto | Valor configurado | Compatible con cPanel compartido |
|---|---|---|
| Cola (`QUEUE_CONNECTION`) | `database` en `.env.example` (2026-08-08, Fase 6, ver `PERF-001`) | Sí — patrón cron `queue:work --stop-when-empty`, ver sección 3. El `.env` local de desarrollo se mantiene en `sync` deliberadamente, para no requerir un worker corriendo en paralelo durante pruebas manuales |
| Sesión (`SESSION_DRIVER`) | `file` | Sí |
| Caché (`CACHE_DRIVER`) | `file` | Sí |
| Broadcasting (`BROADCAST_DRIVER`) | `log` | No usado activamente (no hay canales/eventos definidos en `routes/channels.php` más allá del boilerplate) |
| Base de datos | MySQL | Estándar en cPanel |
| Mail | SMTP | Requiere credenciales SMTP reales en producción (en local usa `mailpit`) |
| PDF (wkhtmltopdf) | `base_path('vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64')` (corregido 2026-08-08, Fase 6, ver `PDF-001`) | Sí — verificado contra producción real (el `config/snappy.php` de `cactu.ec` ya tenía esta línea activa de forma no versionada; el repo divergía). Confirmado localmente con un PDF generado por wkhtmltopdf 0.12.4 |

## 2. Scheduler (Laravel)

`app/Console/Kernel.php::schedule()` registra, desde 2026-08-08 (Fase 6): `queue:work --stop-when-empty` cada minuto (`withoutOverlapping()`, ver sección 3) y `temp:limpiar` diario (`withoutOverlapping()`, ver sección 5, `INFRA-001`). Para que el Scheduler funcione en cPanel se requiere **una única entrada de Cron** apuntando a:

```
* * * * * php /home/usuario/cactu-appweb/artisan schedule:run >> /dev/null 2>&1
```

Esta entrada de cron **no existe documentada en el repositorio** (es configuración del panel, no del código) — debe verificarse directamente en cPanel > Cron Jobs. No se pudo confirmar su existencia desde este entorno de auditoría.

## 3. Colas en un hosting compartido sin Supervisor/Redis

Dado que cPanel compartido normalmente no permite procesos persistentes (`queue:work` en background) ni Supervisor, se implementó (2026-08-08, Fase 6, `PERF-001`):

- **`QUEUE_CONNECTION=database`** en `.env.example`: usa la tabla `jobs`, publicada con `php artisan queue:table` + `migrate` (no existía; solo estaba `failed_jobs` desde `2019_08_19_000000_create_failed_jobs_table.php`).
- **Procesamiento vía Cron + `queue:work --stop-when-empty`**: registrado en `app/Console/Kernel.php`, disparado cada minuto por el Scheduler en vez de un worker persistente, compatible con las restricciones de procesos de cPanel compartido.
- `EnviarCartaRespondidaGestor` y `EnviarNotificacionCartaNueva` ahora implementan `ShouldQueue` (antes solo la importaban sin implementarla, por lo que corrían síncronas pese al driver configurado).

Pendiente de confirmar directamente en producción: que la entrada de Cron para `schedule:run` (sección 7) esté realmente activa — sin ella, los jobs quedarían encolados en la tabla `jobs` sin procesarse nunca.

## 4. PHP CLI / Artisan

No se pudo verificar desde este entorno si el hosting de producción expone `php` por CLI con la misma versión (8.4.1 detectada en el entorno de desarrollo/auditoría) — debe confirmarse contra cPanel antes de planificar el uso de Scheduler/Cron, ya que muchos hostings compartidos usan una versión de PHP CLI distinta a la de PHP-FPM/Apache a menos que se configure explícitamente.

## 5. Almacenamiento

`FILESYSTEM_DISK=local`. Los archivos de cartas/boletas se guardan bajo `storage/app/public/cartas/...` y se sirven mediante `Storage::response()`/`Storage::get()` desde los controladores (no mediante enlace simbólico público directo en varios casos), lo cual es consistente con la necesidad de controlar acceso — aunque, como se documentó en `seguridad.md`, ese control de acceso no siempre se aplica (SEG-003, SEG-007). Debe confirmarse que `storage:link` esté ejecutado en producción para los recursos que sí deben ser públicos.

Existe una carpeta temporal (`Storage::path('temp')` = `storage/app/temp`, usada en `ResponderCartaController::crearArchivoTemporalDesdeBase64`, antes `guardarImagenBase64`) — **corrección a una afirmación previa de este documento**: sí se limpia por request (el archivo se borra tras usarse, incluso en el flujo de excepción vía `finally`, desde el refactor de Fase 4/ARQ-001). El riesgo real que quedaba no era "no se limpia", sino archivos huérfanos si el proceso PHP muere a mitad de camino (OOM, timeout, `kill -9`, cliente que corta la conexión). Resuelto en Fase 6 (`INFRA-001`, 2026-08-08): `php artisan temp:limpiar` (comando nuevo, `app/Console/Commands/LimpiarArchivosTemporalesCommand.php`) borra los archivos de esa carpeta más viejos que 24 horas por defecto, corre diario vía Scheduler.

## 6. Logs

`LOG_CHANNEL=stack`, `LOG_LEVEL=debug` en `.env.example`. Debe confirmarse que en producción el nivel sea más conservador (`error` o `warning`) para evitar archivos de log excesivamente grandes en un hosting con cuota limitada, y que `APP_DEBUG=false` en producción (no verificable desde este repositorio).

## 7. Resumen de lo que debe confirmarse directamente en cPanel (no auditable desde el código)

- Existencia y frecuencia real de la entrada de Cron para `schedule:run`.
- Versión de PHP CLI vs PHP-FPM configurada.
- Contenido real de `.env` de producción (`APP_DEBUG`, `APP_ENV`, credenciales SMTP/Firebase, y si `config/snappy.php` fue sobreescrito localmente en el servidor con una ruta de binario Linux válida).
- Cuota de disco disponible y uso actual de `storage/`.
