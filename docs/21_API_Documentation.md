# NelmioApiDocBundle и документация API

Запускаем контейнеры командой `docker-compose up -d`

## Установка NelmioApiDocBundle

1. Заходим в контейнер командой `docker exec -it php sh`. Дальнейшие команды выполняем из контейнера
2. Устанавливаем пакет `nelmio/api-doc-bundle`
3. Заходим по адресу `http://localhost:7777/api/doc.json`, видим JSON-описание нашего API
4. Заходим по адресу `http://localhost:7777/api/doc`, видим ошибку

## Добавляем роутинг на UI 

1. Добавляем в файл `config/routes.yaml`
    ```yaml
    app.swagger_ui:
      path: /api/doc
      methods: GET
      defaults: { _controller: nelmio_api_doc.controller.swagger_ui }
    ```
2. Ещё раз заходим по адресу `http://localhost:7777/api/doc`, видим описание API

## Добавляем авторизацию 

1. В файле `config/packages/security.yaml` в секцию `access_control` добавляем строку
    ```
    - { path: ^/api/doc, roles: ROLE_ADMIN }
    ```
2. Ещё раз заходим по адресу `http://localhost:7777/api/doc`, видим требование авторизоваться

##  Прячем служебный endpoint

1. Исправляем в файле `config/packages/nelmio_api_doc.yaml` секцию `areas.path_patterns`
    ```yaml
    path_patterns:
        - ^/api(?!/doc(.json)?$)
    ```
2. Ещё раз заходим по адресу `http://localhost:7777/api/doc`, видим, что служебный endpoint не отображается

## Выделяем зону

1. Исправляем в файле `config/packages/nelmio_api_doc.yaml` секцию `areas`
    ```yaml
    feed:
      path_patterns:
        - ^/api/v1/get-feed
    default:
      path_patterns:
        - ^/api(?!/doc(.json)?$)
    ```
2. В файл `config/routes.yaml` добавляем
    ```yaml
    app.swagger_ui_areas:
      path: /api/doc/{area}
      methods: GET
      defaults: { _controller: nelmio_api_doc.controller.swagger_ui }
    ```
3. Заходим по адресу `http://localhost:7777/api/doc/feed`, видим выделенный endpoint

## Добавляем описывающие аннотации

1. В классе `App\Controller\Api\GetFeed\v1\Controller`
    1. Добавляем импорт
        ```php
        use OpenApi\Annotations as OA;
        ```
    2. Убираем атрибуты с описанием параметров
    3. Добавляем аннотации к методу `getFeedAction`
        ```php
        /**
         * @OA\Tag(name="Лента")
         * @Rest\QueryParam(name="userId", requirements="\d+")
         * @Rest\QueryParam(name="count", requirements="\d+")
         * @OA\Parameter(name="userId", description="ID пользователя", in="query", example="135")
         * @OA\Parameter(name="count", description="ID пользователя", in="query", example="135")
         */
        ```
2. Заходим по адресу `http://localhost:7777/api/doc`, видим, что endpoint выделен в отдельный тэг и обновлённое
описание параметров

## Генерируем API-клиент

1. Выполняем команду `php bin/console nelmio:apidoc:dump --format=yaml >apidoc.yaml`, получаем соответствующий файл
с описанием API
2. Добавляем новый сервис в `docker-compose.yml`
    ```yaml
    openapi-generator:
      image: openapitools/openapi-generator-cli:latest
      volumes:
        - ./:/local
      command: ["generate", "-i", "/local/apidoc.yaml", "-g", "php", "-o", "/local/api-client"]
    ```
3. Выходим из контейнера и выполняем команду `docker-compose up openapi-generator` и видим ошибки

## Ограничиваем генерируемый клиент

1. Исправляем в файле `config/packages/nelmio_api_doc.yaml` секцию `areas`
    ```yaml
    default:
      path_patterns:
        - ^/api/v1/get-feed
    ```
2. В классе `App\Controller\Api\GetFeed\v1\Controller` исправляем аннотации к методу `getFeedAction`
    ```php
    /**
     * @Rest\QueryParam(name="userId", requirements="\d+")
     * @Rest\QueryParam(name="count", requirements="\d+")
     * @OA\Get(
     *     operationId="getFeed",
     *     tags={"Лента"},
     *     @OA\Parameter(name="userId", in="query", description="ID пользователя", example="135"),
     *     @OA\Parameter(name="count", in="query", description="Количество твитов в ленте", example="5")
     * )
     */
    ```
3. Опять заходим в контейнер командой `docker exec -it php sh` и выполняем команду
`php bin/console nelmio:apidoc:dump --format=yaml --area=feed >apidoc.yaml`
     ```shell script
     php bin/console nelmio:apidoc:dump >apidoc.json
     vendor/bin/php-openapi convert --write-yaml apidoc.json apidoc.yaml
     ```
3. Выходим из контейнера и выполняем команду `docker-compose up openapi-generator` и видим, что клиент сгенерировался
 
## Добавляем DTO в аннотации

1. Исправляем в файле `config/packages/nelmio_api_doc.yaml` секцию `areas`
    ```yaml
    default:
      path_patterns:
        - ^/api(?!/doc(.json)?$)
    ```
2. В классе `App\Controller\Api\SaveUser\v5\Controller`
    1. Добавляем импорты
        ```php
        use App\Controller\Api\SaveUser\v5\Output\UserIsSavedDTO;
        use OpenApi\Annotations as OA;
        use Nelmio\ApiDocBundle\Annotation\Model;
        ```
    2. Добавляем аннотации к методу `addUserAction`
        ```php
        /**
         * @OA\Post(
         *     operationId="addUser",
         *     tags={"Пользователи"},
         *     @OA\RequestBody(
         *         description="Input data format",
         *         @OA\JsonContent(ref=@Model(type=SaveUserDTO::class))
         *     ),
         *     @OA\Response(
         *         response=200,
         *         description="Success",
         *         @OA\JsonContent(ref=@Model(type=UserIsSavedDTO::class))
         *     )
         * )
         */
        ```
3. Заходим по адресу `http://localhost:7777/api/doc`, видим описания DTO и в запросе `/api/v5/save-user` ссылки на
них, но при этом описание `SaveUserDTO` не полное (нет ролей)

## Дополняем аннотации в DTO

1. Исправляем класс `App\Controller\Api\SaveUser\v5\Input\SaveUserDTO`
    ```php
    <?php
    
    namespace App\Controller\Api\SaveUser\v5\Input;
    
    use App\Entity\Traits\SafeLoadFieldsTrait;
    use Symfony\Component\Validator\Constraints as Assert;
    use OpenApi\Annotations as OA;
    
    class SaveUserDTO
    {
        use SafeLoadFieldsTrait;
    
        /**
         * @Assert\NotBlank()
         * @Assert\Type("string")
         * @Assert\Length(max=32)
         * @OA\Property(property="login", example="my_user")
         */
        public string $login;
    
        /**
         * @Assert\NotBlank()
         * @Assert\Type("string")
         * @Assert\Length(max=32)
         * @OA\Property(property="password", example="my_pass")
         */
        public string $password;
    
        /**
         * @Assert\NotBlank()
         * @Assert\Type("array")
         * @OA\Property(property="roles", type="array", @OA\Items(type="string", example="ROLE_USER"))
         */
        public array $roles;
    
        /**
         * @Assert\NotBlank()
         * @Assert\Type("numeric")
         */
        public int $age;
    
        /**
         * @Assert\NotBlank()
         * @Assert\Type("bool")
         * @OA\Property(property="isActive")
         */
        public bool $isActive;
    
        public function getSafeFields(): array
        {
            return ['login', 'password', 'roles', 'age', 'isActive'];
        }
    }
    ```
2. Заходим по адресу `http://localhost:7777/api/doc`, видим исправленные описания DTO, нажимаем `Try it out`, шлём
   запрос и видим сохранённую запись в БД
