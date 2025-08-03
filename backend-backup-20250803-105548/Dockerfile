# PHP Backend Dockerfile
FROM php:8.2-apache

# Enable Apache mod_rewrite for clean URLs
RUN a2enmod rewrite

# Install system dependencies for PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions for PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql

# Set the working directory
WORKDIR /var/www/html

# Copy PHP source files to the container
COPY src/ /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80