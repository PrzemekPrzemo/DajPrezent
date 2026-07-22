#!/bin/bash
set -e

cd /var/www/html

# Coolify mounts a persistent volume over storage/app; a fresh volume (or a
# fresh deploy) may come in root-owned, so fix permissions before anything
# tries to write to it.
mkdir -p \
    storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www:www storage bootstrap/cache

su -s /bin/sh www -c "php artisan storage:link --force" >/dev/null 2>&1 || true

# Composer ran with --no-scripts at build time (no env/DB available then),
# so run the deferred post-autoload-dump hooks now that real env vars exist.
su -s /bin/sh www -c "php artisan package:discover --ansi"
su -s /bin/sh www -c "php artisan filament:upgrade"

# Config/route/view caches embed the real env values, so they must be
# (re)built at container start, once actual Coolify env vars are present —
# baking them in at image-build time would freeze in build-time defaults.
su -s /bin/sh www -c "php artisan config:clear"
su -s /bin/sh www -c "php artisan config:cache"
su -s /bin/sh www -c "php artisan route:cache"
su -s /bin/sh www -c "php artisan view:cache"
su -s /bin/sh www -c "php artisan event:cache"

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    su -s /bin/sh www -c "php artisan migrate --force"
fi

exec "$@"
