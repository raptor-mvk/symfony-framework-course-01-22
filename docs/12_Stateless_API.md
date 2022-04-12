# Stateless API

Запускаем контейнеры командой `docker-compose up -d`

## Добавляем получение токена

1. Входим в контейнер командой `docker exec -it php sh`. Дальнейшие команды выполняются из контейнера
2. В файле `config/packages/security.yaml` добавляем в секцию `firewalls`
    ```yaml
    token:
        pattern: ^/api/v1/token
        security: false
    ``` 
3. В класс `App\Entity\User` добавляем поле `$token` и стандартные геттер/сеттер для него
    ```php
    #[ORM\Column(type: 'string', length: 32, unique: true, nullable: true)]
    private ?string $token = null;

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): void
    {
        $this->token = $token;
    }
    ```
4. Генерируем миграцию командой `php bin/console doctrine:migrations:diff`
5. Выполняем миграцию командой `php bin/console doctrine:migrations:migrate`
6. В файл `App\Manager\UserManager` добавляем новые методы `findUserByLogin` и `updateUserToken`
    ```php
    public function findUserByLogin(string $login): ?User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User|null $user */
        $user = $userRepository->findOneBy(['login' => $login]);
       
        return $user;
    }

    public function updateUserToken(string $login): ?string
    {
    $user = $this->findUserByLogin($login);
        if ($user === null) {
            return false;
        }
        $token = base64_encode(random_bytes(20));
        $user->setToken($token);
        $this->entityManager->flush();
       
        return $token;
    }
    ```
7. Добавляем класс `App\Service\AuthService`
    ```php
    <?php
    
    namespace App\Service;
    
    use App\Manager\UserManager;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
    
    class AuthService
    {
        private UserManager $userManager;
        
        private UserPasswordHasherInterface $passwordHasher;
    
        public function __construct(UserManager $userManager, UserPasswordHasherInterface $passwordHasher)
        {
            $this->userManager = $userManager;
            $this->passwordHasher = $passwordHasher;
        }
    
        public function isCredentialsValid(string $login, string $password): bool
        {
            $user = $this->userManager->findUserByLogin($login);
            if ($user === null) {
                return false;
            }
    
            return $this->passwordHasher->isPasswordValid($user, $password);
        }
    
        public function getToken(string $login): ?string
        {
            return $this->userManager->updateUserToken($login);
        }
    }
    ```
8. Добавляем класс `App\Controller\Api\v1\TokenController`
    ```php
    <?php
    
    namespace App\Controller\Api\v1;
    
    use App\Service\AuthService;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    
    #[Route(path: '/api/v1/token')]
    class TokenController extends AbstractController
    {
        private AuthService $authService;
    
        public function __construct(AuthService $authService)
        {
            $this->authService = $authService;
        }
    
        #[Route(path: '', methods: ['POST'])]
        public function getTokenAction(Request $request): Response
        {
            $user = $request->getUser();
            $password = $request->getPassword();
            if (!$user || !$password) {
                return new JsonResponse(['message' => 'Authorization required'], Response::HTTP_UNAUTHORIZED);
            }
            if (!$this->authService->isCredentialsValid($user, $password)) {
                return new JsonResponse(['message' => 'Invalid password or username'], Response::HTTP_FORBIDDEN);
            }
    
            return new JsonResponse(['token' => $this->authService->getToken($user)]);
        }
    }
    ```
9. Выполняем запрос Add user v4 из Postman-коллекции v5, чтобы получить в БД пользователя
10. Выполняем запрос Get token из Postman-коллекции v5 без авторизации, получаем ошибку 401
11. Выполняем запрос Get token из Postman-коллекции v5 с неверными реквизитами, получаем ошибку 403
12. Выполняем запрос Get token из Postman-коллекции v5 с верными реквизитами, получаем токен. Проверяем, что в БД токен
     тоже сохранился.

## Добавляем аутентификатор с помощью токена

1. Устанавливаем пакет `lexik/jwt-authentication-bundle`
2. В файле `config/packages/security.yaml` меняем содержимое секции `firewalls.main`
    ```yaml
    main:
        lazy: true
        provider: app_user_provider
        stateless: true
        custom_authenticator: App\Security\ApiTokenAuthenticator
    ```
3. В классе `App\Manager\UserManager` добавляем метод `findUserByToken`
    ```php
    public function findUserByToken(string $token): ?User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User|null $user */
        $user = $userRepository->findOneBy(['token' => $token]);

        return $user;
    }
    ```
4. Добавляем класс `App\Security\ApiTokenAuthenticator`
    ```php
    <?php
    
    namespace App\Security;
    
    use App\Manager\UserManager;
    use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
    use Symfony\Component\Security\Core\Exception\AuthenticationException;
    use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
    use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
    use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
    use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
    use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
    
    class ApiTokenAuthenticator extends AbstractAuthenticator
    {
        private UserManager $userManager;
    
        public function __construct(UserManager $userManager)
        {
            $this->userManager = $userManager;
        }
    
        public function supports(Request $request): ?bool
        {
            return true;
        }
    
        public function authenticate(Request $request): PassportInterface
        {
            $extractor = new AuthorizationHeaderTokenExtractor('Bearer', 'Authorization');
            $token = $extractor->extract($request);
            if ($token === null) {
                throw new CustomUserMessageAuthenticationException('No API token was provided');
            }
    
            return new SelfValidatingPassport(
                new UserBadge($token, fn($token) => $this->userManager->findUserByToken($token))
            );
        }
    
        public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
        {
            return null;
        }
    
        public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
        {
            return new JsonResponse(['message' => 'Invalid API Token'], Response::HTTP_FORBIDDEN);
        }
    }
    ```
5. Выполняем запрос Get user list v3 из Postman-коллекции v5 без авторизации, получаем ошибку 403
6. Выполняем запрос Get token из Postman-коллекции v5, полученныйR токен заносим в Bearer-авторизацию запроса Get user
    list v3 и выполняем его, видим, что ответ возвращается
7. Удаляем у пользователя в БД роль `ROLE_ADMIN` и проверяем, что запрос Get user list v3 сразу же возвращает ошибку
    500 с текстом `Access Denied`

## Добавляем JWT-аутентификатор

1. Создаём каталог `config/jwt`
2. Генерируем ключи, используя passphrase из файла `.env` командами
    ```shell
    openssl genrsa -out config/jwt/private.pem -aes256 4096
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
    chmod 777 config/jwt -R
    ```
3. В файл `.env` добавляем параметр
    ```shell
    JWT_TTL_SEC=3600
    ```
4. В файл `config/packages/lexik_jwt_authentication.yaml` добавляем строку
    ```yaml
    token_ttl: '%env(JWT_TTL_SEC)%'
    ```
5. В классе `App\Service\AuthService`
    1. Добавляем зависимость от `JWTEncoderInterface` и целочисленный параметр `tokenTTL`
        ```php
        private JWTEncoderInterface $jwtEncoder;
    
        private int $tokenTTL;
    
        public function __construct(UserManager $userManager, UserPasswordHasherInterface $passwordHasher, JWTEncoderInterface $jwtEncoder, int $tokenTTL)
        {
            $this->userManager = $userManager;
            $this->passwordHasher = $passwordHasher;
            $this->jwtEncoder = $jwtEncoder;
            $this->tokenTTL = $tokenTTL;
        }
        ```         
    1. Исправляем метод `getToken`
        ```php
        public function getToken(string $login): string
        {
            $tokenData = ['username' => $login, 'exp' => time() + $this->tokenTTL];
   
            return $this->jwtEncoder->encode($tokenData);
        }
        ```
6. В файле `config/services.yaml` добавляем новый сервис
    ```yaml
    App\Service\AuthService:
        arguments:
            $tokenTTL: '%env(JWT_TTL_SEC)%'
    ```
7. Добавляем класс `App\Security\AuthUser`
    ```php
    <?php
    
    namespace App\Security;
    
    use Symfony\Component\Security\Core\User\UserInterface;
    
    class AuthUser implements UserInterface
    {
        private string $username;
        
        /** @var string[] */
        private array $roles;
    
        public function __construct(array $credentials)
        {
            $this->username = $credentials['username'];
            $this->roles = array_unique(array_merge($credentials['roles'] ?? [], ['ROLE_USER']));
        }
    
        /**
         * @return string[]
         */
        public function getRoles(): array
        {
            return $this->roles;
        }
    
        public function getPassword(): string
        {
            return '';
        }
    
        public function getSalt(): string
        {
            return '';
        }
    
        public function getUsername(): string
        {
            return $this->username;
        }
    
        public function eraseCredentials(): void
        {
        }
    }
    ```
8. Добавляем класс `App\Security\JWTTokenAuthenticator`
    ```php
    <?php
    
    namespace App\Security;
    
    use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
    use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
    use Symfony\Component\Security\Core\Exception\AuthenticationException;
    use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
    use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
    use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
    use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
    use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
    
    class JWTTokenAuthenticator extends AbstractAuthenticator
    {
        private JWTEncoderInterface $jwtEncoder;
    
        public function __construct(JWTEncoderInterface $jwtEncoder)
        {
            $this->jwtEncoder = $jwtEncoder;
        }
    
        public function supports(Request $request): ?bool
        {
            return true;
        }
    
        public function authenticate(Request $request): PassportInterface
        {
            $extractor = new AuthorizationHeaderTokenExtractor('Bearer', 'Authorization');
            $token = $extractor->extract($request);
            if ($token === null) {
                throw new CustomUserMessageAuthenticationException('No API token was provided');
            }
            $tokenData = $this->jwtEncoder->decode($token);
            if (!isset($tokenData['username'])) {
                throw new CustomUserMessageAuthenticationException('Invalid JWT token');
            }
    
            return new SelfValidatingPassport(
                new UserBadge($tokenData['username'], fn() => new AuthUser($tokenData))
            );
        }
    
        public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
        {
            return null;
        }
    
        public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
        {
            return new JsonResponse(['message' => 'Invalid JWT Token'], Response::HTTP_FORBIDDEN);
        }
    }
    ```
9. В файле `config/packages/security.yaml` заменяем в секции `firewalls.main` значение поля `custom_authenticator` на
    `App\Security\JWTTokenAuthenticator`
10. Возвращаем пользователю в БД роль `ROLE_ADMIN`
11. Выполняем запрос Get user list v3 из Postman-коллекции v5 со старым токеном, получаем ошибку 500 с сообщением
     `Invalid JWT Token`
12. Выполняем запрос Get token из Postman-коллекции v5, полученный токен заносим в Bearer-авторизацию запроса Get user
     list v3 и выполняем его, получаем ошибку 500 с сообщением `Access Denied`

## Исправляем получение JWT-токена

1. В классе `App\Service\AuthService` исправляем метод `getToken`
     ```php
     public function getToken(string $login): string
     {
         $user = $this->userManager->findUserByLogin($login);
         $roles = $user ? $user->getRoles() : [];
         $tokenData = [
             'username' => $login,
             'roles' => $roles,
             'exp' => time() + $this->tokenTTL,
         ];

         return $this->jwtEncoder->encode($tokenData);
     }
     ```
2. Перевыпускаем токен запросом Get token из Postman-коллекции v5, полученный токен заносим в Bearer-авторизацию
    запроса Get user list v3. Выполняем запрос Get user list v3, получаем результат
3. Удалям у пользователя в БД роль `ROLE_ADMIN`
4. Выполняем запрос Get user list v3 и видим результат, хоть роль и была удалена в БД
5. Ещё раз перевыпускаем токен запросом Get token из Postman-коллекции v5, полученный токен заносим в
    Bearer-авторизацию запроса Get user list v3 и выполняем его, получаем ошибку 500 с сообщением `Access denied`
