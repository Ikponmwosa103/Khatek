FROM php:8.3-apache

WORKDIR /var/www/html

# Install the PDO driver used by the Railway MySQL API.
RUN docker-php-ext-install pdo_mysql

# Configure the Railway-injected PORT at container startup.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Apache must have exactly one Multi-Processing Module enabled.
RUN a2dismod mpm_event mpm_worker mpm_prefork || true \
    && a2enmod mpm_prefork rewrite headers expires deflate \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy project files.
COPY . /var/www/html/

# Create uploads directory and set permissions.
RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/public/uploads

EXPOSE 8080
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]