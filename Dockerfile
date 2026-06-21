FROM php:8.2-apache

ARG TARGETPLATFORM=linux/amd64

RUN docker-php-ext-install pdo pdo_mysql

RUN a2enmod rewrite