FROM php:8.3-fpm-alpine

# Install system dependencies + Node.js + Cron
# Added apk cache cleaning to reduce image size
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    zip \
    libzip-dev \
    oniguruma-dev \
    bash \
    icu-dev \
    libxml2-dev \
    nodejs \
    npm \
    dcron \
    busybox-suid \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        soap \
        xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy configurations
COPY docker/laravel/nginx.conf /etc/nginx/nginx.conf
COPY docker/laravel/default.conf /etc/nginx/http.d/default.conf
# CHANGED: Copy supervisord.conf to the default system location
COPY docker/laravel/supervisord.conf /etc/supervisord.conf
COPY docker/laravel/entrypoint.sh /usr/local/bin/entrypoint.sh

# Make entrypoint executable
RUN chmod +x /usr/local/bin/entrypoint.sh

# CRON SETUP (FIXED & SILENCED)
# 1. Write to ROOT crontab
# 2. Use 'su' to run the actual PHP command as 'www-data' user
# 3. Redirect stdout (INFO messages) to /dev/null to prevent log spam
# 4. Keep stderr (errors) redirected to PID 1 stderr so you see CRITICAL issues
RUN echo "* * * * * su -s /bin/sh www-data -c 'cd /var/www/html && /usr/local/bin/php artisan schedule:run' > /dev/null 2>> /proc/1/fd/2" > /etc/crontabs/root

# Create directories and set permissions
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    && mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Expose port
EXPOSE 80

# Entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]