#!/bin/bash
set -e

# Railway (and similar PaaS) injects $PORT at runtime.
# Fall back to 8080 locally and 80 if APACHE_LISTEN_PORT is set differently.
PORT_TO_USE="${PORT:-8080}"

echo ">>> docker-entrypoint: configuring Apache to listen on port ${PORT_TO_USE}"

# Rewrite Listen directive in ports.conf (covers both "Listen 80" and "Listen 8080")
if grep -qE "^Listen " /etc/apache2/ports.conf; then
    sed -ri "s/^Listen .*/Listen ${PORT_TO_USE}/" /etc/apache2/ports.conf
else
    echo "Listen ${PORT_TO_USE}" >> /etc/apache2/ports.conf
fi

# Update <VirtualHost *:...> in 000-default.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT_TO_USE}>/" /etc/apache2/sites-enabled/000-default.conf 2>/dev/null || true
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT_TO_USE}>/" /etc/apache2/sites-available/000-default.conf 2>/dev/null || true

# Backwards-compat: ensure APACHE_LISTEN_PORT env reflects the actual port
export APACHE_LISTEN_PORT="${PORT_TO_USE}"

echo ">>> ports.conf:"
cat /etc/apache2/ports.conf
echo ">>> 000-default.conf:"
cat /etc/apache2/sites-enabled/000-default.conf 2>/dev/null || cat /etc/apache2/sites-available/000-default.conf 2>/dev/null || true

# Exec apache in foreground
exec apache2-foreground
