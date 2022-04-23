# Развёртывание приложения

## Настраиваем виртуальную машину
1. Заходим в виртуалку и устанавливаем окружение командами (команды для ubuntu 20.04)
    ```shell
    sudo apt update
    sudo apt install curl git unzip nginx postgresql postgresql-contrib rabbitmq-server supervisor memcached libmemcached-tools redis-server php-cli php-fpm php-json php-common php-mysql php-zip php-gd php-mbstring php-curl php-xml php-pear php-bcmath php-pgsql php-memcached php-redis
    ```
2. Устанавливаем composer
    ```shell
    curl -sS https://getcomposer.org/installer -o composer-setup.php
    sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    ```
3. Создаём БД командами
    ```shell
    sudo -u postgres bash -c "psql -c \"CREATE DATABASE twitter ENCODING 'UTF8' TEMPLATE = template0\""
    sudo -u postgres bash -c "psql -c \"CREATE USER my_user WITH PASSWORD '1H8a61ceQW7htGRE6iVz'\""
    sudo -u postgres bash -c "psql -c \"GRANT ALL PRIVILEGES ON DATABASE twitter TO my_user\""
    ```
4. Разрешаем доступ к БД снаружи
    1. В файл `/etc/postgresql/12/main/pg_hba.conf` добавляем строки
        ```
        host    all     all     0.0.0.0/0       md5
        host    all     all     ::/0            md5
        ```
    2. В файле `/etc/postgresql/12/main/postgresql.conf` находим закомментированную строку с параметром
       `listen_addresses` и заменяем её на
        ```
        listen_addresses='*'
        ```
    3. Перезапускаем сервис `postgresql` командой `sudo service postgresql restart`
5. Проверяем, что по порту 5432 можем попасть в БД twitter с реквизитами my_user / 1H8a61ceQW7htGRE6iVz
6. Конфигурируем RabbitMQ командами (по одной команде за раз)
    ```shell
    sudo rabbitmq-plugins enable rabbitmq_management 
    sudo rabbitmq-plugins enable rabbitmq_consistent_hash_exchange
    sudo rabbitmqctl add_user my_user T1y04lWk167MkyEK3YFk
    sudo rabbitmqctl set_user_tags my_user administrator
    sudo rabbitmqctl set_permissions -p / my_user ".*" ".*" ".*"
    ```
7. Проверяем, что по порту 15672 можем залогиниться с указанными кредами
8. Дадим права на работу с каталогом `var/www` всем командой `sudo chmod 777 /var/www`
9. В файле `/etc/nginx/nginx.conf` исправляем строку (актуально для AWS EC2)
    ```
    server_name_hash_bucket_size 128;
    ```
10. Перезапускаем nginx командой `sudo service nginx restart`
11. В файл `/etc/sudoers` добавляем строку
     ```
     www-data ALL=(ALL) NOPASSWD:ALL
     ```

## Добавляем скрипт развёртывания

1. Переносим код в Gitlab:
    1. заводим новый репозиторий на GitLab
    2. клонируем его
    3. помещаем туда код проекта
    4. пушим правки в репозиторий
2. В репозитории в GitLab
    1. заходим в раздел `Settings -> CI / CD` и добавляем переменные окружения
        1. `SERVER1` - адрес сервера
        2. `SSH_USER` - имя пользователя для входа по ssh (для AWS EC2 - `ubuntu`)
        3. `SSH_PRIVATE_KEY` - приватный ключ, закодированный в base64
        4. `DATABASE_HOST` - `localhost`
        5. `DATABASE_NAME` - `twitter`
        6. `DATABASE_USER` - `my_user`
        7. `DATABASE_PASSWORD` - `1H8a61ceQW7htGRE6iVz`
        8. `RABBITMQ_HOST` - `localhost`
        9. `RABBITMQ_USER` - `my_user`
        10. `RABBITMQ_PASSWORD` - `T1y04lWk167MkyEK3YFk`
    2. заходим в раздел `Settings -> Repository` и добавляем deploy token с правами `read_repository`, сохраняем пароль
3. Создаём файл `deploy/nginx.conf`
    ```
    server {
        listen 80;
    
        server_name %SERVER_NAME%;
        error_log  /var/log/nginx/error.log;
        access_log /var/log/nginx/access.log;
        root /var/www/demo/public;
    
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
            fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        }
    }
    ```
4. Создаём файл `deploy/supervisor.conf`
    ```
    [program:add_followers]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 add_followers --env=dev -vv
    process_name=add_follower_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.add_followers.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.add_followers.error.log
    stderr_capture_maxbytes=1MB
    
    [program:publish_tweet]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 publish_tweet --env=dev -vv
    process_name=publish_tweet_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.publish_tweet.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.publish_tweet.error.log
    stderr_capture_maxbytes=1MB
    
    [program:send_notification_email]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 send_notification.email --env=dev -vv
    process_name=send_notification_email_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.send_notification_email.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.send_notification_email.error.log
    stderr_capture_maxbytes=1MB
    
    [program:send_notification_sms]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 send_notification.sms --env=dev -vv
    process_name=send_notification_sms_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.send_notification_sms.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.send_notification_sms.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_0]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 update_feed_0 --env=dev -vv
    process_name=update_feed_0_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_1]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 update_feed_1 --env=dev -vv
    process_name=update_feed_1_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_2]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 update_feed_2 --env=dev -vv
    process_name=update_feed_2_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_3]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 update_feed_3 --env=dev -vv
    process_name=update_feed_3_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_4]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 update_feed_4 --env=dev -vv
    process_name=update_feed_4_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_5]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 update_feed_5 --env=dev -vv
    process_name=update_feed_5_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_6]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 update_feed_6 --env=dev -vv
    process_name=update_feed_6_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_7]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 update_feed_7 --env=dev -vv
    process_name=update_feed_7_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_8]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 update_feed_8 --env=dev -vv
    process_name=update_feed_8_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_9]
    command=php /var/www/demo/bin/console rabbitmq:consumer -m 1000 update_feed_9 --env=dev -vv
    process_name=update_feed_9_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    ```
5. Исправляем файл `.env`
    ```shell
    # In all environments, the following files are loaded if they exist,
    # the latter taking precedence over the former:
    #
    #  * .env                contains default values for the environment variables needed by the app
    #  * .env.local          uncommitted file with local overrides
    #  * .env.$APP_ENV       committed environment-specific defaults
    #  * .env.$APP_ENV.local uncommitted environment-specific overrides
    #
    # Real environment variables win over .env files.
    #
    # DO NOT DEFINE PRODUCTION SECRETS IN THIS FILE NOR IN ANY OTHER COMMITTED FILES.
    #
    # Run "composer dump-env prod" to compile .env files for production use (requires symfony/flex >=1.2).
    # https://symfony.com/doc/current/best_practices.html#use-environment-variables-for-infrastructure-configuration
    
    ###> symfony/framework-bundle ###
    SHELL_VERBOSITY=-1
    APP_ENV=dev
    APP_SECRET=9136beb53e9df6ee23cfc8c18a8feec2
    ###< symfony/framework-bundle ###
    
    ###> doctrine/doctrine-bundle ###
    # Format described at https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url
    # IMPORTANT: You MUST configure your server version, either here or in config/packages/doctrine.yaml
    #
    # DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
    # DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=5.7"
    DATABASE_URL=postgresql://%DATABASE_USER%:%DATABASE_PASSWORD%@%DATABASE_HOST%:5432/%DATABASE_NAME%?serverVersion=11&charset=utf8
    ###< doctrine/doctrine-bundle ###
    
    ###> lexik/jwt-authentication-bundle ###
    JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
    JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
    JWT_PASSPHRASE=1213716e67195d6f15bf6549ac1353d4
    JWT_TTL_SEC=3600
    ###< lexik/jwt-authentication-bundle ###
    
    ###> sentry/sentry-symfony ###
    SENTRY_DSN=http://470a12f0ab5b4d27bf411f323df16d97@sentry:9000/2
    ###< sentry/sentry-symfony ###
    
    MEMCACHED_DSN=memcached://localhost:11211
    
    REDIS_DSN=redis://localhost:6379
    
    ###> php-amqplib/rabbitmq-bundle ###
    RABBITMQ_URL=amqp://%RABBITMQ_USER%:%RABBITMQ_PASSWORD%@%RABBITMQ_HOST%:5672
    RABBITMQ_VHOST=/
    ###< php-amqplib/rabbitmq-bundle ###
    
    ###> friendsofsymfony/elastica-bundle ###
    ELASTICSEARCH_URL=http://elasticsearch:9200/
    ###< friendsofsymfony/elastica-bundle ###
    
    ###> symfony/lock ###
    # Choose one of the stores below
    # postgresql+advisory://db_user:db_password@localhost/db_name
    LOCK_DSN=semaphore
    ###< symfony/lock ###
    ```
6. Создаём файл `deploy.sh`
    ```shell
    sudo cp deploy/nginx.conf /etc/nginx/conf.d/demo.conf -f
    sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/demo.conf -f
    sudo sed -i -- "s|%SERVER_NAME%|$1|g" /etc/nginx/conf.d/demo.conf
    sudo service nginx restart
    sudo -u www-data composer install -q
    sudo service php7.4-fpm restart
    sudo -u www-data sed -i -- "s|%DATABASE_HOST%|$2|g" .env
    sudo -u www-data sed -i -- "s|%DATABASE_USER%|$3|g" .env
    sudo -u www-data sed -i -- "s|%DATABASE_PASSWORD%|$4|g" .env
    sudo -u www-data sed -i -- "s|%DATABASE_NAME%|$5|g" .env
    sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction
    sudo -u www-data sed -i -- "s|%RABBITMQ_HOST%|$6|g" .env
    sudo -u www-data sed -i -- "s|%RABBITMQ_USER%|$7|g" .env
    sudo -u www-data sed -i -- "s|%RABBITMQ_PASSWORD%|$8|g" .env
    sudo service supervisor restart
    ```
7. Создаём файл `.gitlab-ci.yml` (не забудьте указать корректный путь к репозиторию в git clone и креды)
    ```yml
    before_script:
      - apt-get update -qq
      - apt-get install -qq git
      - 'which ssh-agent || ( apt-get install -qq openssh-client )'
      - eval $(ssh-agent -s)
      - ssh-add <(echo "$SSH_PRIVATE_KEY" | base64 -d)
      - mkdir -p ~/.ssh
      - echo -e "Host *\n\tStrictHostKeyChecking no\n\n" > ~/.ssh/config
    
    deploy_server1:
      stage: deploy
      environment:
        name: server1
        url: $SERVER1
      script:
        - ssh $SSH_USER@$SERVER1 "sudo rm -rf /var/www/demo &&
              cd /var/www &&
              git clone https://deploy:ZsZAGdePfH2QCoBYQkh2@gitlab.com/raptor-mvk/deploy-test.git demo &&
              sudo chown www-data:www-data demo -R &&
              cd demo &&
              sh ./deploy.sh $SERVER1 $DATABASE_HOST $DATABASE_USER $DATABASE_PASSWORD $DATABASE_NAME $RABBITMQ_HOST $RABBITMQ_USER $RABBITMQ_PASSWORD"
      only:
        - master
    ```
8. Добавляем код в master-ветку и пушим в GitLab
9. В репозитории в GitLab в разделе `CI / CD -> Pipelines` можно следить за процессом
10. Проверяем в интерфейсе RabbitMQ, что консьюмеры запустились
11. Проверяем POST-запросом на `/api/v1/user`, что API отвечает
   
## Переходим на blue-green deploy

1. На сервере
    1. удаляем на сервере содержимое каталог `/var/www/demo`
    2. создаём каталог `/var/www/demo/shared/log`
    3. выполняем команду `chmod 777 /var/www/demo -R`
2. Исправляем файл `deploy/nginx.conf`
    ```
    server {
        listen 80;
    
        server_name %SERVER_NAME%;
        error_log  /var/log/nginx/error.log;
        access_log /var/log/nginx/access.log;
        root /var/www/demo/current/public;
    
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
            fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        }
    }
    ```
3. Исправлям файл `deploy/supervisor.conf`
    ```
    [program:add_followers]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 add_followers --env=dev -vv
    process_name=add_follower_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.add_followers.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.add_followers.error.log
    stderr_capture_maxbytes=1MB
    
    [program:publish_tweet]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 publish_tweet --env=dev -vv
    process_name=publish_tweet_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.publish_tweet.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.publish_tweet.error.log
    stderr_capture_maxbytes=1MB
    
    [program:send_notification_email]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 send_notification.email --env=dev -vv
    process_name=send_notification_email_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.send_notification_email.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.send_notification_email.error.log
    stderr_capture_maxbytes=1MB
    
    [program:send_notification_sms]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 send_notification.sms --env=dev -vv
    process_name=send_notification_sms_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.send_notification_sms.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.send_notification_sms.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_0]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 update_feed_0 --env=dev -vv
    process_name=update_feed_0_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_1]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 update_feed_1 --env=dev -vv
    process_name=update_feed_1_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_2]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 update_feed_2 --env=dev -vv
    process_name=update_feed_2_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_3]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 update_feed_3 --env=dev -vv
    process_name=update_feed_3_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_4]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 update_feed_4 --env=dev -vv
    process_name=update_feed_4_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_5]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 update_feed_5 --env=dev -vv
    process_name=update_feed_5_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_6]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 update_feed_6 --env=dev -vv
    process_name=update_feed_6_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_7]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 update_feed_7 --env=dev -vv
    process_name=update_feed_7_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_8]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 update_feed_8 --env=dev -vv
    process_name=update_feed_8_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_9]
    command=php /var/www/demo/current/bin/console rabbitmq:consumer -m 1000 update_feed_9 --env=dev -vv
    process_name=update_feed_9_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/var/www/demo/current/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/var/www/demo/current/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    ```
4. Исправляем `.gitlab-ci.yml`
    ```yaml
    before_script:
      - apt-get update -qq
      - apt-get install -qq git
      - 'which ssh-agent || ( apt-get install -qq openssh-client )'
      - eval $(ssh-agent -s)
      - ssh-add <(echo "$SSH_PRIVATE_KEY" | base64 -d)
      - mkdir -p ~/.ssh
      - echo -e "Host *\n\tStrictHostKeyChecking no\n\n" > ~/.ssh/config
      - export DIR=$(date +%Y%m%d_%H%M%S)
    
    deploy_server1:
      stage: deploy
      environment:
        name: server1
        url: $SERVER1
      script:
        - ssh $SSH_USER@$SERVER1 "cd /var/www/demo &&
              git clone https://deploy:ZsZAGdePfH2QCoBYQkh2@gitlab.com/raptor-mvk/deploy-test.git $DIR &&
              sudo chown www-data:www-data $DIR -R &&
              cd $DIR &&
              sh ./deploy.sh $SERVER1 $DATABASE_HOST $DATABASE_USER $DATABASE_PASSWORD $DATABASE_NAME $RABBITMQ_HOST $RABBITMQ_USER $RABBITMQ_PASSWORD &&
              cd .. &&
              rm -rf /var/www/demo/$DIR/var/log &&
              ln -s /var/www/demo/shared/log /var/www/demo/$DIR/var/log &&
              ( [ ! -d /var/www/demo/current ] || mv -Tf /var/www/demo/current /var/www/demo/previous ) &&
              ln -s /var/www/demo/$DIR /var/www/demo/current"
      only:
        - master
    ```
5. Пушим код в репозиторий

## Добавляем rollback

1. Добавляем файл `rollback.sh`
    ```shell
    sudo cp deploy/nginx.conf /etc/nginx/conf.d/demo.conf -f
    sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/demo.conf -f
    sudo sed -i -- "s|%SERVER_NAME%|$1|g" /etc/nginx/conf.d/demo.conf
    sudo service nginx restart
    sudo service php7.4-fpm restart
    sudo -u www-data php bin/console cache:clear
    sudo service supervisor restart
    ```
2. Ещё раз исправляем `.gitlab-ci.yml`
    ```yaml
    stages:
      - deploy
      - rollback

    before_script:
      - apt-get update -qq
      - apt-get install -qq git
      - 'which ssh-agent || ( apt-get install -qq openssh-client )'
      - eval $(ssh-agent -s)
      - ssh-add <(echo "$SSH_PRIVATE_KEY" | base64 -d)
      - mkdir -p ~/.ssh
      - echo -e "Host *\n\tStrictHostKeyChecking no\n\n" > ~/.ssh/config
      - export DIR=$(date +%Y%m%d_%H%M%S)
    
    deploy_server1:
      stage: deploy
      environment:
        name: server1
        url: $SERVER1
      script:
        - ssh $SSH_USER@$SERVER1 "cd /var/www/demo &&
              git clone https://deploy:ZsZAGdePfH2QCoBYQkh2@gitlab.com/raptor-mvk/deploy-test.git $DIR &&
              sudo chown www-data:www-data $DIR -R &&
              cd $DIR &&
              sh ./deploy.sh $SERVER1 $DATABASE_HOST $DATABASE_USER $DATABASE_PASSWORD $DATABASE_NAME $RABBITMQ_HOST $RABBITMQ_USER $RABBITMQ_PASSWORD
              cd .. &&
              rm -rf /var/www/demo/$DIR/var/log &&
              ln -s /var/www/demo/shared/log /var/www/demo/$DIR/var/log &&
              ( [ ! -d /var/www/demo/current ] || mv -Tf /var/www/demo/current /var/www/demo/previous ) &&
              ln -s /var/www/demo/$DIR /var/www/demo/current"
      only:
        - master
   
    rollback:
      stage: rollback
      script:
        - ssh $SSH_USER@$SERVER1 "unlink /var/www/demo/current &&
              mv -Tf /var/www/demo/previous /var/www/demo/current &&
              cd /var/www/demo/current &&
              sh ./rollback.sh $SERVER1"
      when: manual
    ```
