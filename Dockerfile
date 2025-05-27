# Use official PHP + Apache image
FROM php:8.2-apache

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy all project files into Apache root
COPY . /var/www/html/

# Set the working directory
WORKDIR /var/www/html/

# Railway automatically handles port exposure
