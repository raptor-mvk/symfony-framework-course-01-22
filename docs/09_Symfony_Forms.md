# Symfony Forms

Запускаем контейнеры командой `docker-compose up -d`

## Добавляем форму для создания пользователя

1. Заходим в контейнер `php` командой `docker exec -it php sh`. Дальнейшие команды выполняются из контейнера
2. Устанавливаем пакет Symfony Forms командой `composer require symfony/form`
3. Устанавливаем валидатор командой `composer require symfony/validator`
4. В классе `App\Entity\User` добавляем поля и геттеры/сеттеры для них
    ```php
    #[ORM\Column(type: 'string', length: 32, nullable: false)]
    private string $password;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $age;

    #[ORM\Column(type: 'boolean', nullable: false)]
    private string $isActive;

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }
    ```
5. В классе `App\Manager\UserManager`
    1. Добавляем зависимость от сервиса `FormFactoryInterface`
        ```php
        private FormFactoryInterface $formFactory;
      
        public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory)
        {
            $this->entityManager = $entityManager;
            $this->formFactory = $formFactory;
        }
        ```
    2. Добавляем метод `getSaveForm`
        ```php
        public function getSaveForm(): FormInterface
        {
            return $this->formFactory->createBuilder()
                ->add('login', TextType::class)
                ->add('password', PasswordType::class)
                ->add('age', IntegerType::class)
                ->add('isActive', CheckboxType::class, ['required' => false])
                ->add('submit', SubmitType::class)
                ->getForm();
        }
        ```   
6. В классе `App\Controller\Api\v1\UserController`
    1. Добавляем зависимость от Twig
        ```php
        private Environment $twig;
    
        public function __construct(UserManager $userManager, EventDispatcherInterface $eventDispatcher, Environment $twig)
        {
            $this->userManager = $userManager;
            $this->eventDispatcher = $eventDispatcher;
            $this->twig = $twig;
        }
        ```
    2. Добавляем метод `getSaveFormAction`
        ```php
        #[Route(path: '/form', methods: ['GET'])]
        public function getSaveFormAction(): Response
        {
            $form = $this->userManager->getSaveForm();
            $content = $this->twig->render('form.twig', [
                'form' => $form->createView(),
            ]);
    
            return new Response($content);
        }
        ```
7. Добавляем файл `src/templates/form.twig`
    ```html
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
    </head>
    <body>
        {{ form(form) }}
    </body>
    </html>
    ```
8. В браузере переходим по адресу `http://localhost:7777/api/v1/user/form`, видим форму

## Добавляем логику для сохранения данных из формы

1. Добавляем класс `App\DTO\SaveUserDTO`
    ```php
    <?php
   
    namespace App\DTO;
    
    use App\Entity\User;
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
    
        public function __construct(array $data)
        {
            $this->login = $data['login'] ?? '';
            $this->password = $data['password'] ?? '';
            $this->age = $data['age'] ?? 0;
            $this->isActive = $data['isActive'] ?? false;
        }
    
        public static function fromEntity(User $user): self
        {
            return new self([
                'login' => $user->getLogin(),
                'password' => $user->getPassword(),
                'age' => $user->getAge(),
                'isActive' => $user->isActive(),
            ]);
        }    
    }
    ```
2. В классе `App\Manager\UserManager` добавляем метод `saveUserFromDTO`
    ```php
    public function saveUserFromDTO(User $user, SaveUserDTO $saveUserDTO): ?int
    {
        $user->setLogin($saveUserDTO->login);
        $user->setPassword($saveUserDTO->password);
        $user->setAge($saveUserDTO->age);
        $user->setIsActive($saveUserDTO->isActive);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user->getId();
    }
    ```
3. В классе `App\Controller\Api\v1\UserController` добавляем метод `saveUserFormAction`
    ```php
    #[Route(path: '/form', methods: ['POST'])]
    public function saveUserFormAction(Request $request): Response
    {
        $form = $this->userManager->getSaveForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userId = $this->userManager->saveUserFromDTO(new User(), new SaveUserDTO($form->getData()));
            [$data, $code] = ($userId === null) ? [['success' => false], 400] : [['id' => $userId], 200];

            return new JsonResponse($data, $code);
        }
        $content = $this->twig->render('form.twig', [
            'form' => $form->createView(),
        ]);

        return new Response($content);
    }
    ```
4. Выполняем команду `php bin/console doctrine:migrations:diff` для получения миграции
5. Выполняем команду `php bin/console doctrine:migrations:migrate` для применения полученной миграции
6. В браузере переходим по адресу `http://localhost:7777/api/v1/user/form`, заполняем форму, нажимаем Submit, видим
   ответ с id пользователя, проверяем, что в БД пользователь присутствует

## Добавляем форму для обновления пользователя

1. В классе `App\Manager\UserManager` добавляем методы `getUpdateForm` и `updateUserFromDTO`
    ```php
    public function getUpdateForm(int $userId): ?FormInterface
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return null;
        }

        return $this->formFactory->createBuilder(FormType::class, SaveUserDTO::fromEntity($user))
            ->add('login', TextType::class)
            ->add('password', PasswordType::class)
            ->add('age', IntegerType::class)
            ->add('isActive', CheckboxType::class, ['required' => false])
            ->add('submit', SubmitType::class)
            ->setMethod('PATCH')
            ->getForm();
    }

    public function updateUserFromDTO(int $userId, SaveUserDTO $userDTO): bool
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return false;
        }

        return $this->saveUserFromDTO($user, $userDTO);
    }
    ```
2. В классе `App\Controller\Api\v1\UserController` добавляем методы `getUpdateFormAction` и `updateUserFormAction`
    ```php
    #[Route(path: '/form/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getUpdateFormAction(int $id): Response
    {
        $form = $this->userManager->getUpdateForm($id);
        if ($form === null) {
            return new JsonResponse(['message' => "User with ID $id not found"], 404);
        }
        $content = $this->twig->render('form.twig', [
            'form' => $form->createView(),
        ]);

        return new Response($content);
    }

    #[Route(path: '/form/{id}', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function updateUserFormAction(Request $request, int $id): Response
    {
        $form = $this->userManager->getUpdateForm($id);
        if ($form === null) {
            return new JsonResponse(['message' => "User with ID $id not found"], 404);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->userManager->updateUserFromDTO($id, $form->getData());

            return new JsonResponse(['success' => $result], $result ? 200 : 400);
        }
        $content = $this->twig->render('form.twig', [
            'form' => $form->createView(),
        ]);

        return new Response($content);
    }
    ```
3. В браузере переходим по адресу `http://localhost:7777/api/v1/user/form/ID`, где ID - идентификатор созданного
   пользователя из предыдущего запроса, видим заполненную форму
4. Исправляем данные, нажимаем Submit, видим ошибку.
   
## Исправляем ошибку роутинга

1. Исправляем файл `public/index.php`
    ```php
    <?php
    
    use App\Kernel;
    use Symfony\Component\HttpFoundation\Request;
    
    require_once dirname(__DIR__).'/vendor/autoload_runtime.php';
    
    return function (array $context) {
        Request::enableHttpMethodParameterOverride();
        return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    };
    ```
2. В браузере переходим по адресу `http://localhost:7777/api/v1/user/form/ID`, где ID - идентификатор созданного
   пользователя из предыдущего запроса, видим заполненную форму
3. Исправляем данные, нажимаем Submit, видим успешный ответ, проверяем, что в БД данные пользователя изменились

## Добавляем boostrap в форму

1. Исправляем файл `src/templates/form.twig`
    ```php
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        {% block head_css %}
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/1.1.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
        {% endblock %}
    </head>
    <body>
    {% form_theme form 'bootstrap_4_layout.html.twig' %}
    <div style="width:50%;margin-left:10px;margin-top:10px">
        {{ form(form) }}
    </div>
    {% block head_js %}
        <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    {% endblock %}
    </body>
    </html>
    ```
2. В браузере переходим по адресу `http://localhost:7777/api/v1/user/form`, видим более красивый вариант формы

## Добавляем отображение отношений в форму редактирования

1. Добавляем класс `App\Form\LinkedUserType`
    ```php
    <?php
    
    namespace App\Form;
    
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
    use Symfony\Component\Form\Extension\Core\Type\HiddenType;
    use Symfony\Component\Form\Extension\Core\Type\IntegerType;
    use Symfony\Component\Form\Extension\Core\Type\PasswordType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\Form\FormBuilderInterface;
    
    class LinkedUserType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options)
        {
        $builder->add('login', TextType::class)
            ->add('password', PasswordType::class, ['required' => false])
            ->add('age', IntegerType::class)
            ->add('isActive', CheckboxType::class, ['required' => false])
            ->add('id', HiddenType::class);
        }
    }
    ```
2. В классе `App\Manager\UserManager` исправляем метод `getUpdateForm`
    ```php
    public function getUpdateForm(int $userId): ?FormInterface
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return null;
        }

        return $this->formFactory->createBuilder(FormType::class, SaveUserDTO::fromEntity($user))
            ->add('login', TextType::class)
            ->add('password', PasswordType::class, ['required' => false])
            ->add('age', IntegerType::class)
            ->add('isActive', CheckboxType::class, ['required' => false])
            ->add('submit', SubmitType::class)
            ->add('followers', CollectionType::class, [
                'entry_type' => LinkedUserType::class,
                'entry_options' => ['label' => false],
            ])
            ->setMethod('PATCH')
            ->getForm();
    }
    ```
3. Исправляем файл `src/templates/form.twig`
    ```html
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        {% block head_css %}
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/1.1.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
        {% endblock %}
    </head>
    <body>
    {% form_theme form 'bootstrap_4_layout.html.twig' %}
    <div style="width:50%;margin-left:10px;margin-top:10px">
        {{ form_start(form) }}
        {{ form_row(form.login) }}
        {{ form_row(form.password) }}
        {{ form_row(form.age) }}
        {{ form_row(form.isActive) }}
    
        <h3>Followers</h3>
        <ul class="followers">
            {% for follower in form.followers %}
                <li>{{ form_row(follower.login) }}</li>
                <li>{{ form_row(follower.password) }}</li>
                <li>{{ form_row(follower.age) }}</li>
                <li>{{ form_row(follower.isActive) }}</li>
            {% endfor %}
        </ul>
        {{ form_row(form.submit) }}
        {{ form_end(form, {'render_rest': false}) }}
    </div>
    {% block head_js %}
        <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    {% endblock %}
    </body>
    </html>
    ```
4. В классе `App\Entity\User` добавляем метод `getFollowers`
    ```php
    /**
     * @return User[]
     */
    public function getFollowers(): array
    {
        return $this->followers->toArray();
    }
    ```
5. Исправляем класс `App\DTO\SaveUserDTO`
    ```php
    <?php
    
    namespace App\DTO;
    
    use App\Entity\User;
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
    
        public function __construct(array $data)
        {
            $this->login = $data['login'] ?? '';
            $this->password = $data['password'] ?? '';
            $this->age = $data['age'] ?? 0;
            $this->isActive = $data['isActive'] ?? false;
            $this->followers = $data['followers'] ?? [];
        }
    
        public static function fromEntity(User $user): self
        {
            return new self([
                'login' => $user->getLogin(),
                'password' => $user->getPassword(),
                'age' => $user->getAge(),
                'isActive' => $user->isActive(),
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
    }
    ```
6. В базе данных добавляем в таблицу `author_follower` связь между двумя пользователями, добавленными ранее в БД.
7. В браузере переходим по адресу `http://localhost:7777/api/v1/user/form/ID`, где ID - идентификатор
   пользователя-автора из предыдущего пункта
8. Исправляем данные в форме, нажимаем на Submit и видим в БД, что обновились только данные пользователя-автора, но не
   подписчика
   
## Добавляем редактирование полей отношений в форму редактирования

1. B классе `App\Manager\UserManager` исправляем метод `updateUserFromDTO`
    ```php
    public function updateUserFromDTO(int $userId, SaveUserDTO $userDTO): bool
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return false;
        }

        foreach ($userDTO->followers as $followerData) {
            $followerUserDTO = new SaveUserDTO($followerData);
            /** @var User $followerUser */
            $followerUser = $userRepository->find($followerData['id']);
            if ($followerUser !== null) {
                $this->saveUserFromDTO($followerUser, $followerUserDTO);
            }
        }

        return $this->saveUserFromDTO($user, $userDTO);
    }
    ```
2. В браузере переходим по адресу `http://localhost:7777/api/v1/user/form/ID`, где ID - идентификатор
   пользователя-автора из предыдущего раздела
3. Исправляем данные в форме, нажимаем на Submit и видим в БД, что обновились данные и пользователя-автора, и
   пользователя-подписчика

## Добавляем возможность добавления отношений в форму редактирования

1. Исправляем файл `src/templates/form.twig`
    ```html
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        {% block head_css %}
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/1.1.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
        {% endblock %}
    </head>
    <body>
    {% form_theme form 'bootstrap_4_layout.html.twig' %}
    <div style="width:50%;margin-left:10px;margin-top:10px">
        {{ form_start(form) }}
        {{ form_row(form.login) }}
        {{ form_row(form.password) }}
        {{ form_row(form.age) }}
        {{ form_row(form.isActive) }}
    
        <h3>Followers</h3>
        <ul class="followers" data-prototype='{{ form_widget(form.followers.vars.prototype)|e('html_attr') }}'>
            {% for follower in form.followers %}
                <li>{{ form_row(follower.login) }}</li>
                <li>{{ form_row(follower.password) }}</li>
                <li>{{ form_row(follower.age) }}</li>
                <li>{{ form_row(follower.isActive) }}</li>
            {% endfor %}
        </ul>
        {{ form_row(form.submit) }}
        {{ form_end(form, {'render_rest': false}) }}
    </div>
    {% block head_js %}
        <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
        <script src="/user-form.js"></script>
    {% endblock %}
    </body>
    </html>
    ```
2. Добавляем файл `public/user-form.js`
    ```javascript
    function addFollowerForm($collectionHolder, $newLinkLi) {
        var prototype = $collectionHolder.data('prototype');
        var index = $collectionHolder.data('index');
        var newForm = prototype;
        newForm = newForm.replace(/__name__/g, index);
        $collectionHolder.data('index', index + 1);
        var $newFormLi = $('<li></li>').append(newForm);
        $newLinkLi.before($newFormLi);
    }
    
    var $collectionHolder;
    var $addFollowerButton = $('<button type="button" class="btn-info btn add_follower_link">Add a follower</button>');
    var $newLinkLi = $('<li></li>').append($addFollowerButton);
    
    jQuery(document).ready(function() {
        $collectionHolder = $('ul.followers');
        $collectionHolder.append($newLinkLi);
        $collectionHolder.data('index', $collectionHolder.find('input').length);
        $addFollowerButton.on('click', function(e) {
            addFollowerForm($collectionHolder, $newLinkLi);
        });
    });
    ```
3. В классе `App\Manager\UserManager`
    1. исправляем метод `getUpdateForm`
        ```php
        public function getUpdateForm(int $userId): ?FormInterface
        {
            /** @var UserRepository $userRepository */
            $userRepository = $this->entityManager->getRepository(User::class);
            /** @var User $user */
            $user = $userRepository->find($userId);
            if ($user === null) {
                return null;
            }
 
            return $this->formFactory->createBuilder(FormType::class, SaveUserDTO::fromEntity($user))
                ->add('login', TextType::class)
                ->add('password', PasswordType::class)
                ->add('age', IntegerType::class)
                ->add('isActive', CheckboxType::class, ['required' => false])
                ->add('submit', SubmitType::class)
                ->add('followers', CollectionType::class, [
                    'entry_type' => LinkedUserType::class,
                    'entry_options' => ['label' => false],
                    'allow_add' => true,
                ])
                ->setMethod('PATCH')
                ->getForm();
        }
        ```
    2. исправляем метод `updateUserFromDTO`
        ```php
        public function updateUserFromDTO(int $userId, SaveUserDTO $userDTO): bool
        {
            /** @var UserRepository $userRepository */
            $userRepository = $this->entityManager->getRepository(User::class);
            /** @var User $user */
            $user = $userRepository->find($userId);
            if ($user === null) {
                return false;
            }
    
            foreach ($userDTO->followers as $followerData) {
                $followerUserDTO = new SaveUserDTO($followerData);
                /** @var User $followerUser */
                if (isset($followerData['id'])) {
                    $followerUser = $userRepository->find($followerData['id']);
                    if ($followerUser !== null) {
                        $this->saveUserFromDTO($followerUser, $followerUserDTO);
                    }
                } else {
                    $followerUser = new User();
                    $this->saveUserFromDTO($followerUser, $followerUserDTO);
                    $user->addFollower($followerUser);
                }
            }
    
            return $this->saveUserFromDTO($user, $userDTO);
        }
        ```
4. В браузере переходим по адресу `http://localhost:7777/api/v1/user/form/ID`, где ID - идентификатор
   пользователя-автора из предыдущего раздела
5. Нажимаем новую кнопку Add a follower, добавляем данные нового пользователя, нажимаем на Submit и видим в БД, что
   создался новый пользователь и подписался на автора

## Добавляем возможность удаления отношений в форму редактирования

1. Исправляем файл `public/user-form.js`
    ```javascript
    function addFollowerForm($collectionHolder, $newLinkLi) {
        var prototype = $collectionHolder.data('prototype');
        var index = $collectionHolder.data('index');
        var newForm = prototype;
        newForm = newForm.replace(/__name__/g, index);
        $collectionHolder.data('index', index + 1);
        var $newFormLi = $('<li></li>').append(newForm);
        addFollowerFormDeleteLink($newFormLi)
        $newLinkLi.before($newFormLi);
    }
    
    function addFollowerFormDeleteLink($followerFormLi) {
        var $removeFormButton = $('<button type="button" class="btn btn-danger remove_follower_link">Delete this follower</button>');
        $followerFormLi.append($removeFormButton);
    
        $removeFormButton.on('click', function(e) {
            $followerFormLi.remove();
        });
    }
    
    var $collectionHolder;
    var $addFollowerButton = $('<button type="button" class="btn-info btn add_follower_link">Add a follower</button>');
    var $newLinkLi = $('<li></li>').append($addFollowerButton);
    
    jQuery(document).ready(function() {
        $collectionHolder = $('ul.followers');
        $collectionHolder.find('span.follower_form').each(function() {
            addFollowerFormDeleteLink($(this));
        });
        $collectionHolder.append($newLinkLi);
        $collectionHolder.data('index', $collectionHolder.find('input').length);
        $addFollowerButton.on('click', function(e) {
            addFollowerForm($collectionHolder, $newLinkLi);
        });
    });
    ```
2. Исправляем файл `src/templates/form.twig`
    ```html
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        {% block head_css %}
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/1.1.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
        {% endblock %}
    </head>
    <body>
    {% form_theme form 'bootstrap_4_layout.html.twig' %}
    <div style="width:50%;margin-left:10px;margin-top:10px">
        {{ form_start(form) }}
        {{ form_row(form.login) }}
        {{ form_row(form.password) }}
        {{ form_row(form.age) }}
        {{ form_row(form.isActive) }}
    
        <h3>Followers</h3>
        <ul class="followers" data-prototype='{{ form_widget(form.followers.vars.prototype)|e('html_attr') }}'>
            {% for follower in form.followers %}
                <span class="follower_form">
                {{ form_row(follower.id) }}
                <li>{{ form_row(follower.login) }}</li>
                <li>{{ form_row(follower.password) }}</li>
                <li>{{ form_row(follower.age) }}</li>
                <li>{{ form_row(follower.isActive) }}</li>
                </span>
            {% endfor %}
        </ul>
        {{ form_row(form.submit) }}
        {{ form_end(form, {'render_rest': false}) }}
    </div>
    {% block head_js %}
        <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
        <script src="/user-form.js"></script>
    {% endblock %}
    </body>
    </html>
    ```
3. В классе `App\Entity\User` добавляем метод `resetFollowers`
    ```php
    public function resetFollowers(): void
    {
        $this->followers = new ArrayCollection();
    }
    ```
4. В классе `App\Manager\UserManager` исправляем метод `updateUserFromDTO`
    ```php
    public function updateUserFromDTO(int $userId, SaveUserDTO $userDTO): bool
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return false;
        }
        
        $user->resetFollowers();

        foreach ($userDTO->followers as $followerData) {
            $followerUserDTO = new SaveUserDTO($followerData);
            /** @var User $followerUser */
            if (isset($followerData['id'])) {
                $followerUser = $userRepository->find($followerData['id']);
                if ($followerUser !== null) {
                    $this->saveUserFromDTO($followerUser, $followerUserDTO);
                }
            } else {
                $followerUser = new User();
                $this->saveUserFromDTO($followerUser, $followerUserDTO);
            }
            $user->addFollower($followerUser);
        }

        return $this->saveUserFromDTO($user, $userDTO);
    }
    ```
5. В браузере переходим по адресу `http://localhost:7777/api/v1/user/form/ID`, где ID - идентификатор
   пользователя-автора из предыдущего раздела
6. Нажимаем новую кнопку Delete this follower, нажимаем на Submit и видим в БД, что подписка на автора у удалённого 
   пользователя не удалилась

## Добавляем очистку данных формы   

1. В классе `App\Controller\Api\v1\UserController` исправляем метод `updateUserFormAction`
    ```php
    #[Route(path: '/form/{id}', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function updateUserFormAction(Request $request, int $id): Response
    {
        $form = $this->userManager->getUpdateForm($id);
        if ($form === null) {
            return new JsonResponse(['message' => "User with ID $id not found"], 404);
        }

        /** @var SaveUserDTO $formData */
        $formData = $form->getData();
        $formData->followers = [];
        $form->setData($formData);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->userManager->updateUserFromDTO($id, $form->getData());

            return new JsonResponse(['success' => $result], $result ? 200 : 400);
        }
        $content = $this->twig->render('form.twig', [
            'form' => $form->createView(),
        ]);

        return new Response($content);
    }
    ```
2. В браузере переходим по адресу `http://localhost:7777/api/v1/user/form/ID`, где ID - идентификатор
   пользователя-автора из предыдущего раздела
3. Нажимаем новую кнопку Delete this follower, нажимаем на Submit и видим в БД, что подписка на автора у удалённого
   пользователя удалилась
