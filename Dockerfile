FROM php:8.3-apache

WORKDIR /var/www/html

# Make sure only Apache's prefork MPM is enabled
RUN a2dismod mpm_event mpm_worker mpm_multi_processing 2>/dev/null || true \
    && a2enmod mpm_prefork rewrite

# Install PHP database extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy project
COPY . /var/www/html/

# Make Apache listen on Railway's port
RUN sed -i 's/Listen 80/Listen 80/' /etc/apache2/ports.conf

EXPOSE 80
