#!/bin/bash
set -e

echo "🚀 Starting Laravel container..."

# 1. Create .env from example if it doesn't exist
if [ ! -f ".env" ]; then
    echo "📄 Creating .env file..."
    cp .env.example .env
fi

# 2. Detect environment (Local vs Production)
ROLE=${APP_ENV:-local}
echo "Running in $ROLE mode..."

# ---------------------------------------------------------
# DEPENDENCIES (PHP & Node)
# ---------------------------------------------------------

if [ "$ROLE" = "production" ]; then
    echo "📦 Installing PHP dependencies (Production)..."
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --classmap-authoritative

    if [ -f "package.json" ]; then
        echo "📦 Installing Node dependencies (CI)..."
        npm ci
        echo "🏗️ Building frontend (Production)..."
        npm run build
    fi
else
    echo "📦 Installing PHP dependencies (Dev)..."
    if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi

    if [ -f "package.json" ]; then
        if [ ! -d "node_modules" ] || [ -z "$(ls -A node_modules)" ] || [ ! -f "node_modules/.bin/vite" ]; then
            echo "📦 Installing Node dependencies..."
            npm install
        else
            echo "✅ Node dependencies already installed."
        fi
        
        if [ ! -d "public/build" ]; then
            echo "🏗️ Building frontend assets..."
            npm run build
        fi
    fi
fi

# ---------------------------------------------------------
# GENERAL SETUP
# ---------------------------------------------------------

# 3. Fix permissions for storage
# Great job adding '|| true' - this prevents crashes on Windows mounts!
echo "🔒 Fixing permissions..."
chown -R www-data:www-data storage bootstrap/cache public/build 2>/dev/null || true

# 4. Create storage symlink
if [ ! -L "public/storage" ]; then
    echo "🔗 Creating storage link..."
    php artisan storage:link
fi

# 5. Generate application key
if ! grep -q "APP_KEY=" .env || [ -z "$(grep APP_KEY= .env | cut -d '=' -f2)" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --ansi
fi

# 6. Caching
if [ "$ROLE" = "production" ]; then
    echo "🔥 Caching configuration for Production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    echo "🧹 Clearing cache for Development..."
    php artisan optimize:clear
fi

# 7. CRON TOGGLE LOGIC
# Default to false if not set
ENABLE_CRON=${ENABLE_CRON:-false}

# We use 'sed -i s/.../...' instead of 'c\' to be 100% safe on Alpine Linux
if [ "$ENABLE_CRON" = "true" ]; then
    echo "⏰ Cron is ENABLED via .env"
    sed -i 's/^autostart=.* ; cron_autostart_flag/autostart=true ; cron_autostart_flag/' /etc/supervisord.conf
else
    echo "zzz Cron is DISABLED (Enable by setting ENABLE_CRON=true in .env)"
    sed -i 's/^autostart=.* ; cron_autostart_flag/autostart=false ; cron_autostart_flag/' /etc/supervisord.conf
fi

echo "✅ Laravel setup complete! Starting Supervisor..."

# Start Supervisor
exec /usr/bin/supervisord -c /etc/supervisord.conf