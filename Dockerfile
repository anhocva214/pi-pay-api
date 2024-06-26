FROM php:7.2.34

EXPOSE 8181

RUN apt-get update && \
    apt-get install -y libonig-dev
RUN apt-get install -y openssl zip unzip git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pdo pdo_mysql mbstring
WORKDIR /root
RUN chown -R www-data:www-data ./

COPY . /root
# RUN cp .env.example .env
RUN composer install

RUN php artisan storage:link
#RUN php artisan config:cache
RUN php artisan optimize:clear
RUN php artisan key:generate

CMD php artisan serve --host=0.0.0.0 --port=8181