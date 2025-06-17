FROM php:8.3-cli

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Symfony requirements and extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

# Set working directory
WORKDIR /app

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application files
COPY . .

# Create storage directory and set permissions
RUN mkdir -p var/storage && chmod -R 777 var/

# Environment setup
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV PORT=10000

# Expose the port
EXPOSE ${PORT}

# Start PHP's built-in server
CMD ["php", "-S", "0.0.0.0:${PORT}", "-t", "public"]