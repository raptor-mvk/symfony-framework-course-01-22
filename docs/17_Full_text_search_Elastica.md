# Полнотекстовый поиск, Elastica

## Elasticsearch и Kibana для логов

1. Добавляем сервисы `elasticsearch` и `kibana` в `docker-compose.yml`
    ```yaml
    elasticsearch:
        image: docker.elastic.co/elasticsearch/elasticsearch:7.9.2
        container_name: 'elasticsearch'
        environment:
          - cluster.name=docker-cluster
          - bootstrap.memory_lock=true
          - discovery.type=single-node
          - "ES_JAVA_OPTS=-Xms512m -Xmx512m"
        ulimits:
          memlock:
            soft: -1
            hard: -1
        ports:
          - 9200:9200
          - 9300:9300

    kibana:
        image: docker.elastic.co/kibana/kibana:7.9.2
        container_name: 'kibana'
        depends_on:
          - elasticsearch
        ports:
          - 5601:5601
    ```
2. Запускаем контейнеры командой `docker-compose up -d`
3. Заходим в контейнер командой `docker exec -it php sh`. Дальнейшие команды выполняются из контейнера
4. Устанавливаем пакет `symfony/http-client`
5. В файле `config/packages/dev/monolog.yaml`
    1. исправляем поле `channels:`
        ```yaml
        channels:
            - deprecation # Deprecations are logged in the dedicated "deprecation" channel when it exists
            - elasticsearch       
        ```
    2. Добавляем новый обработчик в секцию `handlers`
        ```yaml
        elasticsearch:
            type: service
            id: Symfony\Bridge\Monolog\Handler\ElasticsearchLogstashHandler
            channels: elasticsearch
        ```
    3. Добавляем секцию `services`:
        ```yaml
        services:
            Psr\Log\NullLogger:
                class: Psr\Log\NullLogger
        
            http_client_without_logs:
                class: Symfony\Component\HttpClient\CurlHttpClient
                calls:
                    - [setLogger, ['@Psr\Log\NullLogger']]
        
            Symfony\Bridge\Monolog\Handler\ElasticsearchLogstashHandler:
                arguments:
                    - 'http://elasticsearch:9200'
                    - 'monolog'
                    - '@http_client_without_logs'
        ```
6. В классе `App\Controller\Api\SaveUser\v5\SaveUserManager`
    1. переименовываем параметр конструктора `$logger` в `$elasticsearchLogger`
    2. исправляем метод `saveUser`
        ```php
        public function saveUser(SaveUserDTO $saveUserDTO): UserIsSavedDTO
        {
            $user = new User();
            $user->setLogin($saveUserDTO->login);
            $user->setPassword($saveUserDTO->password);
            $user->setRoles($saveUserDTO->roles);
            $user->setAge($saveUserDTO->age);
            $user->setIsActive($saveUserDTO->isActive);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->logger->info("User #{$user->getId()} is saved: [{$user->getLogin()}, {$user->getAge()} yrs]");
    
            $result = new UserIsSavedDTO();
            $context = (new SerializationContext())->setGroups(['user1', 'user2']);
            $result->loadFromJsonString($this->serializer->serialize($user, 'json', $context));
    
            return $result;
        }
        ```
7. Выполняем запрос Add user v5 из Postman-коллекции v9
8. Заходим в Kibana `http://localhost:5601`.
9. Заходим в Stack Management -> Index Patterns
10. Создаём index pattern на базе индекса `monolog`, переходим в `Discover`, видим наше сообщение

## Индексация данных БД в Elasticsearch

### Установка elastica-bundle

1. Устанавливаем пакет `friendsofsymfony/elastica-bundle`
2. В файле `.env` исправляем DSN для ElasticSearch
    ```shell script
    ELASTICSEARCH_URL=http://elasticsearch:9200/
    ```
3. Выполняем запрос Add followers из Postman-коллекции v9, чтобы получить побольше записей в БД
4. В файле `config/packages/fos_elastica.yaml` в секции `indexes` удаляем `app` и добавляем секцию `user`:
    ```yaml
    user:
        persistence:
            driver: orm
            model: App\Entity\User
        properties:
            login: ~
            age: ~
            phone: ~
            email: ~
            preferred: ~
    ```
5. Заполняем индекс командой `php bin/console fos:elastica:populate`
6. В Kibana заходим в Stack Management -> Index patterns и создаём index pattern на базе индекса `user`
7. Переходим в `Discover`, видим наши данные в новом шаблоне

### Вложенные документы

1. Выполняем запрос Post tweet из Postman-коллекции v9, чтобы получить запись в таблице `tweet`
2. Добавим индекс с составными полями в `config/packages/fos_elastica.yaml` в секцию `indexes`
    ```yaml
    tweet:
        persistence:
            driver: orm
            model: App\Entity\Tweet
            provider: ~
            finder: ~
        properties:
            author:
                type: nested
                properties:
                    name:
                        property_path: login
                    age: ~
                    phone: ~
                    email: ~
                    preferred: ~
            text: ~
    ```
3. В контейнере ещё раз заполняем индекс командой `php bin/console fos:elastica:populate`
4. В Kibana заходим в Stack Management -> Index patterns и создаём index pattern на базе индекса `tweet`
5. Переходим в `Discover`, видим наши данные в новом шаблоне

### Сериализация вместо описания схемы

1. В файле `config/packages/fos_elastica.yaml`
    1. Включаем сериализацию
        ```yaml
        serializer:
            serializer: jms_serializer
        ```
    2. Для каждого индекса (`user`, `tweet`) удаляем секцию `properties` и добавляем секцию `serializer`
        ```yaml
        serializer:
            groups: [elastica]
        ```
2. В классе `App\Entity\User` добавляем атрибуты для полей `login`, `phone`, `email` и `preferred`
    ```php
    #[ORM\Column(type: 'string', length: 32, unique: true, nullable: false)]
    #[JMS\Groups(['user1','elastica'])]
    private string $login;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[JMS\Type('int')]
    #[JMS\Groups(['user1', 'elastica'])]
    private int $age;

    #[ORM\Column(type: 'string', length: 11, nullable: true)]
    #[JMS\Type('string')]
    #[JMS\Groups(['elastica'])]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[JMS\Type('string')]
    #[JMS\Groups(['elastica'])]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[JMS\Type('string')]
    #[JMS\Groups(['elastica'])]
    private ?string $preferred = null;
    ```
3. В классе `App\Entity\Tweet` добавляем атрибуты для полей `author` и `text`
    ```php
    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'tweets')]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
    #[JMS\Groups(['elastica'])]
    private User $author;

    #[ORM\Column(type: 'string', length: 140, nullable: false)]
    #[JMS\Groups(['elastica'])]
    private string $text;
    ```
4. Ещё раз заполняем индекс командой `php bin/console fos:elastica:populate`, получаем ошибку
5. В файле `config/packages/dev/jms_serializer.yml` отключаем опцию `JSON_PRETTY_PRINT`
6. В контейнере ещё раз заполняем индекс командой `php bin/console fos:elastica:populate`, на этот раз ошибки нет
7. Проверяем в Kibana, что в индексах данные присутствуют

##  Отключаем автообновление индекса

1. Выполняем запрос Add user v5 из Postman-коллекции v9
2. Проверяем в Kibana, что новая запись появилась в индексе
3. Отключаем listener для insert в файле `config/fos_elastica.yaml` в секции `indexes.user.persistence` добавляем
   подсекцию `listener`
    ```yaml
    listener:
        insert: false
        update: true
        delete: true
    ```
4. Выполняем ещё один запрос Add user v5 из Postman-коллекции v9
5. Проверяем в Kibana, что новая запись не появилась в индексе, хотя в БД она есть

### Поиск по индексу

1. В классе `App\Manager\UserManager`
    1. Добавляем инъекцию `PaginatedFinderInterface`
        ```php
        private PaginatedFinderInterface $finder;
   
        public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory, UserPasswordHasherInterface $userPasswordHasher, PaginatedFinderInterface $finder)
        {
            $this->entityManager = $entityManager;
            $this->formFactory = $formFactory;
            $this->userPasswordHasher = $userPasswordHasher;
            $this->finder = $finder;
        }
        ```
    2. Добавляем метод `findUserByQuery`
        ```php
        /**
         * @return User[]
         */
        public function findUserByQuery(string $query, int $perPage, int $page): array
        {
            $paginatedResult = $this->finder->findPaginated($query);
            $paginatedResult->setMaxPerPage($perPage);
            $paginatedResult->setCurrentPage($page);
            $result = [];
            array_push($result, ...$paginatedResult->getCurrentPageResults());
    
            return $result;
        }
        ```
2. В файле `config/services.yaml` добавляем новый сервис:
    ```yaml
    App\Manager\UserManager:
        arguments:
            $finder: '@fos_elastica.finder.user'
    ```
3. Добавляем класс `App\Controller\Api\GetUsersByQuery\v1\Controller`
    ```php
    <?php
    
    namespace App\Controller\Api\GetUsersByQuery\v1;
    
    use App\Manager\UserManager;
    use FOS\RestBundle\Controller\AbstractFOSRestController;
    use FOS\RestBundle\Controller\Annotations as Rest;
    use FOS\RestBundle\Controller\Annotations\QueryParam;
    use FOS\RestBundle\View\ViewHandlerInterface;
    use Symfony\Component\HttpFoundation\Response;
    
    class Controller extends AbstractFOSRestController
    {
        private UserManager $userManager;
    
        public function __construct(UserManager $userManager)
        {
            $this->userManager = $userManager;
        }
    
        #[Rest\Get('/api/v1/get-users-by-query')]
        #[QueryParam(name: 'query')]
        #[QueryParam(name: 'perPage', requirements: '\d+')]
        #[QueryParam(name: 'page', requirements: '\d+')]
        public function getUsersByQueryAction(string $query, int $perPage, int $page): Response
        {
            return $this->handleView($this->view($this->userManager->findUserByQuery($query, $perPage, $page), 200));
        }
    }
    ```
4. Выполняем несколько запросов Get users by query из Postman-коллекции v9 с данными из разных полей разных
   пользователей 

##№ Добавляем агрегацию

1. В классе `App\Manager\UserManager` добавляем метод `findUserWithAggregation`
    ```php
    /**
     * @return User[]
     */
    public function findUserWithAggregation(string $field): array
    {
        $aggregation = new Terms('notifications');
        $aggregation->setField($field);
        $query = new Query();
        $query->addAggregation($aggregation);
        $paginatedResult = $this->finder->findPaginated($query);
        /** @var FantaPaginatorAdapter $adapter */
        $adapter = $paginatedResult->getAdapter();

        return $adapter->getAggregations();
    }
    ```
2. Добавляем класс `App\Controller\Api\GetUsersWithAggregation\v1\Controller`
    ```php
    <?php
    
    namespace App\Controller\Api\GetUsersWithAggregation\v1;
    
    use App\Manager\UserManager;
    use FOS\RestBundle\Controller\AbstractFOSRestController;
    use FOS\RestBundle\Controller\Annotations as Rest;
    use FOS\RestBundle\Controller\Annotations\QueryParam;
    use Symfony\Component\HttpFoundation\Response;
    
    class Controller extends AbstractFOSRestController
    {
        private UserManager $userManager;
    
        public function __construct(UserManager $userManager)
        {
            $this->userManager = $userManager;
        }
    
        #[Rest\Get('/api/v1/get-users-with-aggregation')]
        #[QueryParam(name: 'field')]
        public function getUsersWithAggregationAction(string $field): Response
        {
            return $this->handleView($this->view($this->userManager->findUserWithAggregation($field), 200));
        }
    }
    ```
3. Выполняем запрос Get users with aggregation из Postman-коллекции v9, получаем ошибку
4. Добавляем в `config/packages/fos_elastica.yaml` в секцию `indexes.user` секцию `properties`
    ```yaml
    properties:
        preferred:
            fielddata: true
    ```
5. В контейнере заполняем индекс командой `php bin/console fos:elastica:populate`
6. Ещё раз выполняем запрос Get users with aggregation из Postman-коллекции v9, получаем агрегацию по полю `preferred`

### Совмещаем агрегацию и поиск

1. В классе `App\Manager\UserManager` добавляем метод `findUserByQueryWithAggregation`
    ```php
    /**
     * @return User[]
     */
    public function findUserByQueryWithAggregation(string $queryString, string $field): array
    {
        $aggregation = new Terms('notifications');
        $aggregation->setField($field);
        $query = new Query(new QueryString($queryString));
        $query->addAggregation($aggregation);
        $paginatedResult = $this->finder->findPaginated($query);
        /** @var FantaPaginatorAdapter $adapter */
        $adapter = $paginatedResult->getAdapter();
    
        return $adapter->getAggregations();
    }
    ```
2. Добавляем класс `App\Controller\Api\GetUsersByQueryWithAggregation\v1\Controller`
    ```php
    <?php
    
    namespace App\Controller\Api\GetUsersByQueryWithAggregation\v1;
    
    use App\Manager\UserManager;
    use FOS\RestBundle\Controller\AbstractFOSRestController;
    use FOS\RestBundle\Controller\Annotations as Rest;
    use FOS\RestBundle\Controller\Annotations\QueryParam;
    use Symfony\Component\HttpFoundation\Response;
    
    class Controller extends AbstractFOSRestController
    {
        private UserManager $userManager;
    
        public function __construct(UserManager $userManager)
        {
            $this->userManager = $userManager;
        }
    
        #[Rest\Get('/api/v1/get-users-by-query-with-aggregation')]
        #[QueryParam(name: 'query')]
        #[QueryParam(name: 'field')]
        public function getUsersByQueryWithAggregationAction(string $query, string $field): Response
        {
            return $this->handleView($this->view($this->userManager->findUserByQueryWithAggregation($query, $field), 200));
        }
    }
    ```
3. Выполняем запрос Get users by query with aggregation из Postman-коллекции v9, получаем агрегацию найденных
   пользователей по полю `preferred`
