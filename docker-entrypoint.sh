#!/bin/bash
set -e

# Render (and similar PaaS providers) inject $PORT at runtime.
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

# Fail clearly instead of entering a restart loop if configuration is invalid.
apache2ctl -t

exec apache2-foreground