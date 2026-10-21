# Imagen de desarrollo local de CACTU.
# Usa la misma versión de PHP que producción (cPanel: PHP 8.4).
# Solo se usa en local; cPanel no lee este archivo.
FROM php:8.4-cli-bookworm

# Instalador de extensiones PHP (evita compilar a mano).
COPY --from=mlocati/php-extension-installer:2.7 /usr/bin/install-php-extensions /usr/local/bin/

# Extensiones que usa el proyecto: PostgreSQL, Excel/ZIP, imágenes, intl, colas.
RUN install-php-extensions pdo_pgsql pgsql intl zip gd bcmath exif pcntl opcache

# Librerías que necesita el binario wkhtmltopdf incluido en vendor/h4cc (PDF de cartas).
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libxrender1 libxext6 libfontconfig1 libfreetype6 libjpeg62-turbo libpng16-16 libssl3 \
        fontconfig fonts-dejavu-core postgresql-client git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY docker/app/php.ini /usr/local/etc/php/conf.d/99-cactu.ini

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
