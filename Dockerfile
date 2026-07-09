FROM php:8.3-apache

RUN apt-get update && apt-get install -y libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite && a2enmod rewrite headers

RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf && \
    sed -i '/<Directory \/var\/www\/html>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY . /var/www/html

RUN ln -s ../covers /var/www/html/public/covers && \
    chown www-data:www-data /var/www/html && \
    chmod u+w /var/www/html

EXPOSE 80
