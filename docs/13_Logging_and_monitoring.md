# Логирование и пониторинг

Запускаем контейнеры командой `docker-compose up -d`

## Логирование с помощью Monolog

### Добавляем monolog-bundle и логируем сообщения 

1. Входим в контейнер командой `docker exec -it php sh`. Дальнейшие команды выполняются из контейнера
2. Устанавливаем пакет `symfony/monolog-bundle`
3. В файле `config/packages/security.yaml` в секцию `firewalls.main` добавляем параметр `security: false`   
4. В классе `App\Controller\SaveUser\v5\SaveUserManager`
    1. Добавляем инъекцию `LoggerInterface`
        ```php
        private LoggerInterface $logger;
    
        public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, LoggerInterface $logger)
        {
            $this->entityManager = $entityManager;
            $this->serializer = $serializer;
            $this->logger = $logger;
        }
        ```
    2. В начало метода `saveUser` добавляем
        ```php
        $this->logger->debug('This is debug message');
        $this->logger->info('This is info message');
        $this->logger->notice('This is notice message');
        $this->logger->warning('This is warning message');
        $this->logger->error('This is error message');
        $this->logger->critical('This is critical message');
        $this->logger->alert('This is alert message');
        $this->logger->emergency('This is emergency message');
        ```
5. Выполняем запрос Add user v5 из Postman-коллекции v5 и проверяем, что логи попадают в файл `var/log/dev.log`

### Настраиваем уровень логирования

1. Заменяем в `config/packages/monolog.yaml` значение в секции `when@dev.monolog.handlers.main.level` на `critical`
2. Выполняем запрос Add user v5 из Postman-коллекции v5 и проверяем, что в файл `var/log/dev.log` попадают только
   сообщения с уровнями `critical`, `alert` и `emergency`
   
### Настраиваем режим fingers crossed

1. В файле `config/packages/monolog.yaml`
    1. Заменяем содержимое секции `when@dev.monolog.handlers.main`
        ```yaml
        type: fingers_crossed
        action_level: error
        handler: nested
        buffer_size: 3
        ```
    2. Добавляем в секцию `when@dev.monolog.handlers`
        ```yaml
        nested:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug
        ```
2. Выполняем запрос Add user v5 из Postman-коллекции v5 и проверяем, что в файл `var/log/dev.log` дополнительно попадают
   сообщение с уровнем `error` и два предыдущих сообщения с уровнем ниже

### Добавляем форматирование

1. Добавляем в `config/services.yaml`
    ```yaml
    monolog.formatter.app_formatter:
        class: Monolog\Formatter\LineFormatter
        arguments:
            - "[%%level_name%%]: [%%datetime%%] %%message%%\n"
    ```
2. Добавляем в `config/packages/monolog.yaml` в секцию `when@dev.monolog.handlers.main` форматтер
    ```yaml
    formatter: monolog.formatter.app_formatter
    ```
3. Выполняем запрос Add user v5 из Postman-коллекции v5 и проверяем, что в файл `var/log/dev.log` новые сообщения
   попадают с новом формате

## Интеграция с Sentry   

### Установка Sentry и бандла для интеграции с ним

1. Устанавливаем пакеты `nyholm/psr7`, `symfony/psr-http-message-bridge`, `sentry/sentry-symfony`
2. Добавляем сервисы Sentry в `docker-compose.yml` (не забудьте прописать описание volume `sentry-pgdb`)
    ```yaml
    redis:
        container_name: 'redis'
        image: redis
    
    sentry-postgres:
        image: postgres
        container_name: 'sentry-postgres'
        environment:
          POSTGRES_USER: sentry
          POSTGRES_PASSWORD: sentry
          POSTGRES_DB: sentry
        volumes:
         - sentry-pgdb:/var/lib/postgresql/data
    
    sentry:
        image: sentry
        container_name: 'sentry'
        links:
         - redis
         - sentry-postgres
        ports:
         - 10000:9000
        environment:
          SENTRY_SECRET_KEY: '&1k8n7lr_p9q5fd_5*kde9*p)&scu%pqi*3*rflw+b%mprdob)'
          SENTRY_POSTGRES_HOST: sentry-postgres
          SENTRY_DB_USER: sentry
          SENTRY_DB_PASSWORD: sentry
          SENTRY_REDIS_HOST: redis
    
    cron:
        image: sentry
        container_name: 'sentry-cron'
        links:
         - redis
         - sentry-postgres
        command: "sentry run cron"
        environment:
          SENTRY_SECRET_KEY: '&1k8n7lr_p9q5fd_5*kde9*p)&scu%pqi*3*rflw+b%mprdob)'
          SENTRY_POSTGRES_HOST: sentry-postgres
          SENTRY_DB_USER: sentry
          SENTRY_DB_PASSWORD: sentry
          SENTRY_REDIS_HOST: redis
    
    worker:
        image: sentry
        container_name: 'sentry-worker'
        links:
         - redis
         - sentry-postgres
        command: "sentry run worker"
        environment:
          SENTRY_SECRET_KEY: '&1k8n7lr_p9q5fd_5*kde9*p)&scu%pqi*3*rflw+b%mprdob)'
          SENTRY_POSTGRES_HOST: sentry-postgres
          SENTRY_DB_USER: sentry
          SENTRY_DB_PASSWORD: sentry
          SENTRY_REDIS_HOST: redis
    ```
3. Перезапускаем контейнеры и инициализируем Sentry
    1. Выходим из контейнера `php`
    2. Перезапускаем контейнеры
        ```shell
        docker-compose stop
        docker-compose up -d
        ```
    3. Инициализируем Sentry командой `docker exec -it sentry sentry upgrade`
    4. В процессе инициализации создаём суперпользователя `user@mail.com` / `password`
    5. Перезапускаем Sentry командой `docker-compose restart sentry`
4. Логинимся на `localhost:10000` с созданными реквизитами суперпользователя
5. Создаём новый проект, получаем DSN и прописываем его в файл `.env`
    ```shell
    http://DSN@sentry:9000/2
    ```
6. Выполняем запрос Add user с неуникальным логином из Postman-коллекции v5 и проверяем, что в Sentry появляется issue

### Игнорирование ошибок

1. Делаем POST-запрос на несуществующий endpoint `/api/v5/save-users`, проверяем, что в Sentry появляется issue
2. В файле `config/packages/sentry.yaml`
    1. Добавляем в секцию `sentry`
        ```yaml
        options:
            integrations:
                - 'Sentry\Integration\IgnoreErrorsIntegration'
        ```
    2. Добавляем сервис
        ```yaml
        services:
            Sentry\Integration\IgnoreErrorsIntegration:
               arguments:
                   $options:
                       ignore_exceptions:
                           - Symfony\Component\HttpKernel\Exception\NotFoundHttpException
        ```
3. Ещё раз делаем POST-запрос на несуществующий endpoint `/api/v1/users`, проверяем, что issue в Sentry больше не
   появляется

### Интеграция Monolog и Sentry

1. В `config/packages/sentry.yaml` добавляем сервис
    ```yaml
    Sentry\Monolog\Handler:
        arguments:
            $hub: '@Sentry\State\HubInterface'
            $level: !php/const Monolog\Logger::ERROR
    ```
2. В `config/packages/monolog.yaml` добавляем в секцию `when@dev.monolog.handlers.main`
    ```yaml
    sentry:
        type: service
        id: Sentry\Monolog\Handler
    ```
3. Выполняем запрос Add user v5 из Postman-коллекции v5 и проверяем, что в Sentry появляются ошибки уровней `error`,
   `critical`, `alert` и `emergency`
   
## Grafana для сбора метрик, интеграция с Graphite

1. Входим в контейнер командой `docker exec -it php sh` и устанавливаем пакет `slickdeals/statsd`
3. Добавляем сервисы Graphite и Grafana в `docker-compose.yml`
    ```yaml
    graphite:
        image: graphiteapp/graphite-statsd
        container_name: 'graphite'
        restart: always
        ports:
          - 8000:80
          - 2003:2003
          - 2004:2004
          - 2023:2023
          - 2024:2024
          - 8125:8125/udp
          - 8126:8126

    grafana:
        image: grafana/grafana
        container_name: 'grafana'
        restart: always
        ports:
          - 3000:3000
    ```
4. Выходим из контейнера `php` и перезапускаем контейнеры
    ```shell
    docker-compose stop
    docker-compose up -d
    ```
5. Проверяем, что можем зайти в интерфейс Graphite по адресу `localhost:8000`
6. Проверяем, что можем зайти в интерфейс Grafana по адресу `localhost:3000`, логин / пароль - `admin` / `admin`
7. Добавляем класс `App\Client\StatsdAPIClient`
    ```php
    <?php
    
    namespace App\Client;
    
    use Domnikl\Statsd\Client;
    use Domnikl\Statsd\Connection\UdpSocket;
    
    class StatsdAPIClient
    {
        private const DEFAULT_SAMPLE_RATE = 1.0;
        
        private Client $client;
    
        public function __construct(string $host, int $port, string $namespace)
        {
            $connection = new UdpSocket($host, $port);
            $this->client = new Client($connection, $namespace);
        }
    
        public function increment(string $key, ?float $sampleRate = null, ?array $tags = null): void
        {
            $this->client->increment($key, $sampleRate ?? self::DEFAULT_SAMPLE_RATE, $tags ?? []);
        }
    }
    ```
8. В файле `config/services.yaml` добавляем описание сервиса statsd API-клиента
    ```yaml
    App\Client\StatsdAPIClient:
        arguments: 
            - graphite
            - 8125
            - my_app
    ```
9. В классе `App\Controller\Api\SaveUser\v5\SaveUserManager`
    1. Добавляем инъекцию `StatsdAPIClient`
        ```php
        private StatsdAPIClient $statsdAPIClient;
    
        public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, LoggerInterface $logger, StatsdAPIClient $statsdAPIClient)
        {
            $this->entityManager = $entityManager;
            $this->serializer = $serializer;
            $this->logger = $logger;
            $this->statsdAPIClient = $statsdAPIClient;
        }
        ```
    2. В начале метода `saveUser` инкрементируем счётчик
        ```php
        $this->statsdAPIClient->increment('save_user_v5_attempt');
        ```
10. Выполняем несколько раз запрос Add user v5 из Postman-коллекции v5 и проверяем, что в Graphite появляются события
11. Настраиваем график в Grafana
     1. добавляем в Data source с типом Graphite и адресом graphite:80
     2. добавляем новый Dashboard 
     3. на дашборде добавляем панель с запросом в Graphite счётчика `stats_counts.my_app.save_user_v5_attempt`
     4. видим график с запросами
12. Выполняем ещё несколько раз запрос Add user v5 из Postman-коллекции v5 и проверяем, что в Grafana обновились данные
