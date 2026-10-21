# Docker para desarrollo local

> Creado el 2026-10-21 en la rama `feature/docker-desarrollo-local`.

## Qué es y por qué existe

La PC de desarrollo tiene PHP 8.5 y Node 24, mientras que producción (cPanel) corre **PHP 8.4** con Laravel 10. Con este entorno Docker el proyecto corre en local con la misma versión de PHP que producción, sin instalar ni cambiar nada en la PC.

**Producción no usa Docker.** cPanel sigue desplegando con *Git Version Control* tal como antes. Los archivos de Docker (`compose.yaml`, `docker/app/`, `.dockerignore`) son nuevos y cPanel los ignora.

## Archivos

| Archivo | Para qué sirve |
|---|---|
| `compose.yaml` | Define los servicios `app` (servidor web) y `scheduler` (opcional, hace lo mismo que el cron de cPanel). |
| `docker/app/Dockerfile` | Imagen `php:8.4-cli` con `pdo_pgsql`, `intl`, `zip`, `gd`, `bcmath`, `exif`, `pcntl` y las librerías de sistema que usa wkhtmltopdf para los PDF. |
| `docker/app/php.ini` | Límites de memoria y de subida, y zona horaria (solo en local). |
| `.dockerignore` | Deja la imagen liviana: el código se monta como volumen, no se copia. |

Se eliminaron los restos de Laravel Sail que nunca se configuró: las carpetas `docker/8.0`, `docker/8.1`, `docker/8.2` y el script `sail` en la raíz. Antes de borrarlos se buscó en todo el proyecto y nada los usaba (`CLAUDE.md` sección 49). El paquete `laravel/sail` sigue en `composer.json` (require-dev): quitarlo obligaría a modificar `vendor/` y `composer.lock`, y cPanel depende de ellos.

## Cómo funciona

- **Base de datos:** se usa el PostgreSQL que ya está instalado en la PC (`127.0.0.1:5432`). Los contenedores usan `network_mode: host`, es decir, comparten la red de la PC. Por eso el `.env` local sirve tal cual (`DB_HOST=127.0.0.1`) y no hace falta abrir PostgreSQL a otras redes.
- **Código:** la carpeta del proyecto se monta en `/var/www/html`. Los cambios se ven al instante, sin reconstruir la imagen.
- **Usuario:** el contenedor corre con tu UID/GID (por defecto 1000). Así los archivos que crea en `storage/` no quedan como `root`.
- **Assets:** no hay contenedor de Node porque `@vite` está comentado en `resources/views/sections/head.blade.php` y los assets se sirven desde `public/`.

## Uso

```bash
# Construir la imagen (la primera vez, o si cambia docker/app/)
docker compose build

# Levantar la app → http://127.0.0.1:8000
docker compose up -d

# Levantar también el scheduler (procesa la cola, igual que el cron de cPanel)
docker compose --profile cola up -d

# Ejecutar comandos artisan
docker compose exec app php artisan migrate:status
docker compose exec app php artisan test
docker compose exec app php artisan tinker

# Ver logs / detener
docker compose logs -f app
docker compose down
```

Si tu UID no es 1000: `UID=$(id -u) GID=$(id -g) docker compose up -d`.

## Reglas importantes

1. **`vendor/` está versionado en Git** porque cPanel no ejecuta `composer install`. La imagen **no incluye Composer** a propósito, para no modificar `vendor/` ni `composer.lock` por accidente.
2. Si alguna vez hay que agregar o actualizar paquetes, se hace con PHP 8.4 (la versión de producción). Luego se revisa el diff de `vendor/` y `composer.lock` antes del commit.
3. El `.env` no se sube a Git. Producción tiene su propio `.env` en cPanel.

## Pruebas realizadas (2026-10-21)

- `php -v` → PHP 8.4.26; `php artisan --version` → Laravel 10.50.3.
- Conexión al PostgreSQL local OK (la base `cactu` local estaba vacía, sin migraciones).
- `GET /login` → 200.
- PDF real generado con Knp Snappy + `vendor/h4cc/wkhtmltopdf-amd64` (wkhtmltopdf 0.12.4).
- `php artisan test` → 94/94 en verde (usan SQLite en memoria, no tocan PostgreSQL).
