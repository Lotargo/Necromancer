# Navis pro Interprete (PHP)
ARG PHP_BASE_IMAGE=php:8.3-cli-alpine
ARG COMPOSER_BASE_IMAGE=composer:2

FROM ${COMPOSER_BASE_IMAGE} AS composer_base
FROM ${PHP_BASE_IMAGE}

ARG INSTALL_SYSTEM_DEPS=1

RUN rm -rf /app/interpres

WORKDIR /app/interpres

# Copy the PHP scripts
COPY . .

# Install Composer and dependencies
COPY --from=composer_base /usr/bin/composer /usr/bin/composer
RUN if [ "$INSTALL_SYSTEM_DEPS" = "1" ]; then apk add --no-cache git unzip; fi \
    && composer install --no-dev --optimize-autoloader

# Expose the web server port
EXPOSE 8000

# Start the built-in PHP development server
CMD ["php", "-S", "0.0.0.0:8000"]
