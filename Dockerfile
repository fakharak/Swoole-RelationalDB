FROM openswoole/swoole:22.0-php8.2

# install pcov
RUN pecl install pcov && docker-php-ext-enable pcov

# install composer
RUN apt-get update && \
    apt-get install -y git zip
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/bin --filename=composer
RUN chmod 755 /usr/bin/composer

# system setup
RUN mkdir /usr/lib/small-swoole-db
WORKDIR /usr/lib/small-swoole-db

USER www-data

ENTRYPOINT sleep infinity