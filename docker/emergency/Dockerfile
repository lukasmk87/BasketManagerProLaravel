# BasketManager Pro - Emergency Services Docker Configuration
# Optimized for high availability and fast response times

FROM php:8.3-fpm-alpine

LABEL maintainer="BasketManager Pro Emergency Team"
LABEL version="1.0.0"
LABEL description="Emergency Services Container with optimized performance"

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    supervisor \
    nginx \
    redis \
    postgresql-client \
    imagemagick \
    imagemagick-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    nodejs \
    npm

# Install PHP extensions optimized for emergency services
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        gd \
        intl \
        zip \
        opcache \
        bcmath \
        pcntl \
        sockets \
        xml \
        mbstring

# Install Imagick
RUN pecl install imagick \
    && docker-php-ext-enable imagick

# Install Redis extension for caching
RUN pecl install redis \
    && docker-php-ext-enable redis

# Configure PHP for emergency services (high performance, low latency)
COPY docker/emergency/php.ini /usr/local/etc/php/php.ini
COPY docker/emergency/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create application directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install and build Node.js dependencies
RUN npm ci --only=production \
    && npm run build \
    && rm -rf node_modules

# Configure Nginx for emergency services
COPY docker/emergency/nginx.conf /etc/nginx/nginx.conf
COPY docker/emergency/emergency.conf /etc/nginx/http.d/default.conf

# Configure Supervisor for process management
COPY docker/emergency/supervisord.conf /etc/supervisord.conf

# Create necessary directories
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/log/nginx \
    && mkdir -p /var/log/php \
    && mkdir -p /var/www/html/storage/app/public/emergency_qr \
    && mkdir -p /var/www/html/storage/logs/emergency

# Emergency services optimization
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Health check for emergency services
COPY docker/emergency/healthcheck.sh /usr/local/bin/healthcheck.sh
RUN chmod +x /usr/local/bin/healthcheck.sh

HEALTHCHECK --interval=10s --timeout=3s --start-period=30s --retries=3 \
    CMD /usr/local/bin/healthcheck.sh

# Expose ports
EXPOSE 80 443

# Start services via Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

# Emergency services environment variables
ENV APP_ENV=production
ENV EMERGENCY_CACHE_TTL=86400
ENV EMERGENCY_QR_STORAGE=public
ENV EMERGENCY_LOG_LEVEL=info
ENV EMERGENCY_RESPONSE_TIMEOUT=5
ENV EMERGENCY_MAX_CONCURRENT_ACCESS=100