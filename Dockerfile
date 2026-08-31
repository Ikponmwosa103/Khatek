FROM php:8.3-apache

WORKDIR /var/www/html

# Disable all Apache MPMs first, then enable only prefork
RUN a2dismod mpm_event || true \
    && a2dismod mpm_worker || true \
    && a2dismod mpm_prefork || true \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# Install PHP database extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy website files
COPY . /var/www/html/

EXPOSE 80
