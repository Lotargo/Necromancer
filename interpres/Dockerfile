# Navis pro Interprete (PHP)
FROM public.ecr.aws/docker/library/php:8.3-cli-alpine

WORKDIR /app/interpres

# Copy the PHP scripts
COPY . .

# Expose the web server port
EXPOSE 8000

# Start the built-in PHP development server
CMD ["php", "-S", "0.0.0.0:8000"]
