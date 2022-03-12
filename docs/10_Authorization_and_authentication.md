# Авторизация и аутентификация 

Запускаем контейнеры командой `docker-compose up -d`

## Добавляем пререквизиты

1. Заходим в контейнер `php` командой `docker exec -it php sh`. Дальнейшие команды выполняются из контейнера
2. Устанавливаем пакеты `symfony/security-bundle`, `symfony/maker-bundle`
3. В файле `config/packages/security.yaml`
    1. Исправляем секцию `providers`
        ```yaml
        providers:
            app_user_provider:
                entity:
                    class: App\Entity\User
                    property: login
        ```
    2. В секции `firewalls.main` заменяем `provider: users_in_memory` на `provider: app_user_provider`
4. В классе `App\Entity\User`
    1. исправляем атрибуты полей `$login` и `$password`
        ```php
        #[ORM\Column(type: 'string', length: 32, unique: true, nullable: false)]
        private string $login;

        #[ORM\Column(type: 'string', length: 120, nullable: false)]
        private string $password;
        ```
    2. добавляем поле `$roles`, а также геттер и сеттер для него
        ```php
        #[ORM\Column(type: 'string', length: 1024, nullable: false)]
        private string $roles = '{}';

        /**
         * @return string[]
         *
         * @throws JsonException
         */
        public function getRoles(): array
        {
            $roles = json_decode($this->roles, true, 512, JSON_THROW_ON_ERROR);
            // guarantee every user at least has ROLE_USER
            $roles[] = 'ROLE_USER';
    
            return array_unique($roles);
        }
    
        /**
         * @param string[] $roles
         *
         * @throws JsonException
         */
        public function setRoles(array $roles): void
        {
            $this->roles = json_encode($roles, JSON_THROW_ON_ERROR);
        }
        ```
    3. имплементируем `Symfony\Component\Security\Core\User\UserInterface`
        ```php
        public function getSalt(): ?string
        {
            return null;
        }
     
        public function eraseCredentials(): void
        {
        }
     
        public function getUsername(): string
        {
            return $this->login;
        }
     
        public function getUserIdentifier(): string
        {
            return $this->login;
        }
        ``` 
    4. имплементируем `Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface` (нужный метод уже есть)
    5. Исправляем метод `toArray`
        ```php
        /**
         * @throws JsonException
         */
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'login' => $this->login,
                'password' => $this->password,
                'roles' => $this->getRoles(),
                'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
                'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
                'tweets' => array_map(static fn(Tweet $tweet) => $tweet->toArray(), $this->tweets->toArray()),
                'followers' => array_map(
                    static fn(User $user) => ['id' => $user->getId(), 'login' => $user->getLogin()],
                    $this->followers->toArray()
                ),
                'authors' => array_map(
                    static fn(User $user) => ['id' => $user->getLogin(), 'login' => $user->getLogin()],
                    $this->authors->toArray()
                ),
                'subscriptionFollowers' => array_map(
                    static fn(Subscription $subscription) => [
                        'subscription_id' => $subscription->getId(),
                        'user_id' => $subscription->getFollower()->getId(),
                        'login' => $subscription->getFollower()->getLogin(),
                    ],
                    $this->subscriptionFollowers->toArray()
                ),
                'subscriptionAuthors' => array_map(
                    static fn(Subscription $subscription) => [
                        'subscription_id' => $subscription->getId(),
                        'user_id' => $subscription->getAuthor()->getId(),
                        'login' => $subscription->getAuthor()->getLogin(),
                    ],
                    $this->subscriptionAuthors->toArray()
                ),
            ];
        }
        ```
5. Генерируем миграцию командой `php bin/console doctrine:migrations:diff`
6. Выполняем миграцию командой `php bin/console doctrine:migrations:migrate`
7. Исправляем класс `App\DTO\SaveUserDTO`
    ```php
    <?php
    
    namespace App\DTO;
    
    use App\Entity\User;
    use JsonException;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Validator\Constraints as Assert;
    
    class SaveUserDTO
    {
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        public string $login;
    
        #[Assert\NotBlank]
        #[Assert\Length(max: 32)]
        public string $password;
    
        #[Assert\NotBlank]
        public int $age;
    
        public bool $isActive;
    
        #[Assert\Type('array')]
        public array $followers;
    
        /** @var string[] */
        public array $roles;
    
        public function __construct(array $data)
        {
            $this->login = $data['login'] ?? '';
            $this->password = $data['password'] ?? '';
            $this->age = $data['age'] ?? 0;
            $this->isActive = $data['isActive'] ?? false;
            $this->followers = $data['followers'] ?? [];
            $this->roles = $data['roles'] ?? [];
        }
    
        public static function fromEntity(User $user): self
        {
            return new self([
                'login' => $user->getLogin(),
                'password' => $user->getPassword(),
                'age' => $user->getAge(),
                'isActive' => $user->isActive(),
                'roles' => $user->getRoles(),
                'followers' => array_map(
                    static function (User $user) {
                        return [
                            'id' => $user->getId(),
                            'login' => $user->getLogin(),
                            'password' => $user->getPassword(),
                            'age' => $user->getAge(),
                            'isActive' => $user->isActive(),
                        ];
                    },
                    $user->getFollowers()
                ),
            ]);
        }
    
        /**
         * @throws JsonException
         */
        public static function fromRequest(Request $request): self
        {
            $roles = $request->request->get('roles') ?? $request->query->get('roles');
    
            return new self(
                [
                    'login' => $request->request->get('login') ?? $request->query->get('login'),
                    'password' => $request->request->get('password') ?? $request->query->get('password'),
                    'roles' => json_decode($roles, true, 512, JSON_THROW_ON_ERROR),
                ]
            );
        }
    }
    ```
8. В классе `App\Manager\UserManager`
    1. добавляем инъекцию `UserPasswordEncoderInterface`
        ```php
        private UserPasswordHasherInterface $userPasswordHasher;
        
        public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory, UserPasswordHasherInterface $userPasswordHasher)
        {
            $this->entityManager = $entityManager;
            $this->formFactory = $formFactory;
            $this->userPasswordHasher = $userPasswordHasher;
        }
        ```
    2. Исправляем метод `saveUserFromDTO`
        ```php
        public function saveUserFromDTO(User $user, SaveUserDTO $saveUserDTO): ?int
        {
            $user->setLogin($saveUserDTO->login);
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $saveUserDTO->password));
            $user->setAge($saveUserDTO->age);
            $user->setIsActive($saveUserDTO->isActive);
            $user->setRoles($saveUserDTO->roles);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
    
            return $user->getId();
        }
        ```
9. Добавляем класс `App\Controller\Api\v3\UserController`
    ```php
    <?php
    
    namespace App\Controller\Api\v3;
    
    use App\DTO\SaveUserDTO;
    use App\Entity\User;
    use App\Manager\UserManager;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    
    #[Route(path: 'api/v3/user')]
    class UserController extends AbstractController
    {
        private UserManager $userManager;
    
        public function __construct(UserManager $userManager)
        {
            $this->userManager = $userManager;
        }
    
        #[Route(path: '', methods: ['POST'])]
        public function saveUserAction(Request $request): Response
        {
            $saveUserDTO = SaveUserDTO::fromRequest($request);
            $userId = $this->userManager->saveUserFromDTO(new User(), $saveUserDTO);
            [$data, $code] = $userId === null ?
                [['success' => false], Response::HTTP_BAD_REQUEST] :
                [['success' => true, 'userId' => $userId], Response::HTTP_OK];
    
            return new JsonResponse($data, $code);
        }
    
        #[Route(path: '', methods: ['GET'])]
        public function getUsersAction(Request $request): Response
        {
            $perPage = $request->query->get('perPage');
            $page = $request->query->get('page');
            $users = $this->userManager->getUsers($page ?? 0, $perPage ?? 20);
            $code = empty($users) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK;
    
            return new JsonResponse(['users' => array_map(static fn(User $user) => $user->toArray(), $users)], $code);
        }
    
        #[Route(path: '', methods: ['DELETE'])]
        public function deleteUserAction(Request $request): Response
        {
            $userId = $request->query->get('userId');
            $result = $this->userManager->deleteUserById($userId);
    
            return new JsonResponse(['success' => $result], $result ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
        }
    
        #[Route(path: '', methods: ['PATCH'])]
        public function updateUserAction(Request $request): Response
        {
            $userId = $request->query->get('userId');
            $saveUserDTO = SaveUserDTO::fromRequest($request);
            $result = $this->userManager->updateUserFromDTO($userId, $saveUserDTO);
    
            return new JsonResponse(['success' => $result], $result ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
        }
    }
    ```
10. Выполняем запрос Add user v3 из Postman-коллекции v3, видим, что пользователь добавлен в БД и пароль захэширован

## Добавляем форму логина 

1. В файле `config/packages/security.yaml` в секции `firewall.main` добавляем `security:false`
2. Генерируем форму логина `php bin/console make:auth`
     1. Выбираем `Login form authenticator`
     2. Указываем имя класса для аутентификатора `AppLoginAuthenticator` и контроллера `LoginController`
     3. Не создаём `/logout URL`
3. В файле `src/templates/security/login.html.twig` зависимость от базового шаблона `layout.twig`
4. Переходим по адресу `http://localhost:7777/login` и вводим логин/пароль пользователя, которого создали при проверке
    API. Видим, что после нажатия на `Sign in` ничего не происходит.

## Включаем security 

1. Убираем в файле `config/packages/security.yaml` в секции `firewall.main` строку `security:false`
2. Ещё раз переходим по адресу `http://localhost:7777/login` и вводим логин/пароль пользователя, после нажатия на
    `Sign in` получаем ошибку

## Исправляем ошибку
 
1. В классе `App\Security\AppLoginAuthenticator` исправляем метод `onAuthenticationSuccess`
    ```php
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_api_v3_user_getusers'));
    }
    ```
2. Проверяем, что всё заработало

## Добавляем авторизацию для ROLE_ADMIN

1. В файле `config/packages/security.yaml` в секцию `access_control` добавляем условие
     ```yaml
     - { path: ^/api/v3/user, roles: ROLE_ADMIN, methods: [DELETE] }
     ```
2. Выполняем запрос Delete user v3 из Postman-коллекции v3, добавив Cookie `PHPSESSID`, которую можно посмотреть в браузере
    после успешного логина. Проверяем, что возвращается ответ 500 с сообщением `Access denied`
3. Добавляем роль `ROLE_ADMIN` пользователю в БД, перелогинимся, чтобы получить корректную сессию и проверяем, что
    стал возвращаться ответ 200

## Добавляем авторизацию для ROLE_VIEW
 
1. В файле `config/packages/security.yaml` в секции `access_control` добавляем условие
     ```yaml
     - { path: ^/api/v3/user, roles: ROLE_VIEW, methods: [GET] }
     ```
2. Выполняем запрос Get user list v3 из Postman-коллекции v3. Проверяем, что возвращается ответ 500 с сообщением
    `Access denied`
 
## Добавляем иерархию ролей 

1. Добавляем в файл `config/packages/security.yaml` секцию `role_hierarchy`
     ```yaml
     role_hierarchy:
         ROLE_ADMIN: ROLE_VIEW
     ```
2. Ещё раз выполняем запрос Get user list v3 из Postman-коллекции v3. Проверяем, что возвращается ответ 200
 
## Добавляем Voter 

1. В класс `App\Manager\UserManager` добавляем метод `findUserById`
     ```php
     public function findUserById(int $userId): ?User
     {
         /** @var UserRepository $userRepository */
         $userRepository = $this->entityManager->getRepository(User::class);

         return $userRepository->find($userId);
     }
     ```
2. Добавляем класс `App\Security\Voter\UserVoter`
     ```php
     <?php
    
     namespace App\Security\Voter;
    
     use App\Entity\User;
     use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
     use Symfony\Component\Security\Core\Authorization\Voter\Voter;
    
     class UserVoter extends Voter
     {
         public const DELETE = 'delete';
    
         protected function supports(string $attribute, $subject): bool
         {
             return $attribute === self::DELETE && ($subject instanceof User);
         }
    
         protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
         {
             $user = $token->getUser();
             if (!$user instanceof User) {
                 return false;
             }
    
             /** @var User $subject */
             return $user->getId() !== $subject->getId();
         }
     }
     ```
3. В классе `App\Controller\Api\v3\UserController`
     1. добавляем инъекцию `AuthorizationCheckerInterface`
         ```php
         private AuthorizationCheckerInterface $authorizationChecker;
    
         public function __construct(UserManager $userManager, AuthorizationCheckerInterface $authorizationChecker)
         {
             $this->userManager = $userManager;
             $this->authorizationChecker = $authorizationChecker;
         }
         ```
     2. Исправляем метод `deleteUserAction`
         ```php
         #[Route(path: '', methods: ['DELETE'])]
         public function deleteUserAction(Request $request): Response
         {
             $userId = $request->query->get('userId');
             $user = $this->userManager->findUserById($userId);
             if (!$this->authorizationChecker->isGranted(UserVoter::DELETE, $user)) {
                 return new JsonResponse('Access denied', Response::HTTP_FORBIDDEN);
             }
             $result = $this->userManager->deleteUserById($userId);
     
             return new JsonResponse(['success' => $result], $result ? Response::HTTP_OK : Response::HTTP_NOT_FOUND);
         }
         ```
4. Выполняем запрос Delete user v3 из Postman-коллекции v3 сначала с идентификатором другого пользователя (не того,
    который залогинен), потом со своим идентификатором. Проверяем, что в первом случае ответ 200, во втором - 403
 
## Изменяем стратегию для Voter'ов 

1. В файл `config/packages/security.yaml` добавляем секцию `access_decision_manager`
     ```yaml
     access_decision_manager:
         strategy: consensus
     ```
2. Добавляем класс `App\Security\Voter\FakeUserVoter`
     ```php
     <?php
    
     namespace App\Security\Voter;
    
     use App\Entity\User;
     use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
     use Symfony\Component\Security\Core\Authorization\Voter\Voter;
    
     class FakeUserVoter extends Voter
     {
         protected function supports(string $attribute, $subject): bool
         {
             return $subject instanceof User;
         }
    
         protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
         {
             return false;
         }
     }
     ```
3. Добавляем класс `App\Security\Voter\DummyUserVoter`
     ```php
     <?php
    
     namespace App\Security\Voter;
        
     use App\Entity\User;
     use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
     use Symfony\Component\Security\Core\Authorization\Voter\Voter;
    
     class DummyUserVoter extends Voter
     {
         protected function supports(string $attribute, $subject): bool
         {
             return $subject instanceof User;
         }
    
         protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
         {
             return false;
         }
     }
     ```
4. Проверяем, что удалить другого (не того, кто выполняет запрос) пользователя тоже больше нельзя
