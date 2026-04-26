#!/bin/sh
set -e

echo "==> Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "==> Warming up cache..."
php bin/console cache:warmup

if [ -n "$ADMIN_EMAIL" ] && [ -n "$ADMIN_PASSWORD" ]; then
    echo "==> Creating admin user $ADMIN_EMAIL..."
    php bin/console app:create-admin "$ADMIN_EMAIL" "$ADMIN_PASSWORD" || true
fi

echo "==> Starting services..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
