# Установка и "Hello, world"

## Устанавливаем Symfony
1. Инициализируем проект командой `composer create-project symfony/skeleton otus_project "5.4.*`
2. Добавляем в .gitignore служебный каталог PhpStorm
    ```gitignore
    .idea/*
    ```

## Добавляем WorldController с экшеном hello
   
1. Создаём класс `App\Controller\WorldController`
    ```php
    <?php

    namespace App\Controller;

    use Symfony\Component\HttpFoundation\Response;

    class WorldController
    {
        public function hello(): Response
        {
            return new Response('<html><body><h1><b>Hello,</b> <i>world</i>!</h1></body></html>');
        }
    }
    ```
2. В файле `config/routes.yaml` добавляем описание endpoint'а
    ```yaml
    hello_world:
        path: /world/hello
        controller: App\Controller\WorldController::hello
    ```
3. Выполняем команду `symfony serve`
4. Заходим по адресу `http://localhost:8000`, видим приветственную страницу Symfony
5. Заходим по адресу `http://localhost:8000/world/hello`, видим результат работы нашего контроллера

## Добавляем Docker

1. Создаём файл `docker-compose.yml`
    ```yaml
    version: '3.7'
    
    services:
    
      php-fpm:
        build: docker
        container_name: 'php'
        ports:
          - '9000:9000'
        volumes:
          - ./:/app
        working_dir: /app
    
      nginx:
        image: nginx
        container_name: 'nginx'
        working_dir: /app
        ports:
          - '7777:80'
        volumes:
          - ./:/app
          - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    ```
2. Создаём файл `docker\nginx.conf`
   ```
   server {
       listen 80;
    
       server_name localhost;
       error_log  /var/log/nginx/error.log;
       access_log /var/log/nginx/access.log;
       root /app/public;
    
       rewrite ^/index\.php/?(.*)$ /$1 permanent;
    
       try_files $uri @rewriteapp;
    
       location @rewriteapp {
           rewrite ^(.*)$ /index.php/$1 last;
       }
    
       # Deny all . files
       location ~ /\. {
           deny all;
       }
    
       location ~ ^/index\.php(/|$) {
           fastcgi_split_path_info ^(.+\.php)(/.*)$;
           include fastcgi_params;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
           fastcgi_param PATH_INFO $fastcgi_path_info;
           fastcgi_index index.php;
           send_timeout 1800;
           fastcgi_read_timeout 1800;
           fastcgi_pass php:9000;
       }
   }
   ```
3. Создаём файл `docker\Dockerfile`
    ```dockerfile
    FROM php:8.0.14-fpm-alpine
    
    # Install dev dependencies
    RUN apk update \
        && apk upgrade --available \
        && apk add --virtual build-deps \
            autoconf \
            build-base \
            icu-dev \
            libevent-dev \
            openssl-dev \
            zlib-dev \
            libzip \
            libzip-dev \
            zlib \
            zlib-dev \
            bzip2 \
            git \
            libpng \
            libpng-dev \
            libjpeg \
            libjpeg-turbo-dev \
            libwebp-dev \
            freetype \
            freetype-dev \
            postgresql-dev \
            curl \
            wget \
            bash
    
    # Install Composer
    RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
    
    # Install PHP extensions
    RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
    RUN docker-php-ext-install -j$(getconf _NPROCESSORS_ONLN) \
        intl \
        gd \
        bcmath \
        pdo_pgsql \
        sockets \
        zip
    RUN pecl channel-update pecl.php.net \
        && pecl install -o -f \
            redis \
            event \
        && rm -rf /tmp/pear \
        && echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini \
        && echo "extension=event.so" > /usr/local/etc/php/conf.d/event.ini
    ```
4. Запускаем контейнеры командой `docker-compose up -d`
5. Заходим по адресу `http://localhost:7777/world/hello`, видим результат работы нашего контроллера
