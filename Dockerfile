FROM php:8.3-apache

WORKDIR /var/www/html

# Install PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        zip \
        unzip \
        libzip-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo pdo_mysql mysqli zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Make sure Apache uses ONLY prefork
RUN a2dismod mpm_event mpm_worker mpm_shared 2>/dev/null || true \
    && a2enmod mpm_prefork rewrite headers expires deflate

# Allow .htaccess
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy project
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/public/uploads

# Railway startup script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:${PORT:-8080}/ || exit 1

CMD ["docker-entrypoint.sh"]
