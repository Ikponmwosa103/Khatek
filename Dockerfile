FROM php:8.3-apache

WORKDIR /var/www/html

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Fix MPM conflict
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true && \
    a2enmod mpm_prefork rewrite

# Copy project files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Configure port - Railway sets PORT env var
ENV APACHE_LISTEN_PORT=8080

# Use Railway's PORT or default to 8080
RUN sed -i "s/Listen 80/Listen 8080/" /etc/apache2/ports.conf && \
    sed -i "s/*:80/*:8080/" /etc/apache2/sites-enabled/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]
