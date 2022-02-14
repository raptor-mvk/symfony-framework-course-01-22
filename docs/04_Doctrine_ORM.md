# Doctrine ORM

Запускаем docker-контейнеры командой `docker-compose up -d`

## Устанавливаем требуемые пакеты, добавляем контейнер с СУБД, добавляем миграцию и выполняем её

1. Заходим в контейнер `php` командой `docker exec -it php sh`. Дальнейшие команды выполняются из контейнера
2. Устанавливаем Doctrine ORM командой `composer require doctrine/orm`
3. Устанавливаем пакет для работы с Doctrine командой `composer require doctrine/doctrine-bundle`
4. Устанавливаем пакет для работы с миграциями командой `composer require doctrine/doctrine-migrations-bundle`
5. Выходим из контейнера и останавливаем все контейнеры командой `docker-compose stop`
6. Создаём файл `migrations/Version20210212155210.php`
    ```php
    <?php
    
    namespace DoctrineMigrations;
    
    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\Migrations\AbstractMigration;
    
    class Version20210212155210 extends AbstractMigration
    {
        public function up(Schema $schema): void
        {
            $this->addSql('CREATE TABLE tweet (id BIGSERIAL NOT NULL, author_id BIGINT DEFAULT NULL, text VARCHAR(140) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE TABLE "user" (id BIGSERIAL NOT NULL, login VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('ALTER TABLE tweet ADD CONSTRAINT tweet__author_id__fk FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('CREATE TABLE subscription (id BIGSERIAL NOT NULL, author_id BIGINT DEFAULT NULL, follower_id BIGINT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX subscription__author_id__idx ON subscription (author_id)');
            $this->addSql('CREATE INDEX subscription__follower_id__idx ON subscription (follower_id)');
            $this->addSql('CREATE UNIQUE INDEX subscription__author_id__follower_id__uniq ON subscription (author_id, follower_id)');
            $this->addSql('ALTER TABLE subscription ADD CONSTRAINT subscription__author_id__fk FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('ALTER TABLE subscription ADD CONSTRAINT subscription__follower_id__fk FOREIGN KEY (follower_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('CREATE TABLE author_follower (author_id BIGINT DEFAULT NULL, follower_id BIGINT DEFAULT NULL, PRIMARY KEY(author_id, follower_id))');
            $this->addSql('CREATE INDEX author_follower__author_id__idx ON author_follower (author_id)');
            $this->addSql('CREATE INDEX author_follower__follower_id__idx ON author_follower (follower_id)');
            $this->addSql('CREATE UNIQUE INDEX author_folllower__author_id__follower_id__uniq ON author_follower (author_id, follower_id)');
            $this->addSql('ALTER TABLE author_follower ADD CONSTRAINT author_follower__author_id__fk FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('ALTER TABLE author_follower ADD CONSTRAINT author_follower__follower_id__fk FOREIGN KEY (follower_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('CREATE TABLE feed (id BIGSERIAL NOT NULL, reader_id BIGINT DEFAULT NULL, tweets JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE UNIQUE INDEX feed__reader_id__uniq ON feed (reader_id)');
            $this->addSql('ALTER TABLE feed ADD CONSTRAINT feed__reader_id__fk FOREIGN KEY (reader_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        }
    
        public function down(Schema $schema): void
        {
            $this->addSql('DROP TABLE author_follower');
            $this->addSql('DROP TABLE subscription');
            $this->addSql('DROP TABLE tweet');
            $this->addSql('DROP TABLE "user"');
            $this->addSql('DROP TABLE feed');
        }
    }
    ```
7. В файле `docker-compose.yml`
    1. Удаляем созданные рецептом сервис и volume
    2. В секцию `services` добавляем
        ```yaml
        postgres:
          image: postgres:12
          ports:
            - 15432:5432
          container_name: 'postgresql'
          working_dir: /app
          restart: always
          environment:
            POSTGRES_DB: 'twitter'
            POSTGRES_USER: 'user'
            POSTGRES_PASSWORD: 'password'
          volumes:
            - dump:/app/dump
            - postgresql:/var/lib/postgresql/data
        ```
    3. Добавляем секцию `volumes`
        ```yaml
        volumes:
          dump:
          postgresql:
        ```
8. Удаляем `docker-compose.override.yml`
9. В файле `.env` настраиваем переменную `DATABASE_URL` для доступа к БД
    ```shell
    DATABASE_URL="postgresql://user:password@postgresql:5432/twitter?serverVersion=12&charset=utf8"
    ```
10. Перезапускаем контейнеры командой `docker-compose up -d`
11. Заходим в контейнер `php` командой `docker exec -it php sh`, дальнейшие команды выполняются из контейнера 
12. В контейнере выполняем команду `php bin/console doctrine:migrations:migrate`
13. Подключаемся к БД и проверяем, что таблицы были созданы

## Добавляем сущность (Entity) пользователя и метод его создания

1. В файле `config/packages/doctrine.yaml` исправляем секцию `doctrine.orm.mappings`
    ```yaml
    App:
        is_bundle: false
        dir: '%kernel.project_dir%/src/Entity'
        prefix: 'App\Entity'
        alias: App
        type: attribute
    ```
2. Исправляем класс `App\Entity\User`
    ```php
    <?php
    
    namespace App\Entity;
    
    use DateTime;
    use Doctrine\ORM\Mapping as ORM;
    
    #[ORM\Table(name: '`user`')]
    #[ORM\Entity]
    class User
    {
        #[ORM\Column(name: 'id', type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private ?int $id = null;
    
        #[ORM\Column(type: 'string', length: 32, nullable: false)]
        private string $login;
    
        #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
        private DateTime $createdAt;
    
        #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
        private DateTime $updatedAt;
    
        public function getId(): int
        {
            return $this->id;
        }
    
        public function setId(int $id): void
        {
            $this->id = $id;
        }
    
        public function getLogin(): string
        {
            return $this->login;
        }
    
        public function setLogin(string $login): void
        {
            $this->login = $login;
        }
    
        public function getCreatedAt(): DateTime {
            return $this->createdAt;
        }
    
        public function setCreatedAt(): void {
            $this->createdAt = new DateTime();
        }
    
        public function getUpdatedAt(): DateTime {
            return $this->updatedAt;
        }
    
        public function setUpdatedAt(): void {
            $this->updatedAt = new DateTime();
        }
    
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'login' => $this->login,
                'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
                'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            ];
        }
    }
    ```
3. Исправляем класс `App\Manager\UserManager`
    ```php
    <?php
    
    namespace App\Manager;
    
    use App\Entity\User;
    
    class UserManager
    {
        public function create(string $login): User
        {
            $user = new User();
            $user->setLogin($login);
            $user->setCreatedAt();
            $user->setUpdatedAt();
    
            return $user;
        }
    }
    ```
4. Исправляем в классе `App\Controller\WorldController` метод `hello`
    ```php
    public function hello(): Response
    {
        $user = $this->userManager->create('My user');
        
        return $this->json($user->toArray());
    }
    ```
5. Заходим по адресу `http://localhost:7777/world/hello`, видим данные нашего пользователя и `id = null`

## Добавляем сохранение пользователя в БД

1. Исправляем класс `App\Manager\UserManager`
    ```php
    <?php
    
    namespace App\Manager;
    
    use App\Entity\User;
    use Doctrine\ORM\EntityManagerInterface;
    
    class UserManager
    {
        private EntityManagerInterface $entityManager;
    
        public function __construct(EntityManagerInterface $entityManager)
        {
            $this->entityManager = $entityManager;
        }
    
        public function create(string $login): User
        {
            $user = new User();
            $user->setLogin($login);
            $user->setCreatedAt();
            $user->setUpdatedAt();
            $this->entityManager->persist($user);
            $this->entityManager->flush();
    
            return $user;
        }
    }
    ```
2. Заходим по адресу `http://localhost:7777/world/hello`, видим данные нашего пользователя с заполненным id
3. Проверяем, что запись в БД также создалась

## Добавляем сущность твита и создаём пару твитов для пользователя

1. Создаём класс `App\Entity\Tweet`
    ```php
    <?php
    
    namespace App\Entity;
    
    use DateTime;
    use Doctrine\ORM\Mapping as ORM;
    
    #[ORM\Table(name: 'tweet')]
    #[ORM\Entity]
    class Tweet
    {
        #[ORM\Column(name: 'id', type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private ?int $id = null;
    
        #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'tweets')]
        #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
        private User $author;
    
        #[ORM\Column(type: 'string', length: 140, nullable: false)]
        private string $text;
    
        #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
        private DateTime $createdAt;
    
        #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
        private DateTime $updatedAt;
    
        public function getId(): int
        {
            return $this->id;
        }
    
        public function setId(int $id): void
        {
            $this->id = $id;
        }
    
        public function getAuthor(): User
        {
            return $this->author;
        }
    
        public function setAuthor(User $author): void
        {
            $this->author = $author;
        }
    
        public function getText(): string
        {
            return $this->text;
        }
    
        public function setText(string $text): void
        {
            $this->text = $text;
        }
    
        public function getCreatedAt(): DateTime {
            return $this->createdAt;
        }
    
        public function setCreatedAt(): void {
            $this->createdAt = new DateTime();
        }
    
        public function getUpdatedAt(): DateTime {
            return $this->updatedAt;
        }
    
        public function setUpdatedAt(): void {
            $this->updatedAt = new DateTime();
        }
    
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'login' => $this->author->getLogin(),
                'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
                'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            ];
        }
    }
    ```
2. Исправляем класс `App\Entity\User`
    1. Добавляем новое поле `tweets` и конструктор
        ```php
        #[ORM\OneToMany(mappedBy: 'author', targetEntity: 'Tweet')]
        private Collection $tweets;
    
        public function __construct()
        {
            $this->tweets = new ArrayCollection();
        }
        ```
    2. Исправляем метод `toArray`
        ```php
        public function toArray(): array
        {
            return [
               'id' => $this->id,
               'login' => $this->login,
               'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
               'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
               'tweets' => array_map(static fn(Tweet $tweet) => $tweet->toArray(), $this->tweets->toArray()),
            ];
        }        
        ```
3. Создаём класс `App\Manager\TweetManager`
    ```php
    <?php
    
    namespace App\Manager;
    
    use App\Entity\Tweet;
    use App\Entity\User;
    use Doctrine\ORM\EntityManagerInterface;
    
    class TweetManager
    {
        private EntityManagerInterface $entityManager;
    
        public function __construct(EntityManagerInterface $entityManager)
        {
            $this->entityManager = $entityManager;
        }
    
        public function postTweet(User $author, string $text): void
        {
            $tweet = new Tweet();
            $tweet->setAuthor($author);
            $tweet->setText($text);
            $tweet->setCreatedAt();
            $tweet->setUpdatedAt();
            $this->entityManager->persist($tweet);
            $this->entityManager->flush();
        }
    }
    ```
4. Создаём класс `App\Service\UserBuilderService`
    ```php
    <?php
    
    namespace App\Service;
    
    use App\Entity\User;
    use App\Manager\TweetManager;
    use App\Manager\UserManager;
    
    class UserBuilderService
    {
    private TweetManager $tweetManager;
    
        private UserManager $userManager;
    
        public function __construct(TweetManager $tweetManager, UserManager $userManager)
        {
            $this->tweetManager = $tweetManager;
            $this->userManager = $userManager;
        }
    
        /**
         * @param string[] $texts
         */
        public function createUserWithTweets(string $login, array $texts): User
        {
            $user = $this->userManager->create($login);
            foreach ($texts as $text) {
                $this->tweetManager->postTweet($user, $text);
            }
    
            return $user;
        }
    }
    ```
5. Исправляем класс `App\Controller\WorldController`
    ```php
    <?php
    
    namespace App\Controller;
    
    use App\Service\UserBuilderService;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    
    class WorldController extends AbstractController
    {
        private UserBuilderService $userBuilderService;
    
        public function __construct(UserBuilderService $userBuilderService)
        {
            $this->userBuilderService = $userBuilderService;
        }
    
        public function hello(): Response
        {
            $user = $this->userBuilderService->createUserWithTweets(
                'J.R.R. Tolkien',
                ['The Hobbit', 'The Lord of the Rings']
            );
    
            return $this->json($user->toArray());
        }
    }
    ```
6. Заходим по адресу `http://localhost:7777/world/hello`, видим данные нашего пользователя с пустым списком твитов
7. Проверяем, что в БД твиты появились

## Исправляем проблему с помощью очистки EntityManager

1. Добавляем в класс `App\Manager\UserManager` два новых метода:
    ```php
    public function clearEntityManager(): void
    {
        $this->entityManager->clear();
    }

    public function findUser(int $id): ?User
    {
        $repository = $this->entityManager->getRepository(User::class);
        $user = $repository->find($id);

        return $user instanceof User ? $user : null;
    }
    ```
2. Исправляем в классе `App\Service\UserBuilderService` метод `createUserWithTweets`
    ```php
    /**
     * @param string[] $texts
     */
    public function createUserWithTweets(string $login, array $texts): User
    {
        $user = $this->userManager->create($login);
        foreach ($texts as $text) {
            $this->tweetManager->postTweet($user, $text);
        }
        $userId = $user->getId();
        $this->userManager->clearEntityManager();

        return $this->userManager->findUser($userId);
    }
    ```
3. Заходим по адресу `http://localhost:7777/world/hello`, видим твиты появились в данных пользователя

## Исправляем проблему с помощью работы с коллекцией

1. В классе `App\Entity\User` добавляем новый метод `addTweet`
    ```php
    public function addTweet(Tweet $tweet): void
    {
        if (!$this->tweets->contains($tweet)) {
            $this->tweets->add($tweet);
        }
    }
    ```
2. В классе `App\Manager\TweetManager` исправляем метод `postTweet`
    ```php
    public function postTweet(User $author, string $text): void
    {
        $tweet = new Tweet();
        $tweet->setAuthor($author);
        $tweet->setText($text);
        $tweet->setCreatedAt();
        $tweet->setUpdatedAt();
        $author->addTweet($tweet);
        $this->entityManager->persist($tweet);
        $this->entityManager->flush();
    }
    ```
3. В классе `App\Service\UserBuilderService` возвращаем предыдущую версию метода `createUserWithTweets`
    ```php
    /**
     * @param string[] $texts
     */
    public function createUserWithTweets(string $login, array $texts): User
    {
        $user = $this->userManager->create($login);
        foreach ($texts as $text) {
            $this->tweetManager->postTweet($user, $text);
        }

        return $user;
    }
    ```
4. Заходим по адресу `http://localhost:7777/world/hello`, видим, что твиты в данных всё ещё присутствуют
   
## Добавляем самоссылающуюся связь многие-ко-многим к сущности пользователя

1. Исправляем класс `App\Entity\User`
    1. Добавляем два новых поля `followers` и `authors` и инициализируем их в конструкторе
        ```php
        #[ORM\ManyToMany(targetEntity: 'User', mappedBy: 'followers')]
        private Collection $authors;

        #[ORM\ManyToMany(targetEntity: 'User', inversedBy: 'authors')]
        #[ORM\JoinTable(name: 'author_follower')]
        #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
        #[ORM\InverseJoinColumn(name: 'follower_id', referencedColumnName: 'id')]
        private Collection $followers;
    
        public function __construct()
        {
            $this->tweets = new ArrayCollection();
            $this->authors = new ArrayCollection();
            $this->followers = new ArrayCollection();
        }
        ```
    2. Добавляем метод `addFollower`
        ```php
        public function addFollower(User $follower): void
        {
            if (!$this->followers->contains($follower)) {
                $this->followers->add($follower);
            }
        }
        ```
    3. Исправляем метод `toArray`
        ```php
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'login' => $this->login,
                'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
                'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
                'tweets' => array_map(static fn(Tweet $tweet) => $tweet->toArray(), $this->tweets->toArray()),
                'followers' => array_map(static fn(User $user) => $user->getLogin(), $this->followers->toArray()),
                'authors' => array_map(static fn(User $user) => $user->getLogin(), $this->authors->toArray()),
            ];
        }
        ```
2. Добавляем в класс `App\Manager\UserManager` новый метод `subscribeUser`
    ```php
    public function subscribeUser(User $author, User $follower): void
    {
        $author->addFollower($follower);
        $this->entityManager->flush();
    }
    ```
3. Добавляем в класс `App\Service\UserBuilderService` новый метод `createUserWithFollower`
    ```php
    /**
     * @return User[]
     */
    public function createUserWithFollower(string $login, string $followerLogin): array
    {
        $user = $this->userManager->create($login);
        $follower = $this->userManager->create($followerLogin);
        $this->userManager->subscribeUser($user, $follower);

        return [$user, $follower];
    }
    ```
4. В классе `App\Controller\WorldController` исправляем метод `hello`
    ```php
    public function hello(): Response
    {
        $users = $this->userBuilderService->createUserWithFollower(
            'J.R.R. Tolkien',
            'Ivan Ivanov'
        );

        return $this->json(array_map(static fn(User $user) => $user->toArray(), $users));
    }
    ```
5. Заходим по адресу `http://localhost:7777/world/hello`, видим, что поле `followers` заполнилось, а вот поле
   `authors` - нет

## Исправляем возникшую проблему

1. Добавляем в класс `App\Entity\User` новый метод `addAuthor`
    ```php
    public function addAuthor(User $author): void
    {
        if (!$this->authors->contains($author)) {
            $this->authors->add($author);
        }
    }
    ```
1. Исправляем в классе `App\Manager\UserManager` метод `subscribeUser`
    ```php
    public function subscribeUser(User $author, User $follower): void
    {
        $author->addFollower($follower);
        $follower->addAuthor($author);
        $this->entityManager->flush();
    }
    ```
1. Заходим по адресу `http://localhost:7777/world/hello`, видим, что оба поля `followers` и `authors` заполнились

## Добавляем сущность подписки (альтернативная реализация связи многие-ко-многим)

1. Добавляем класс `App\Entity\Subscription`
    ```php
    <?php
    
    namespace App\Entity;
    
    use DateTime;
    use Doctrine\ORM\Mapping as ORM;
    
    #[ORM\Table(name: 'subscription')]
    #[ORM\Entity]
    class Subscription
    {
        #[ORM\Column(name: 'id', type: 'bigint', unique: true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private int $id;
    
        #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'subscriptionFollowers')]
        #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
        private User $author;
    
        #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'subscriptionAuthors')]
        #[ORM\JoinColumn(name: 'follower_id', referencedColumnName: 'id')]
        private User $follower;
    
        #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
        private DateTime $createdAt;
    
        #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
        private DateTime $updatedAt;
    
        public function getId(): int
        {
            return $this->id;
        }
    
        public function setId(int $id): void
        {
            $this->id = $id;
        }
    
        public function getAuthor(): User
        {
            return $this->author;
        }
    
        public function setAuthor(User $author): void
        {
            $this->author = $author;
        }
    
        public function getFollower(): User
        {
            return $this->follower;
        }
    
        public function setFollower(User $follower): void
        {
            $this->follower = $follower;
        }
    
        public function getCreatedAt(): DateTime {
            return $this->createdAt;
        }
    
        public function setCreatedAt(): void {
            $this->createdAt = new DateTime();
        }
    
        public function getUpdatedAt(): DateTime {
            return $this->updatedAt;
        }
    
        public function setUpdatedAt(): void {
            $this->updatedAt = new DateTime();
        }
    }
    ```
2. Исправляем класс `App\Entity\User`
    1. Добавляем два новых поля `subscriptionAuthors` и `subscriptionFollowers` и инициализируем их в конструкторе
        ```php
        #[ORM\OneToMany(mappedBy: 'follower', targetEntity: 'Subscription')]
        private Collection $subscriptionAuthors;

        #[ORM\OneToMany(mappedBy: 'author', targetEntity: 'Subscription')]
        private Collection $subscriptionFollowers;
        
        public function __construct()
        {
            $this->tweets = new ArrayCollection();
            $this->authors = new ArrayCollection();
            $this->followers = new ArrayCollection();
            $this->subscriptionAuthors = new ArrayCollection();
            $this->subscriptionFollowers = new ArrayCollection();
        }
        ```
    2. Добавляем два новых метода `addSubscriptionAuthor` и `addSubscriptionFollower`
        ```php
        public function addSubscriptionAuthor(Subscription $subscription): void
        {
            if (!$this->subscriptionAuthors->contains($subscription)) {
                $this->subscriptionAuthors->add($subscription);
            }
        }
        
        public function addSubscriptionFollower(Subscription $subscription): void
        {
            if (!$this->subscriptionFollowers->contains($subscription)) {
                $this->subscriptionFollowers->add($subscription);
            }
        }
        ```
    3. Исправляем метод `toArray`
        ```php
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'login' => $this->login,
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
                        'subscriptionId' => $subscription->getId(),
                        'userId' => $subscription->getFollower()->getId(),
                        'login' => $subscription->getFollower()->getLogin(),
                    ],
                    $this->subscriptionFollowers->toArray()
                ),
                'subscriptionAuthors' => array_map(
                    static fn(Subscription $subscription) => [
                        'subscriptionId' => $subscription->getId(),
                        'userId' => $subscription->getAuthor()->getId(),
                        'login' => $subscription->getAuthor()->getLogin(),
                    ],
                    $this->subscriptionAuthors->toArray()
                ),
            ];
        }
        ```
3. Создаём класс `App\Manager\SubscriptionManager`
    ```php
    <?php
    
    namespace App\Manager;
    
    use App\Entity\Subscription;
    use App\Entity\User;
    use Doctrine\ORM\EntityManagerInterface;
    
    class SubscriptionManager
    {
        private EntityManagerInterface $entityManager;
    
        public function __construct(EntityManagerInterface $entityManager)
        {
            $this->entityManager = $entityManager;
        }
        
        public function addSubscription(User $author, User $follower): void
        {
            $subscription = new Subscription();
            $subscription->setAuthor($author);
            $subscription->setFollower($follower);
            $subscription->setCreatedAt();
            $subscription->setUpdatedAt();
            $author->addSubscriptionFollower($subscription);
            $follower->addSubscriptionAuthor($subscription);
            $this->entityManager->persist($subscription);
            $this->entityManager->flush();
        }
    
    }
    ```
4. В классе `App\Service\UserBuilderService`
    1. Добавляем инъекцию `App\Manager\SubscriptionManager`
        ```php
        private SubscriptionManager $subscriptionManager;
    
        public function __construct(TweetManager $tweetManager, UserManager $userManager, SubscriptionManager $subscriptionManager)
        {
            $this->tweetManager = $tweetManager;
            $this->userManager = $userManager;
            $this->subscriptionManager = $subscriptionManager;
        }
        ```
    2. Исправляем метод `createUserWithFollower`
        ```php
        /**
         * @return User[]
         */
        public function createUserWithFollower(string $login, string $followerLogin): array
        {
            $user = $this->userManager->create($login);
            $follower = $this->userManager->create($followerLogin);
            $this->userManager->subscribeUser($user, $follower);
            $this->subscriptionManager->addSubscription($user, $follower);
    
            return [$user, $follower];
        }
        ```
5. Заходим по адресу `http://localhost:7777/world/hello`, видим, что значения полей `subscriptionId` и `userId`
   отличаются

## Добавляем поиск по логину

1. Добавляем в класс `App\Manager\UserManager` метод `findUsersByLogin`
    ```php
    /**
     * @return User[]
     */
    public function findUsersByLogin(string $name): array
    {
        return $this->entityManager->getRepository(User::class)->findBy(['login' => $name]);
    }
    ```
2. Исправляем класс `App\Controller\WorldController` (возвращаем зависимость от `App\Manager\UserManager`)
    ```php
    <?php
    
    namespace App\Controller;
    
    use App\Entity\User;
    use App\Manager\UserManager;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    
    class WorldController extends AbstractController
    {
        private UserManager $userManager;
    
        public function __construct(UserManager $userManager)
        {
            $this->userManager = $userManager;
        }
    
        public function hello(): Response
        {
            $users = $this->userManager->findUsersByLogin('Ivan Ivanov');
    
            return $this->json(array_map(static fn(User $user) => $user->toArray(), $users));
        }
    }
    ```
3. Заходим по адресу `http://localhost:7777/world/hello`, видим список добавленных нами ранее пользователей

## Добавляем поиск по критерию

1. Добавляем в класс `App\Manager\UserManager` метод `findUsersByCriteria`
    ```php
    /**
     * @return User[]
     */
    public function findUsersByCriteria(string $login): array
    {
        $criteria = Criteria::create();
        /** @noinspection NullPointerExceptionInspection */
        $criteria->andWhere(Criteria::expr()->eq('login', $login));
        /** @var EntityRepository $repository */
        $repository = $this->entityManager->getRepository(User::class);

        return $repository->matching($criteria)->toArray();
    }
    ```
2. В классе `App\Controller\WorldController` исправляем метод `hello`
    ```php
    public function hello(): Response
    {
        $users = $this->userManager->findUsersByCriteria('J.R.R. Tolkien');

        return $this->json(array_map(static fn(User $user) => $user->toArray(), $users));
    }
    ```
3. Заходим по адресу `http://localhost:7777/world/hello`, видим список добавленных нами ранее пользователей
