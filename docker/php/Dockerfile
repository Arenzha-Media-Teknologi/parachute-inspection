# docker/php/Dockerfile

# Gunakan base image PHP 8.3 FPM versi Alpine (ringan)
FROM php:8.3-fpm-alpine

# Set direktori kerja
WORKDIR /var/www/html

# Install dependensi sistem yang umum dibutuhkan
# --- DITAMBAHKAN: nodejs, npm, chromium, dan dependensinya ---
RUN apk add --no-cache \
    build-base \
    libpng-dev \
    libzip-dev \
    zip \
    curl \
    git \
    oniguruma-dev \
    libxml2-dev \
    nodejs \
    npm \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ttf-freefont

# Install ekstensi PHP yang dibutuhkan Laravel
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    xml

# Install Composer (package manager untuk PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ganti user ke non-root untuk keamanan
USER www-data

# Expose port untuk FPM
EXPOSE 9000

# Perintah default untuk menjalankan PHP-FPM
CMD ["php-fpm"]