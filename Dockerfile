FROM php:8.2-fpm-alpine

# Arguments defined in docker-compose.yml
ARG user
ARG uid

WORKDIR /var/www

# Install system dependencies
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    vim \
    unzip \
    git \
    icu-dev \
    freetype-dev libjpeg-turbo-dev libpng-dev libzip-dev zlib-dev \
    libpq-dev # For PostgreSQL, if needed later

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg=/usr/include/ --with-freetype=/usr/include/
RUN docker-php-ext-install -j$(nproc) gd pdo pdo_mysql pdo_pgsql zip bcmath intl exif

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . /var/www

# Install Composer dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction

USER root

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"] 