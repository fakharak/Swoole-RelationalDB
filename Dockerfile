FROM php:8.3-cli

ARG uid
ARG project

RUN pecl install $project && docker-php-ext-enable $project

RUN usermod -u $uid www-data

# install pcov
RUN pecl install pcov && docker-php-ext-enable pcov

# install gd
RUN apt-get update && apt-get install -y zlib1g-dev libpng-dev libzip-dev libfreetype-dev fonts-arkpandora
RUN docker-php-ext-configure gd --with-freetype
RUN docker-php-ext-install gd

# install bcmath for msall-collection
RUN docker-php-ext-install bcmath

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
COPY . /usr/lib/small-swoole-db
WORKDIR /usr/lib/small-swoole-db
RUN chown www-data:www-data /var/www

USER www-data

ENTRYPOINT sleep infinity