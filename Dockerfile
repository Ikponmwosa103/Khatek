FROM php:8.3-apache

WORKDIR /var/www/html

# Install system dependencies + PHP extensions in a single layer (keeps image small)
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        zip \
        unzip \
        libzip-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo pdo_mysql mysqli zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Fix MPM conflict + enable required Apache modules
# php:8.3-apache uses mpm_prefork for mod_php. If mpm_event/worker is enabled it conflicts.
# Use "|| true" so build doesn't fail when module already disabled.
RUN a2dismod mpm_event 2>/dev/null || true \
    && a2dismod mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork rewrite headers expires deflate \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    # Allow .htaccess overrides (required for rewrite rules, Laravel, WordPress, etc.)
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy Apache vhost config (optional - uncomment if you have a custom vhost)
# COPY apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Copy project files
# .dockerignore ensures vendor/, .git, node_modules etc. are not copied
COPY . /var/www/html/

# Set proper permissions
# - www-data owns files so Apache can read/write
# - 755 for dirs/files, 775 for writable dirs (uploads, storage, cache)
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/public/uploads

# Configure Apache to listen on Railway's $PORT at RUNTIME, not build time
# Railway injects PORT env var (e.g. 8080). Hardcoding at build breaks if PORT changes.
# This entrypoint rewrites ports.conf + vhost on container start.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Apache default is 80; we default to 8080 for Railway. EXPOSE is documentation only.
# Real port is set by docker-entrypoint.sh via $PORT
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:${PORT:-8080}/ || exit 1

CMD ["docker-entrypoint.sh"]
