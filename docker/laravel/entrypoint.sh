#!/bin/bash
set -e

cd /var/www/html

if [ ! -f artisan ]; then
    echo "=============================================="
    echo "  Laravel app is not installed yet."
    echo ""
    echo "  To set up, run:"
    echo "    docker compose exec laravel bash"
    echo "    composer create-project laravel/laravel ."
    echo "=============================================="
    exec tail -f /dev/null
fi

# Start Vite dev server in background if node_modules is ready
if [ -f package.json ] && [ -d node_modules ]; then
    npm run dev -- --host 0.0.0.0 &
fi

exec php-fpm -F
