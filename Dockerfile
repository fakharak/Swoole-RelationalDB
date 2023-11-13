FROM php:8.2-cli

ARG uid

RUN pecl install openswoole && docker-php-ext-enable openswoole

RUN usermod -u 1000 www-data

# install pcov
RUN pecl install pcov && docker-php-ext-enable pcov

# install gd
RUN apt-get update && apt-get install -y zlib1g-dev libpng-dev libzip-dev libfreetype-dev fonts-arkpandora
RUN docker-php-ext-configure gd --with-freetype
RUN docker-php-ext-install gd

# install composer
RUN apt-get update && \
    apt-get install -y git zip
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/bin --filename=composer
RUN chmod 755 /usr/bin/composer

# usual packages
RUN apt-get update && apt-get install -y procps

# system setup
RUN mkdir /usr/lib/small-swoole-db
WORKDIR /usr/lib/small-swoole-db

USER www-data

ENTRYPOINT sleep infinity