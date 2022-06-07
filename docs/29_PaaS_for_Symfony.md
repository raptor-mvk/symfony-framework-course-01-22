# PaaS-решения для Symfony

## Создаём пользователя
1. Регистрируемся на https://amazon.com
2. Логинимся и заходим в службу IAM
3. Создаём нового пользователя только для программного доступа
4. Создаём группу с доступом AdministratorAccess-AWSElasticBeanstalk и AdministratorAccess и добавляем в неё
   пользователя
5. Скачиваем файл с ключами

## Устанавливаем Elastic Beanstalk CLI и инициализируем проект

1. Клонируем репозиторий https://github.com/aws/aws-elastic-beanstalk-cli-setup
2. Внимательно читаем readme и выполняем необходимые действия для вашей ОС
    1. Потенциальные проблемы описаны в readme
    2. После установки нужно не забыть экспортировать переменные с путями
3. Проверяем, что консольный интерфейс установлен командой `eb --version`
4. Выполняем в корневой директории проекта команду `eb init`
    1. Выбираем регион
    2. Указываем реквизиты доступа из файла с ключами (Access key ID (не User name!) / Secret access key)
    3. Указываем название приложения
    4. Выбираем платформу PHP 8.0
    5. Разрешаем доступ по SSH

## Настраиваем и деплоим проект

1. В файле `config/packages/doctrine.yaml` удаляем сервисы и драйверы кэша для доктрины
2. Выполняем в корневой директории проекта команду `eb create`
      1. Указываем имя окружения и DNS-имя
      2. Выбираем тип балансировщика (application)
      3. Отказываемся от использования Spot Fleet
3. Добавляем файл `.ebextensions/01-main.config`
    ```yaml
    commands:
        01-composer-update:
            command: "export COMPOSER_HOME=/root"
    
    container_commands:
        02-get-composer:
            command: "sudo php -dmemory_limit=-1 /usr/bin/composer.phar install --no-interaction --optimize-autoloader"
        03-clear-cache:
            command: "sudo -u webapp php bin/console cache:clear --env=dev"
    
    option_settings:
      - namespace: aws:elasticbeanstalk:application:environment
        option_name: COMPOSER_HOME
        value: /root
     ```
4. Добавляем файл `.platform/nginx/conf.d/elasticbeanstalk/symfony.conf`
    ```
    location / {
      try_files $uri $uri/ /index.php?$query_string;
    }
    ```
5. Выполняем команду `eb deploy`
6. Выполняем команду `eb open`, видим ошибку 403

## Добавляем healthcheck

1. Добавляем класс `App\Controller\Api\v1\HealthController`
    ```php
    <?php
    
    namespace App\Controller\Api\v1;
    
    use FOS\RestBundle\Controller\AbstractFOSRestController;
    use FOS\RestBundle\Controller\Annotations as Rest;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Response;
    
    #[Rest\Route(path: 'api/v1/health')]
    class HealthController extends AbstractFOSRestController
    {
        #[Rest\Get('/check')]
        public function checkAction(): Response
        {
            return new JsonResponse(['success' => true]);
        }
    }
    ```
2. В файле `config/packages/security.yaml` в секцию `firewalls` добавляем новый элемент
    ```yaml
    health:
        pattern: ^/api/v1/health
        security: false
    token:
        pattern: ^/api/v1/token
        security: false
    create_user:
        pattern: ^/api/v4/save-user
        security: false
    ```
3. Выполняем команду `eb deploy`
4. Заходим в консоль Elastic Beanstalk и исправляем параметры проекта:
     1. В разделе Configuration -> Software устанавливаем Document root = /public
     2. В разделе Configuration -> Load balancer -> Processes устанавливаем Health check path = /api/v1/health/check 
5. Проверяем, что Health сменился на Ok, и что теперь ошибка при входе в приложение внятная
6. Выполняем запрос Get token из Postman-коллекции v11, заменив хост, получаем ошибку доступа к БД

## Добавляем RDS

1. Заходим в консоль Elastic Beanstalk
     1. В разделе Configuration -> Database добавляем БД с параметрами
         - Engine = postgres
         - Engine version = 12.10
         - Username = twitterUser
         - Password = 0ZRa4pVHdT0mRAMeLEIU
2. Заходим в консоль RDS
     1. Выбираем наш инстанс в разделе Databases
     2. В блоке полей Security group rules выбираем верхнюю группу и в Inbound rules добавляем правило с параметрами
         - Type = PostgreSQL
         - Source 0.0.0.0/0
3. Исправляем параметры доступа в файле `.env` (HOST - Endpoint RDS в AWS)
    ```shell
    DATABASE_URL="postgresql://twitterUser:0ZRa4pVHdT0mRAMeLEIU@HOST:5432/ebdb?serverVersion=12&charset=utf8"
    ```
4. Исправляем файл `.ebextensions/01-main.config`
    ```yaml
    commands:
        01-composer-update:
            command: "export COMPOSER_HOME=/root"
    
    container_commands:
        02-get-composer:
            command: "sudo -u webapp /usr/bin/composer.phar install --no-interaction --optimize-autoloader"
        03-migrate:
            command: "sudo -u webapp php bin/console doctrine:migration:migrate --env=dev"
        04-clear-cache:
            command: "sudo -u webapp php bin/console cache:clear --env=dev"
    
    option_settings:
      - namespace: aws:elasticbeanstalk:application:environment
        option_name: COMPOSER_HOME
        value: /root
    ```
5. Исправляем файл `config/packages/messenger.yaml`
    ```yaml
    framework:
        messenger:
            # reset services after consuming messages
            reset_on_message: true
    
            # Uncomment this (and the failed transport below) to send failed messages to this transport for later handling.
            # failure_transport: failed
    
            buses:
                messenger.bus.default:
    
            transports:
                sync: 'sync://'
    
            routing:
                App\DTO\AddFollowersDTO: sync
                FeedBundle\DTO\SendNotificationDTO: sync
                FeedBundle\DTO\SendNotificationAsyncDTO: sync
    ```
6. Выполняем команду `eb deploy`
7. Выполняем запрос Get token из Postman-коллекции v11, заменив хост, получаем ошибку реквизитов
8. Выполняем запрос Add user v4 из Postman-коллекции v11, заменив хост
9. Ещё раз выполняем запрос Get token из Postman-коллекции v11, заменив хост, видим токен


