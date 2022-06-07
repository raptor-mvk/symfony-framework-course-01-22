# API Platform: погружение

Запускаем контейнеры командой `docker-compose up -d`

## Добавляем создание твита через API API Platform 

1. Подключаемся в контейнер командой `docker exec -it php sh`. Дальнейшие команды выполняем из контейнера
2. В классе `App\Entity\Tweet` добавляем атрибут `#[ApiResource]` к классу
3. Выполняем запрос Post Tweet API Platform из Postman-коллекции v11. Видим, что твит записан в БД, но материализация в
   таблицу `Feed` сама не заработает

## Добавляем асинхронную материализацию

1. В классе `App\Entity\Tweet` исправляем атрибут класса `#[ApiResource]`
    ```php
    #[ApiResource(collectionOperations: ['post' => ['status' => 202]], itemOperations: [], output: false)]
    ```
2. Проверяем по адресу `http://localhost:7777/api-platform`, что для твита остался только запрос на создание
3. Добавляем класс `App\Persister\AsyncMessagePersisterDecorator`
    ```php
    <?php
    
    namespace App\Persister;
    
    use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
    use App\Entity\Tweet;
    use App\Service\AsyncService;
    
    class AsyncMessagePersisterDecorator implements ContextAwareDataPersisterInterface
    {
        private ContextAwareDataPersisterInterface $decoratedPersister;
    
        private AsyncService $asyncService;
    
        public function __construct(ContextAwareDataPersisterInterface $decoratedPersister, AsyncService $asyncService)
        {
            $this->decoratedPersister = $decoratedPersister;
            $this->asyncService = $asyncService;
        }
    
        public function supports($data, array $context = []): bool
        {
            return $this->decoratedPersister->supports($data, $context);
        }
    
        public function persist($data, array $context = [])
        {
            $result = $this->decoratedPersister->persist($data, $context);

            if ($result instanceof Tweet) {
                $this->asyncService->publishToExchange(AsyncService::PUBLISH_TWEET, $result->toAMPQMessage());
            }
        }
    
        public function remove($data, array $context = [])
        {
            return $this->decoratedPersister->remove($data, $context);
        }
    }
    ```
4. В файл `config/services.yaml` добавляем новый декоратор
    ```yaml
    App\Persister\AsyncMessagePersisterDecorator:
        decorates: 'api_platform.doctrine.orm.data_persister'
    ```
5. Ещё раз выполняем запрос Post Tweet API Platform из Postman-коллекции v11. Видим, что сообщение материализовалось в
   ленты

## Добавляем работу с DTO

1. Добавляем класс `App\DTO\UserDTO`
    ```
    <?php
    
    namespace App\DTO;
    
    class UserDTO
    {
        public string $login;

        public string $email;

        public string $phone;

        public array $followers;

        public array $followed;
    }
    ```
2. В классе `App\Entity\User`
    1. добавляем новое поле и геттер
        ```php
        #[ORM\OneToMany(mappedBy: 'follower', targetEntity: 'Subscription')]
        private Collection $followed;
    
        /**
         * @return Subscription[]
         */
        public function getFollowed(): array
        {
            return $this->followed->getValues();
        }
        ```
    2. исправляем конструктор
        ```php
        public function __construct()
        {
            $this->tweets = new ArrayCollection();
            $this->authors = new ArrayCollection();
            $this->followers = new ArrayCollection();
            $this->followed = new ArrayCollection();
            $this->subscriptionAuthors = new ArrayCollection();
            $this->subscriptionFollowers = new ArrayCollection();
        }
        ```
    3. исправляем атрибут `#[ApiResource]` класса
        ```php
        #[ApiResource(graphql: ['itemQuery' => ['item_query' => UserResolver::class, 'args' => ['id' => ['type' => 'Int'], 'login' => ['type' => 'String']], 'read' => false], 'collectionQuery' => ['collection_query' => UserCollectionResolver::class]], output: UserDTO::class)]
        ```
3. Добавляем класс `App\Transformer\UserTransformer`
    ```php
    <?php
    
    namespace App\Transformer;
    
    use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
    use App\DTO\UserDTO;
    use App\Entity\Subscription;
    use App\Entity\User;
    
    class UserTransformer implements DataTransformerInterface
    {
        /**
         * @param User $user
         */
        public function transform($user, string $to, array $context = []): UserDTO
        {
            /** @var User $user */
            $userDTO = new UserDTO();
            $userDTO->login = $user->getLogin();
            $userDTO->email = $user->getEmail();
            $userDTO->phone = $user->getPhone();
            $userDTO->followers = array_map(
                static function (Subscription $subscription): string {
                    return $subscription->getFollower()->getLogin();
                },
                $user->getSubscriptionFollowers()
            );
            $userDTO->followed = array_map(
                static function (Subscription $subscription): string {
                    return $subscription->getAuthor()->getLogin();
                },
                $user->getFollowed()
            );
    
            return $userDTO;
        }
    
        /**
         * @param User $user
         */
        public function supportsTransformation($user, string $to, array $context = []): bool
        {
            return UserDTO::class === $to && ($user instanceof User);
        }
    }
    ```
4. Выполняем команду `php bin/console doctrine:cache:clear-metadata`   
5. Выполняем запрос Get user API Platform из Postman-коллекции v11. Видим, что трансформер отрабатывает

## Пробуем получить JSON Schema

1. Добавляем класс `App\Controller\Api\v1\JSONSchemaController`
    ```php
    <?php
    
    namespace App\Controller\Api\v1;
    
    use ApiPlatform\Core\Hydra\JsonSchema\SchemaFactory;
    use FOS\RestBundle\Controller\AbstractFOSRestController;
    use FOS\RestBundle\Controller\Annotations\QueryParam;
    use FOS\RestBundle\View\View;
    use FOS\RestBundle\Controller\Annotations as Rest;
    
    #[Rest\Route(path: 'api/v1/json-schema')]
    class JSONSchemaController extends AbstractFOSRestController
    {
        private SchemaFactory $jsonSchemaFactory;
    
        public function __construct(SchemaFactory $jsonSchemaFactory)
        {
            $this->jsonSchemaFactory = $jsonSchemaFactory;
        }
    
        #[Rest\Get('')]
        #[QueryParam(name:'resource')]
        public function getJSONSchemaAction(string $resource): View
        {
            $className = 'App\\Entity\\'.ucfirst($resource);
            $schema = $this->jsonSchemaFactory->buildSchema($className);
            $arraySchema = json_decode(json_encode($schema), true);
            return View::create($arraySchema);
        }
    }
    ```
2. В файле `config/services.yaml` добавляем новый сервис
    ```yaml
    App\Controller\Api\v1\JSONSchemaController:
        arguments:
            - '@api_platform.json_schema.schema_factory'
    ```
3. Выполняем запрос Get JSON Schema из Postman-коллекции v11
4. Заходим по адресу `https://rjsf-team.github.io/react-jsonschema-form/` и вставляем в поле JSONSchema результат
запроса, видим сгенерированную динамическую форму

## Убираем лишние поля из JSON Schema

1. В классе `App\Controller\Api\v1\JSONSchemaController` исправляем метод `getJSONSchemaAction`
    ```php
    #[Rest\Get('')]
    #[QueryParam(name:'resource')]
    public function getJSONSchemaAction(string $resource): View
    {
        $className = 'App\\Entity\\'.ucfirst($resource);
        $schema = $this->jsonSchemaFactory->buildSchema($className);
        $arraySchema = json_decode(json_encode($schema), true);
        foreach ($arraySchema['definitions'] as $key => $value) {
            $entityKey = $key;
            break;
        }
        $unnecessaryPropertyKeys = array_filter(
            array_keys($arraySchema['definitions'][$entityKey]['properties']),
            static function (string $key) {
                return $key[0] === '@';
            }
        );
        foreach ($unnecessaryPropertyKeys as $key) {
            unset($arraySchema['definitions'][$entityKey]['properties'][$key]);
        }

        return View::create($arraySchema);
    }
    ```
2. Ещё раз выполняем запрос Get JSON Schema из Postman-коллекции v11. Вставляем в поле JSONSchema результат запроса,
   видим, что лишние поля из формы ушли

## Добавляем аутентификацию с помощью JWT

1. В файле `config/packages/security.yaml`
    1. в секцию `providers` добавляем новый провайдер
        ```yaml
        app_user_provider:
            entity:
                class: App\Entity\User
                property: login
        ```
    2. изменяем секцию `firewalls.main`
        ```yaml
        main:
            stateless: true
            provider: app_user_provider
            json_login:
                check_path: /authentication_token
                username_path: login
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
            guard:
                authenticators:
                    - lexik_jwt_authentication.jwt_token_authenticator
        ```
    3. изменяем секцию `access_control`
        ```yaml
        - { path: ^/authentication_token, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/, roles: IS_AUTHENTICATED_FULLY }
        ```
2. В файле `config/packages/fos_rest.yaml` добавляем в секцию `format_listener.rules`
    ```yaml
    - { path: ^/authentication_token, prefer_extension: true, fallback_format: json, priorities: [ json, html ] }
    ```
3. В файле `config/routes.yaml` добавляем endpoint для получения токена
    ```yaml
    authentication_token:
      path: /authentication_token
      methods: ['POST']
    ```
4. Выполняем запрос Get token API Platform из Postman-коллекции v11.
5. Выполняем запрос Get JSON Schema из Postman-коллекции v11, видим ошибку 401
6. Подставляем токен в заголовок запроса Get JSON Schema из Postman-коллекции v11 и проверяем, что всё работает
