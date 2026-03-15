FROM php:8.3-fpm

ARG UID=1000
ARG GID=1000

# System dependencies + Node.js 22 LTS
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    librdkafka-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath \
    && pecl install rdkafka redis \
    && docker-php-ext-enable rdkafka redis \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Match host user UID/GID so files are owned by your user
RUN usermod -u ${UID} www-data && groupmod -g ${GID} www-data

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
