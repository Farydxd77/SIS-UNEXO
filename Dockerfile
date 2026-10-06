# syntax=docker/dockerfile:1

# Imagen de produccion: PHP 8.5 + Apache, lista para Render.
# La base de datos (Neon / PostgreSQL) llega por variables de entorno;
# el .env local NO entra en la imagen (ver .dockerignore).
FROM php:8.5-apache

# Extensiones: pdo_pgsql (PostgreSQL), intl, bcmath y zip (para composer).
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev unzip \
    && docker-php-ext-install pdo_pgsql intl bcmath zip \
    && rm -rf /var/lib/apt/lists/* \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && a2enmod rewrite

# Render indica el puerto en $PORT (10000 por defecto). Apache lo lee al arrancar.
ENV PORT=10000

COPY <<'EOF' /etc/apache2/ports.conf
Listen ${PORT}
EOF

COPY <<'EOF' /etc/apache2/sites-available/000-default.conf
<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /dev/stderr
    CustomLog /dev/stdout combined
</VirtualHost>
EOF

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Primero solo las dependencias: si no cambia composer.lock, Docker reusa esta capa.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Al arrancar: cachea la config (ya con las variables de Render), migra y sirve.
COPY <<'EOF' /usr/local/bin/iniciar-app
#!/bin/sh
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
    # Los roles son necesarios para que la app funcione; el seeder no duplica.
    php artisan db:seed --class=RolSeeder --force
fi

chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
EOF

RUN chmod +x /usr/local/bin/iniciar-app

EXPOSE 10000

CMD ["iniciar-app"]
