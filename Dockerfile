# Cravelo / Yemek Kurye Sistemi — Production Dockerfile
# Base: Official PHP 8.2 with Apache
FROM php:8.2-apache

# Install required system libraries and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install opcache \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for front controller and clean URLs
RUN a2enmod rewrite

# Configure Apache DocumentRoot to point to /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Copy application source code
COPY . /var/www/html/

# Ensure storage directory exists and configure permissions for flock & file access
RUN mkdir -p /var/www/html/storage && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/public && \
    chmod -R 775 /var/www/html/storage

# Expose standard HTTP port
EXPOSE 80

# Run Apache in foreground
CMD ["apache2-foreground"]
