# REST-приложения и FOSRestBundle

Запускаем контейнеры командой `docker-compose up -d`

## Устанавливам rest-bundle и добавляем контроллер

1. Заходим в контейнер `php` командой `docker exec -it php sh`. Дальнейшие команды выполняются из контейнера
2. Устанавливаем пакеты `jms/serializer-bundle` и `friendsofsymfony/rest-bundle`
3. В файле `config/packages/fos_rest.yaml` раскомментируем строки
    ```yaml
    fos_rest:
        view:
            view_response_listener:  true

        format_listener:
            rules:
                - { path: ^/api, prefer_extension: true, fallback_format: json, priorities: [ json, html ] }
    ```
4. Добавляем класс `Controller\Api\GetUsers\v4\Controller`
    ```php
    <?php
    
    namespace App\Controller\Api\GetUsers\v4;
    
    use App\Manager\UserManager;
    use FOS\RestBundle\Controller\AbstractFOSRestController;
    use FOS\RestBundle\Controller\Annotations as Rest;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    
    class Controller extends AbstractFOSRestController
    {
        private UserManager $userManager;
    
        public function __construct(UserManager $userManager)
        {
            $this->userManager = $userManager;
        }
    
        #[Rest\Get(path: '/api/v4/get-users')]
        public function getUsersAction(Request $request): Response
        {
            $perPage = $request->request->get('perPage');
            $page = $request->request->get('page');
            $users = $this->userManager->getUsers($page ?? 0, $perPage ?? 20);
            $code = empty($users) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK;
    
            return $this->handleView($this->view(['users' => $users], $code));
        }
    }
    ```
5. Выполняем запрос Get user list v4 из Postman-коллекции v4, видим, что возвращается список пользователей, хотя мы
   не выполняем явно `toArray` для каждого из них

## Добавляем атрибуты для типов при сериализации

1. В классе `App\Entity\User` исправляем атрибуты для полей `$age` и `$isActive`
   них
    ```php
    #[ORM\Column(type: 'integer', nullable: false)]
    #[JMS\Type('string')]
    private int $age;

    #[ORM\Column(type: 'boolean', nullable: false)]
    #[JMS\Type('int')]
    private bool $isActive;
    ```
2. Выполняем запрос Get user list v4 из Postman-коллекции v4 и видим, что типы данных в сериализованном ответе
   отличаются от типов данных в БД

## Добавляем группу сериализации

1. В классе `App\Entity\User` добавляем атрибуты группы для полей `$login`, `$age` и `$isActive`
    ```php
    #[ORM\Column(type: 'string', length: 32, unique: true, nullable: false)]
    #[JMS\Groups(['user1'])]
    private string $login;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[JMS\Type('string')]
    #[JMS\Groups(['user1'])]
    private int $age;

    #[ORM\Column(type: 'boolean', nullable: false)]
    #[JMS\Type('int')]
    #[JMS\Groups(['user1'])]
    private bool $isActive;
    ```
2. В классе `App\Controller\GetUsers\v4\Controller` исправляем метод `getUsersAction`
    ```php
    #[Rest\Get(path: '/api/v4/get-users')]
    public function getUsersAction(Request $request): Response
    {
        $perPage = $request->request->get('perPage');
        $page = $request->request->get('page');
        $users = $this->userManager->getUsers($page ?? 0, $perPage ?? 20);
        $code = empty($users) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK;
        $context = (new Context())->setGroups(['user1']);
        $view = $this->view(['users' => $users], $code)->setContext($context);

        return $this->handleView($view);
    }
    ```
3. Выполняем запрос Get user list v4 из Postman-коллекции v4 и видим, что отдаются только атрибутированные поля

## Добавляем ещё одну группу сериализации

1. В классе `App\Entity\User` Добавляем атрибут для другой группы сериализации для поля `$id`
    ```php
    #[ORM\Column(name: 'id', type: 'bigint', unique:true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[JMS\Groups(['user2'])]
    private ?int $id = null;
    ```
2. В классе `App\Controller\GetUsers\v4\Controller` в методе `getUsersAction` добавляем в контекст ещё одну группу
     ```php
     $context = (new Context())->setGroups(['user1', 'user2']);
     ```
3. Выполняем запрос Get user list v4 из Postman-коллекции v4 и видим, что в ответ добавилось поле `id`

## Добавляем параметры запроса

1. Добавляем класс `Controller\Api\SaveUser\v4\Controller`
    ```php
    <?php
    
    namespace App\Controller\Api\SaveUser\v4;
    
    use App\Entity\User;
    use App\Manager\UserManager;
    use FOS\RestBundle\Controller\AbstractFOSRestController;
    use FOS\RestBundle\Controller\Annotations as Rest;
    use FOS\RestBundle\Controller\Annotations\RequestParam;
    use Symfony\Component\HttpFoundation\Response;
    use App\DTO\SaveUserDTO;
    
    class Controller extends AbstractFOSRestController
    {
        private UserManager $userManager;
    
        public function __construct(UserManager $userManager)
        {
            $this->userManager = $userManager;
        }
    
        #[Rest\Post(path: '/api/v4/save-user')]
        #[RequestParam(name: 'login')]
        #[RequestParam(name: 'password')]
        #[RequestParam(name: 'roles')]
        #[RequestParam(name: 'age', requirements: '\d+')]
        #[RequestParam(name: 'isActive', requirements: 'true|false')]
        public function saveUserAction(string $login, string $password, string $roles, string $age, string $isActive): Response
        {
            $userDTO = new SaveUserDTO([
                    'login' => $login,
                    'password' => $password,
                    'roles' => json_decode($roles, true, 512, JSON_THROW_ON_ERROR),
                    'age' => (int)$age,
                    'isActive' => $isActive === 'true']
            );
            $userId = $this->userManager->saveUserFromDTO(new User(), $userDTO);
            [$data, $code] = ($userId === null) ? [['success' => false], 400] : [['id' => $userId], 200];
            return $this->handleView($this->view($data, $code));
        }
    }
    ```
2. Выполняем запрос Add user v4 из Postman-коллекции v4, видим ошибку

## Форсируем ParamFetcherListener

1. В файле `config/packages/fos_rest.yaml` добавляем строку
      ```yaml
      param_fetcher_listener:  force
      ```
1. Выполняем запрос Add user v4 из Postman-коллекции v4, видим, что пользователь сохранился в БД

## Добавляем ParamConverter
 
1. Устанавливаем пакет `symfony/options-resolver`
2. В файл `config/services.yaml` исправляем секцию `App\` добавляем строку
    ```yaml
    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'
            - '../src/Controller/Common/*'
    ```
3. Добавляем класс `App\Controller\Common\Error`
    ```php
    <?php
   
    namespace App\Controller\Common;
   
    class Error
    {
        public string $propertyPath;
   
        public string $message;
   
        public function __construct(string $propertyPath, string $message)
        {
            $this->propertyPath = $propertyPath;
            $this->message = $message;
        }
    }
    ```
4. Добавляем класс `App\Controller\Common\ErrorResponse`
    ```php
    <?php
   
    namespace App\Controller\Common;
   
    class ErrorResponse
    {
        public bool $success = false;
   
        /** @var Error[] */
        public array $errors;
   
        public function __construct(Error ...$errors)
        {
            $this->errors = $errors;
        }
    }
    ```
5. Добавляем трейт `App\Controller\Common\ErrorResponseTrait`
    ```php
    <?php
   
    namespace App\Controller\Common;
   
    use FOS\RestBundle\View\View;
    use Symfony\Component\Validator\ConstraintViolationInterface;
    use Symfony\Component\Validator\ConstraintViolationListInterface;
   
    trait ErrorResponseTrait
    {
        private function createValidationErrorResponse(int $code, ConstraintViolationListInterface $validationErrors): View
        {
            $errors = [];
            foreach ($validationErrors as $error) {
                /** @var ConstraintViolationInterface $error */
                $errors[] = new Error($error->getPropertyPath(), $error->getMessage());
            }
            return View::create(new ErrorResponse(...$errors), $code);
        }
    }
    ```
6. Добавляем трейт `App\Entity\Traits\SafeLoadFieldsTrait`
    ```php
    <?php
   
    namespace App\Entity\Traits;
   
    use Symfony\Component\HttpFoundation\Request;
   
    trait SafeLoadFieldsTrait
    {
        abstract public function getSafeFields(): array;
   
        public function loadFromJsonString(string $json): void
        {
            $this->loadFromArray(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
        }
   
        public function loadFromJsonRequest(Request $request): void
        {
            $this->loadFromJsonString($request->getContent());
        }
   
        public function loadFromArray(?array $input): void
        {
            if (empty($input)) {
                return;
            }
            $safeFields = $this->getSafeFields();
   
            foreach ($safeFields as $field) {
                if (array_key_exists($field, $input)) {
                    $this->{$field} = $input[$field];
                }
            }
        }
    }
    ```
7. Добавляем класс `App\Controller\Api\SaveUser\v5\Input\SaveUserDTO`
    ```php
    <?php
   
    namespace App\Controller\Api\SaveUser\v5\Input;
   
    use App\Entity\Traits\SafeLoadFieldsTrait;
    use Symfony\Component\Validator\Constraints as Assert;
   
    class SaveUserDTO
    {
        use SafeLoadFieldsTrait;
   
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(max: 32)]
        public string $login;
    
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(max: 32)]
        public string $password;
    
        #[Assert\NotBlank]
        #[Assert\Type('array')]
        public array $roles;
    
        #[Assert\NotBlank]
        #[Assert\Type('numeric')]
        public int $age;
    
        #[Assert\NotBlank]
        #[Assert\Type('bool')]
        public bool $isActive;

        public function getSafeFields(): array
        {
            return ['login', 'password', 'roles', 'age', 'isActive'];
        }
    }
    ```
8. Добавляем класс `App\Controller\Api\SaveUser\v5\Output\UserIsSavedDTO`
    ```php
    <?php
   
    namespace App\Controller\Api\SaveUser\v5\Output;
   
    use App\Entity\Traits\SafeLoadFieldsTrait;
   
    class UserIsSavedDTO
    {
        use SafeLoadFieldsTrait;
   
        public int $id;
   
        public string $login;
   
        public int $age;
   
        public bool $isActive;
   
        public function getSafeFields(): array
        {
            return ['id', 'login', 'age', 'isActive'];
        }
    }
    ```
9. Добавляем класс `App\Controller\Api\SaveUser\v5\Controller`
    ```php
    <?php
   
    namespace App\Controller\Api\SaveUser\v5;
   
    use App\Controller\Api\SaveUser\v5\Input\SaveUserDTO;
    use App\Controller\Common\ErrorResponseTrait;
    use FOS\RestBundle\Controller\AbstractFOSRestController;
    use FOS\RestBundle\Controller\Annotations as Rest;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Validator\ConstraintViolationListInterface;
   
    class Controller extends AbstractFOSRestController
    {
        use ErrorResponseTrait;
     
        private SaveUserManager $saveUserManager;
    
        public function __construct(SaveUserManager $saveUserManager)
        {
            $this->saveUserManager = $saveUserManager;
        }
        
        #[Rest\Post(path: '/api/v5/save-user')]
        public function saveUserAction(SaveUserDTO $request, ConstraintViolationListInterface $validationErrors): Response
        {
            if ($validationErrors->count()) {
                $view = $this->createValidationErrorResponse(Response::HTTP_BAD_REQUEST, $validationErrors);
                return $this->handleView($view);
            }
            $user = $this->saveUserManager->saveUser($request);
            [$data, $code] = ($user->id === null) ? [['success' => false], 400] : [['user' => $user], 200];
            return $this->handleView($this->view($data, $code));
        }
    }
    ```
10. Добавляем класс `App\Controller\Api\SaveUser\v5\SaveUserManager`
     ```php
     <?php
   
     namespace App\Controller\Api\SaveUser\v5;
   
     use App\Controller\Api\SaveUser\v5\Input\SaveUserDTO;
     use App\Controller\Api\SaveUser\v5\Output\UserIsSavedDTO;
     use App\Entity\User;
     use Doctrine\ORM\EntityManagerInterface;
     use JMS\Serializer\SerializationContext;
     use JMS\Serializer\SerializerInterface;
   
     class SaveUserManager
     {
         private EntityManagerInterface $entityManager;

         private SerializerInterface $serializer;
   
         public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
         {
             $this->entityManager = $entityManager;
             $this->serializer = $serializer;
         }
   
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
   
             $result = new UserIsSavedDTO();
             $context = (new SerializationContext())->setGroups(['user1', 'user2']);
             $result->loadFromJsonString($this->serializer->serialize($user, 'json', $context));
   
             return $result;
         }
     }
     ```
11. Добавляем класс `App\Symfony\MainParamConverter`
     ```php
     <?php
   
     namespace App\Symfony;
   
     use App\Entity\Traits\SafeLoadFieldsTrait;
     use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
     use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
     use Symfony\Component\HttpFoundation\Request;
     use Symfony\Component\OptionsResolver\OptionsResolver;
     use Symfony\Component\Validator\ConstraintViolationListInterface;
     use Symfony\Component\Validator\Validator\ValidatorInterface;
   
     class MainParamConverter implements ParamConverterInterface
     {
         private ValidatorInterface $validator;
   
         public function __construct(ValidatorInterface $validator)
         {
             $this->validator = $validator;
         }
   
         public function apply(Request $httpRequest, ParamConverter $configuration): bool
         {
             $class = $configuration->getClass();
             /** @var SafeLoadFieldsTrait $request */
             $request = new $class();
             $request->loadFromJsonRequest($httpRequest);
             $errors = $this->validate($request, $httpRequest, $configuration);
             $httpRequest->attributes->set('validationErrors', $errors);
   
             return true;
         }
   
         public function supports(ParamConverter $configuration): bool
         {
             return !empty($configuration->getClass()) &&
                 in_array(SafeLoadFieldsTrait::class, class_uses($configuration->getClass()), true);
         }
   
         public function validate($request, Request $httpRequest, ParamConverter $configuration): ConstraintViolationListInterface
         {
             $httpRequest->attributes->set($configuration->getName(), $request);
             $options = (array)$configuration->getOptions();
             $resolver = new OptionsResolver();
             $resolver->setDefaults([
                 'groups' => null,
                 'traverse' => false,
                 'deep' => false,
             ]);
             $validatorOptions = $resolver->resolve($options['validator'] ?? []);
   
             return $this->validator->validate($request, null, $validatorOptions['groups']);
         }
     }
     ```
12. В классе `App\Entity\User` возвращаем правильные типы данных в атрибутах для полей `$age` и `$isActive`, а также
    добавляем к полю `$isActive` атрибут `#JMS\SerializedName`
     ```php
     #[ORM\Column(type: 'integer', nullable: false)]
     #[JMS\Type('int')]
     #[JMS\Groups(['user1'])]
     private int $age;

     #[ORM\Column(type: 'boolean', nullable: false)]
     #[JMS\Type('bool')]
     #[JMS\Groups(['user1'])]
     #[JMS\SerializedName('isActive')]
     private bool $isActive;
     ```
13. Выполняем запрос Add user v5 из Postman-коллекции v4, видим, что пользователь добавился
