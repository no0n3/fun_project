FROM php:7.2.2-apache

RUN apt-get update && apt-get install -y \
        curl \
        software-properties-common \
        gnupg \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        unzip \ 
    && curl -sL https://deb.nodesource.com/setup_8.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd \
    && docker-php-ext-install zip \
    && curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# install ffmpeg
RUN apt-get install ffmpeg -y

# install git
RUN apt-get install -y git

COPY . /var/www/html/
COPY ./docker/web/php.ini /usr/local/etc/php/php.ini
COPY ./docker/web/vhost.conf /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html/ \
    && a2enmod rewrite

WORKDIR /var/www/html

RUN composer install
