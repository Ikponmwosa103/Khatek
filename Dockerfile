FROM php:8.3-apache

WORKDIR /var/www/html

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Fix MPM conflict - disable all except prefork
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true && \
    a2enmod mpm_prefork rewrite

# Copy project files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Allow .htaccess overrides (optional but common)
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/sites-available/000-default.conf

# Railway automatically sets PORT, Apache will use it
EXPOSE 80

CMD ["apache2-foreground"]
