FROM php:8.3-apache

WORKDIR /var/www/html

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable only the modules we need
RUN a2enmod rewrite

# Force only mpm_prefork (clean method)
RUN echo "LoadModule mpm_prefork_module /usr/lib/apache2/modules/mod_mpm_prefork.so" > /etc/apache2/mods-enabled/mpm_prefork.load \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.*

# Fix PORT configuration for Railway
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data

# Use a startup script to handle PORT dynamically
RUN echo '#!/bin/bash\n\
# Set port from Railway environment\n\
PORT=${PORT:-8080}\n\
echo "Starting Apache on port $PORT"\n\
# Update Apache configs with the correct port\n\
sed -i "s/Listen .*/Listen $PORT/" /etc/apache2/ports.conf\n\
sed -i "s/:80>/:$PORT>/" /etc/apache2/sites-enabled/000-default.conf\n\
# Start Apache\n\
apache2-foreground' > /start.sh && chmod +x /start.sh

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

# Use custom start script
CMD ["/start.sh"]
