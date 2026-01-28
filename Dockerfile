FROM php:8.4-fpm-alpine AS php

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    autoconf \
    build-base \
    libzip-dev \
    && yes '' | pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_mysql zip opcache \
    && apk del autoconf build-base

# Create upload directory with proper permissions
RUN install -o www-data -g www-data -d /var/www/upload/image/

# Create session directory with proper permissions
RUN mkdir -p /tmp/sessions && chown -R www-data:www-data /tmp/sessions

# Copy PHP configuration
COPY ./php.ini ${PHP_INI_DIR}/php.ini

# Set working directory
WORKDIR /var/www/public

# Switch to non-root user (www-data) for better security
USER www-data

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=3 \
    CMD php-fpm-healthcheck || exit 1