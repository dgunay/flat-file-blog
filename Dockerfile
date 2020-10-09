FROM php:7.4

RUN apt update
RUN apt -y install zlib1g-dev libzip-dev unzip
RUN docker-php-ext-install zip

RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

COPY . .
