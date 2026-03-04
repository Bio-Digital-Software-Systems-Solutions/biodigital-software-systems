# Multi-stage Dockerfile for Laravel + React/Inertia application

# ==========================================
# Stage 1: Node.js build stage for frontend
# ==========================================
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copy package files
COPY package.json package-lock.json ./

# Install dependencies
RUN npm ci

# Copy source files needed for build
COPY resources ./resources
COPY vite.config.js tsconfig.json tailwind.config.js postcss.config.js ./

# Build frontend assets
RUN npm run build

# ==========================================
# Stage 2: Composer dependencies
# ==========================================
FROM composer:2.7 AS composer-builder

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies without dev dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# ==========================================
# Stage 3: Production PHP image
# ==========================================
FROM php:8.4-fpm-alpine AS production

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    gd \
    zip \
    bcmath \
    opcache \
    intl \
    pcntl \
    exif

# Install Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Clean up
RUN apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/* /tmp/*

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy application from composer stage
COPY --from=composer-builder /app /var/www/html

# Copy built assets from node stage
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Create necessary directories
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Switch to non-root user
USER www-data

# Expose port 9000
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]

# ==========================================
# Stage 4: Development PHP image
# ==========================================
FROM php:8.4-fpm-alpine AS development

# Set working directory
WORKDIR /var/www/html

# Install system dependencies (including make for WSL/Windows compatibility)
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    linux-headers \
    bash \
    nodejs \
    npm \
    make \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    gd \
    zip \
    bcmath \
    opcache \
    intl \
    pcntl \
    exif

# Install Redis and Xdebug extensions
RUN pecl install redis xdebug \
    && docker-php-ext-enable redis xdebug

# Clean up build dependencies but keep runtime ones
RUN rm -rf /var/cache/apk/* /tmp/*

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy PHP configuration
COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Create necessary directories
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Expose port 9000
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
