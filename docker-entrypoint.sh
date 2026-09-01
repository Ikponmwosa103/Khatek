#!/bin/bash
set -e

# Railway (and similar PaaS providers) inject $PORT at runtime.
PORT_TO_USE="${PORT:-8080}"

echo ">>> docker-entrypoint: configuring Apache to listen on port ${PORT_TO_USE}"

# Rewrite the Listen directive in ports.conf without changing unrelated values.
if grep -qE "^Listen " /etc/apache2/ports.conf; then
    sed -ri "s/^Listen .*/Listen ${PORT_TO_USE}/" /etc/apache2/ports.conf
else
    echo "Listen ${PORT_TO_USE}" >> /etc/apache2/ports.conf
fi

# Keep the default virtual host on the same port as the listener.
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT_TO_USE}>/" \
    /etc/apache2/sites-enabled/000-default.conf 2>/dev/null || true
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT_TO_USE}>/" \
    /etc/apache2/sites-available/000-default.conf 2>/dev/null || true

export APACHE_LISTEN_PORT="${PORT_TO_USE}"

# Keep runtime configuration safe even if the image cache contains an older
# Apache module state. The base image must have exactly one MPM enabled.
a2dismod mpm_event mpm_worker mpm_prefork >/dev/null 2>&1 || true
rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf
a2enmod mpm_prefork >/dev/null

# Fail clearly instead of entering a restart loop if configuration is invalid.
apache2ctl -t

exec apache2-foreground