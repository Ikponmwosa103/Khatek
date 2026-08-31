# Use official PHP with Apache base image
FROM php:8.2-apache

# Install system dependencies and required PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli

# Fix Apache MPM Conflicts (Disables event/worker, enforces prefork)
RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork

# Enable Apache mod_rewrite for custom routing / .htaccess
RUN a2enmod rewrite

# Set working directory inside container
WORKDIR /var/www/html

# Copy application files to Apache root
COPY . /var/www/html/

# Set proper permissions for web user
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose standard web port
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
