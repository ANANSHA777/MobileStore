FROM php:8.2-apache

# Install PDO MySQL extension for database queries
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache URL rewrite module
RUN a2enmod rewrite

# Copy project files into standard Apache web directory
COPY . /var/www/html/

# Set correct working directory and permissions
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80