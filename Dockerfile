# Cravelo — Production Dockerfile (Render.com & Local Docker uyumlu)
# Base: Resmi PHP 8.2 + Apache (Saf PHP 8.2+ katmanlı MVC, veritabanısız JSON)
FROM php:8.2-apache

# Performans için OPcache uzantısını etkinleştir (harici bağımlılık gerekmez)
RUN docker-php-ext-install opcache

# Front Controller temiz URL yönlendirmesi için Apache mod_rewrite aç
RUN a2enmod rewrite

# Apache DocumentRoot'u /var/www/html/public olarak ayarla
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf

# Public dizininde .htaccess (AllowOverride All) ve DirectoryIndex index.php etkinleştir
RUN printf '%s\n' \
    'ServerName localhost' \
    '' \
    '<Directory /var/www/html/public>' \
    '    Options -Indexes +FollowSymLinks' \
    '    AllowOverride All' \
    '    DirectoryIndex index.php index.html' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/cravelo.conf \
    && a2enconf cravelo

# Güvenli PHP üretim yapılandırması (yığın izi gizli, İstanbul saat dilimi)
RUN printf '%s\n' \
    'display_errors = Off' \
    'log_errors = On' \
    'expose_php = Off' \
    'date.timezone = Europe/Istanbul' \
    'session.cookie_httponly = 1' \
    'session.cookie_samesite = Lax' \
    > /usr/local/etc/php/conf.d/cravelo.ini

# Çalışma dizini
WORKDIR /var/www/html

# Uygulama kaynak kodlarını kopyala (.dockerignore ile gereksiz yerel dosyalar hariç tutulur)
COPY . /var/www/html/

# Depolama dizini izinlerini hazırla (flock + atomik rename uyumlu)
RUN mkdir -p /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/public \
    && chmod -R 775 /var/www/html/storage

# Render dinamik $PORT bağlama + eksik/boş diskte otomatik bin/seed.php başlatıcı betiği (saf LF)
RUN printf '%s\n' \
    '#!/bin/sh' \
    'set -eu' \
    '' \
    'PORT="${PORT:-80}"' \
    'printf "Listen %s\n" "$PORT" > /etc/apache2/ports.conf' \
    'sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf' \
    '' \
    'mkdir -p /var/www/html/storage' \
    'if [ "${FORCE_SEED:-false}" = "true" ] || [ ! -f /var/www/html/storage/users.json ] || [ ! -f /var/www/html/storage/restaurants.json ] || [ ! -f /var/www/html/storage/menu_items.json ] || [ ! -f /var/www/html/storage/couriers.json ] || [ ! -f /var/www/html/storage/orders.json ]; then' \
    '    echo "[Cravelo] Baslangic JSON verileri olusturuluyor (bin/seed.php)..."' \
    '    php /var/www/html/bin/seed.php' \
    'fi' \
    '' \
    'chown -R www-data:www-data /var/www/html/storage' \
    'chmod -R 775 /var/www/html/storage' \
    '' \
    'exec apache2-foreground' \
    > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Varsayılan yerel HTTP portu (Render çalışma zamanında $PORT ile otomatik güncellenir)
EXPOSE 80

# Konteyner başlatıcı
CMD ["/usr/local/bin/docker-entrypoint.sh"]
