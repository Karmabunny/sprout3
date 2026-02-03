FROM php:8.2-cli-alpine

RUN apk add --update --no-cache zip git bash openssh

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && install-php-extensions \
    pcntl intl zip pdo_mysql
