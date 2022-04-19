# Интеграционное тестирование

Запускаем контейнеры командой `docker-compose up -d`

# Устанавливаем Codeception

1. Подключаемся в контейнер командой `docker exec -it php sh`. Дальнейшие команды выполняем из контейнера
2. Переименовываем директорию `tests/unit` в `tests/phpunit`
3. В файле `composer.json` исправляем секцию `autoload-dev`
    ```json
    "autoload-dev": {
        "psr-4": {
            "UnitTests\\": "tests/phpunit"
        }
    },
    ```
4. Выполняем команду `composer dump-autoload`
5. Проверяем, что тесты работают командой `./vendor/bin/simple-phpunit`
6. Устанавливаем пакеты `codeception/codeception`, `codeception/module-phpbrowser:^2.0`,
`codeception/module-symfony:^2.0`, `codeception/module-doctrine2:^2.0`, `codeception/module-asserts:^2.0`,
`codeception/module-datafactory`, `codeception/module-rest:^2.0` **в dev-режиме**
7. Запускаем тесты командой `./vendor/bin/codecept run`, видим, что наши юнит-тесты не запускаются
8. Проверяем, что тесты всё ещё работают командой `./vendor/bin/simple-phpunit`

## Переносим "настоящие" unit-тесты в Codeception 

1. Исправляем секцию `params` в `codeception.yml`
    ```yaml
    params:
        - .env
        - .env.test
    ```
2. Исправляем файл `.env.test`, заменяя значение переменной `SYMFONY_DEPRECATIONS_HELPER` на `disabled`
3. В файле `composer.json` исправляем секцию `autoload-dev`
    ```json
    "autoload-dev": {
        "psr-4": {
            "UnitTests\\": "tests/phpunit",
            "CodeceptionUnitTests\\": "tests/unit"
        }
    },
    ```
4. Выполняем команду `composer dump-autoload`
5. Создаём класс `CodeceptionUnitTests\Entity\TweetTest` (копируем содержимое класса `UnitTests\Entity\TweetTest` и
заменяем базовый класс)
    ```php
    <?php
    
    namespace CodeceptionUnitTests\Entity;
    
    use App\Entity\Tweet;
    use App\Entity\User;
    use Codeception\Test\Unit;
    use DateTime;
    use Symfony\Bridge\PhpUnit\ClockMock;
    
    class TweetTest extends Unit
    {
        private const NOW_TIME = '@now';
    
        public function tweetDataProvider(): array
        {
            $expectedPositive = [
                'id' => 5,
                'author' => 'Terry Pratchett',
                'text' => 'The Colour of Magic',
                'createdAt' => self::NOW_TIME,
            ];
            $positiveTweet = $this->addAuthor($this->makeTweet($expectedPositive), $expectedPositive);
            $expectedNoAuthor = [
                'id' => 30,
                'author' => null,
                'text' => 'Unknown book',
                'createdAt' => self::NOW_TIME,
            ];
            $expectedNoCreatedAt = [
                'id' => 42,
                'author' => 'Douglas Adams',
                'text' => 'The Hitchhiker\'s Guide to the Galaxy',
                'createdAt' => '',
            ];
            return [
                'positive' => [
                    $positiveTweet,
                    $expectedPositive,
                    0,
                ],
                'no author' => [
                    $this->makeTweet($expectedNoAuthor),
                    $expectedNoAuthor,
                    0
                ],
                'no createdAt' => [
                    $this->addAuthor($this->makeTweet($expectedNoCreatedAt), $expectedNoCreatedAt),
                    $expectedNoCreatedAt,
                    null
                ],
                'positive with delay' => [
                    $positiveTweet,
                    $expectedPositive,
                    2
                ],
            ];
        }
    
        /**
         * @dataProvider tweetDataProvider
         * @group time-sensitive
         */
        public function testToFeedReturnsCorrectValues(Tweet $tweet, array $expected, ?int $delay = null): void
        {
            ClockMock::register(Tweet::class);
            if ($expected['createdAt'] === self::NOW_TIME) {
                $expected['createdAt'] = DateTime::createFromFormat('U',(string)time())->format('Y-m-d h:i:s');
            }
            $tweet = $this->setCreatedAtWithDelay($tweet, $delay);
            $actual = $tweet->toFeed();
    
            static::assertSame($expected, $actual, 'Tweet::toFeed should return correct result');
        }
    
        private function makeTweet(array $data): Tweet
        {
            $tweet = new Tweet();
            $tweet->setId($data['id']);
            $tweet->setText($data['text']);
    
            return $tweet;
        }
    
        private function addAuthor(Tweet $tweet, array $data): Tweet
        {
            $author = new User();
            $author->setLogin($data['author']);
            $tweet->setAuthor($author);
    
            return $tweet;
        }
    
        private function setCreatedAtWithDelay(Tweet $tweet, ?int $delay = null): Tweet
        {
            if ($delay !== null) {
                \sleep($delay);
                $tweet->setCreatedAt();
            }
    
            return $tweet;
        }
    }
    ```
6. Создаём класс `CodeceptionUnitTests\Service\SubscriptionServiceTest` (копируем содержимое класса
`UnitTests\Service\SubscriptionServiceTest` и тоже заменяем базовый класс)
    ```php
    <?php
    
    namespace CodeceptionUnitTests\Service;
    
    use App\Entity\User;
    use App\Manager\SubscriptionManager;
    use App\Manager\UserManager;
    use App\Service\SubscriptionService;
    use Codeception\Test\Unit;
    use Doctrine\ORM\EntityManagerInterface;
    use Doctrine\ORM\EntityRepository;
    use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
    use Mockery;
    use Mockery\MockInterface;
    use Symfony\Component\Form\FormFactoryInterface;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
    
    class SubscriptionServiceTest extends Unit
    {
        use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    
        /** @var EntityManagerInterface|MockInterface */
        private static $entityManager;
        private const CORRECT_AUTHOR = 1;
        private const CORRECT_FOLLOWER = 2;
        private const INCORRECT_AUTHOR = 3;
        private const INCORRECT_FOLLOWER = 4;
    
        public static function setUpBeforeClass(): void
        {
            /** @var MockInterface|EntityRepository $repository */
            $repository = Mockery::mock(EntityRepository::class);
            $repository->shouldReceive('find')->with(self::CORRECT_AUTHOR)->andReturn(new User());
            $repository->shouldReceive('find')->with(self::INCORRECT_AUTHOR)->andReturn(null);
            $repository->shouldReceive('find')->with(self::CORRECT_FOLLOWER)->andReturn(new User());
            $repository->shouldReceive('find')->with(self::INCORRECT_FOLLOWER)->andReturn(null);
            /** @var MockInterface|EntityManagerInterface $repository */
            self::$entityManager = Mockery::mock(EntityManagerInterface::class);
            self::$entityManager->shouldReceive('getRepository')->with(User::class)->andReturn($repository);
            self::$entityManager->shouldReceive('persist');
            self::$entityManager->shouldReceive('flush');
        }
    
        public function subscribeDataProvider(): array
        {
            return [
                'both correct' => [self::CORRECT_AUTHOR, self::CORRECT_FOLLOWER, true],
                'author incorrect' => [self::INCORRECT_AUTHOR, self::CORRECT_FOLLOWER, false],
                'follower incorrect' => [self::CORRECT_AUTHOR, self::INCORRECT_FOLLOWER, false],
                'both incorrect' => [self::INCORRECT_AUTHOR, self::INCORRECT_FOLLOWER, false],
            ];
        }
    
        /**
         * @dataProvider subscribeDataProvider
         */
        public function testSubscribeReturnsCorrectResult(int $authorId, int $followerId, bool $expected): void
        {
            usleep(400000);
            /** @var UserPasswordHasherInterface $encoder */
            $encoder = Mockery::mock(UserPasswordHasherInterface::class);
            /** @var PaginatedFinderInterface $finder */
            $finder = Mockery::mock(PaginatedFinderInterface::class);
            /** @var FormFactoryInterface $formFactory */
            $formFactory = Mockery::mock(FormFactoryInterface::class);
            $userManager = new UserManager(self::$entityManager, $formFactory, $encoder, $finder);
            $subscriptionManager = new SubscriptionManager(self::$entityManager);
            $subscriptionService = new SubscriptionService($userManager, $subscriptionManager);
    
            $actual = $subscriptionService->subscribe($authorId, $followerId);
    
            static::assertSame($expected, $actual, 'Subscribe should return correct result');
        }
    
        public function testSubscribeReturnsAfterFirstError(): void
        {
            /** @var MockInterface|EntityRepository $repository */
            $repository = Mockery::mock(EntityRepository::class);
            $repository->shouldReceive('find')->with(self::INCORRECT_AUTHOR)->andReturn(null)->once();
            $repository->shouldReceive('find')->with(self::INCORRECT_FOLLOWER)->never();
            /** @var MockInterface|EntityManagerInterface $repository */
            self::$entityManager = Mockery::mock(EntityManagerInterface::class);
            self::$entityManager->shouldReceive('getRepository')->with(User::class)->andReturn($repository);
            self::$entityManager->shouldReceive('persist');
            self::$entityManager->shouldReceive('flush');
            /** @var UserPasswordHasherInterface $encoder */
            $encoder = Mockery::mock(UserPasswordHasherInterface::class);
            /** @var PaginatedFinderInterface $finder */
            $finder = Mockery::mock(PaginatedFinderInterface::class);
            /** @var FormFactoryInterface $formFactory */
            $formFactory = Mockery::mock(FormFactoryInterface::class);
            $userManager = new UserManager(self::$entityManager, $formFactory, $encoder, $finder);
            $subscriptionManager = new SubscriptionManager(self::$entityManager);
            $subscriptionService = new SubscriptionService($userManager, $subscriptionManager);
    
            $subscriptionService->subscribe(self::INCORRECT_AUTHOR, self::INCORRECT_FOLLOWER);
        }
    }
    ```
7. Запускаем тесты командой `./vendor/bin/codecept run unit`, видим, что юнит-тесты переносятся практически без
изменений из phpunit

## Переносим тест команды

1. Создаём класс `CodeceptionUnitTests\Command\AddFollowersCommandTest` (копируем содержимое класса
`UnitTests\Command\AddFollowersCommandTest`)
    ```php
    <?php
   
    namespace CodeceptionUnitTests\Command;
   
    use Symfony\Bundle\FrameworkBundle\Console\Application;
    use Symfony\Component\Console\Tester\CommandTester;
    use UnitTests\FixturedTestCase;
    use UnitTests\Fixtures\MultipleUsersFixture;
   
    class AddFollowersCommandTest extends FixturedTestCase
    {
        private const COMMAND = 'followers:add';
   
        /** @var Application */
        private static $application;
   
        public function setUp(): void
        {
            parent::setUp();
   
            self::$application = new Application(self::$kernel);
            $this->addFixture(new MultipleUsersFixture());
        }
   
        public function executeDataProvider(): array
        {
            return [
                'positive' => [20, "20 followers were created\n"],
                'zero' => [0, "0 followers were created\n"],
                'default' => [null, "100 followers were created\n"],
                'negative' => [-1, "Count should be positive integer\n"],
            ];
        }
   
        /**
         * @dataProvider executeDataProvider
         */
        public function testExecuteReturnsResult(?int $followersCount, string $expected): void
        {
            $command = self::$application->find(self::COMMAND);
            $commandTester = new CommandTester($command);
            $userService = self::$container->get('App\Service\UserService');
            $author = $userService->findByLogin(MultipleUsersFixture::PRATCHETT);
            $params = ['authorId' => $author->getId()];
            $inputs = $followersCount === null ? ["\n"] : ["$followersCount\n"];
            $commandTester->setInputs($inputs);
            $commandTester->execute($params);
            $output = $commandTester->getDisplay();
            static::assertStringEndsWith($expected, $output);
        }
    }
    ```
2. Запускаем тесты командой `./vendor/bin/codecept run unit`, видим ошибки

## Делаем тест команды интеграционным

1. Выполняем команду `./vendor/bin/codecept clean`, чтобы очистить результаты неудачного вызова тестов
2. В файле `composer.json` исправляем секцию `autoload-dev`
    ```json
    "autoload-dev": {
        "psr-4": {
            "UnitTests\\": "tests/phpunit",
            "CodeceptionUnitTests\\": "tests/unit",
            "IntegrationTests\\": "tests/functional"
        }
    },
    ```
3. Выполняем команду `composer dump-autoload`
4. В файле `tests/functional.suite.yml`
    1. раскомментируем модуль `Doctrine2`
        ```yaml
        - Doctrine2:
            depends: Symfony
            cleanup: true
        ```
    2. добаляем модуль `Asserts`
        ```yaml
        - Asserts
        ```
5. Перегенерируем акторы командой `./vendor/bin/codecept build`
7. Переносим класс `CodeceptionUnitTests\Command\AddFollowersCommandTest` в namespace `IntegerationTests`,
   переименовываем в `AddFollowersCommandCest` и исправляем:
    ```php
    <?php
   
    namespace IntegrationTests\Command;
   
    use App\Entity\User;
    use App\Tests\FunctionalTester;
    use Codeception\Example;
    use UnitTests\Fixtures\MultipleUsersFixture;
   
    class AddFollowersCommandCest
    {
        private const COMMAND = 'followers:add';
       
        public function executeDataProvider(): array
        {
            return [
                'positive' => ['followersCount' => 20, 'expected' => "20 followers were created\n"],
                'zero' => ['followersCount' => 0, 'expected' => "0 followers were created\n"],
                'default' => ['followersCount' => null, 'expected' => "100 followers were created\n"],
                'negative' => ['followersCount' => -1, 'expected' => "Count should be positive integer\n"],
            ];
        }
   
        /**
         * @dataProvider executeDataProvider
         */
        public function testExecuteReturnsResult(FunctionalTester $I, Example $example): void
        {
            $I->loadFixtures(MultipleUsersFixture::class);
            $author = $I->grabEntityFromRepository(User::class, ['login' => MultipleUsersFixture::PRATCHETT]);
            $params = ['authorId' => $author->getId()];
            $inputs = $example['followersCount'] === null ? ["\n"] : [$example['followersCount']."\n"];
            $output = $I->runSymfonyConsoleCommand(self::COMMAND, $params, $inputs);
            $I->assertStringEndsWith($example['expected'], $output);
        }
    }
    ```
9. Очищаем в тестовой БД таблицы `subscription`, `tweet`, `user`
8. Запускаем тесты командой `./vendor/bin/codecept run functional`, видим ошибки
 
## Чиним тест команды

1. Исправляем класс `IntegrationTests\Command\AddFollowersCommandCest`
    ```php
    <?php
   
    namespace IntegrationTests\Command;
   
    use App\Entity\User;
    use App\Tests\FunctionalTester;
    use Codeception\Example;
    use UnitTests\Fixtures\MultipleUsersFixture;
   
    class AddFollowersCommandCest
    {
        private const COMMAND = 'followers:add';
      
        public function executeDataProvider(): array
        {
            return [
                'positive' => ['followersCount' => 20, 'expected' => "20 followers were created\n", 'exitCode' => 0],
                'zero' => ['followersCount' => 0, 'expected' => "0 followers were created\n", 'exitCode' => 0],
                'default' => ['followersCount' => null, 'expected' => "100 followers were created\n", 'exitCode' => 0],
                'negative' => ['followersCount' => -1, 'expected' => "Count should be positive integer\n", 'exitCode' => 1],
            ];
        }
   
        /**
         * @dataProvider executeDataProvider
         */
        public function testExecuteReturnsResult(FunctionalTester $I, Example $example): void
        {
            $I->loadFixtures(MultipleUsersFixture::class);
            $author = $I->grabEntityFromRepository(User::class, ['login' => MultipleUsersFixture::PRATCHETT]);
            $params = ['authorId' => $author->getId()];
            $inputs = $example['followersCount'] === null ? ["\n"] : [$example['followersCount']."\n"];
            $output = $I->runSymfonyConsoleCommand(self::COMMAND, $params, $inputs, $example['exitCode']);
            $I->assertStringEndsWith($example['expected'], $output);
        }
    }
    ```
2. Запускаем тесты командой `./vendor/bin/codecept run functional`, видим, что всё работает
 
## Заменяем фикстуры фабриками

1. Создаём файл `tests/_support/Helper/Factories.php`
    ```php
    <?php
   
    namespace App\Tests\Helper;
   
    use App\Entity\User;
    use Codeception\Module;
    use Codeception\Module\DataFactory;
    use League\FactoryMuffin\Faker\Facade;
   
    class Factories extends Module
    {
        public function _beforeSuite($settings = []): void
        {
            /** @var DataFactory $factory */
            $factory = $this->getModule('DataFactory');
   
            $factory->_define(
                User::class,
                [
                    'login' => Facade::text(20)(),
                    'password' => Facade::text(20)(),
                    'age' => Facade::randomNumber(2)(),
                    'is_active' => true,
                    'phone' => '+0'.Facade::randomNumber(9, true)(),
                    'email' => Facade::email()(),
                    'preferred' => 'email',
                ]
            );
        }
    }
    ```
2. В файле `tests/functional.suite.yml` подключаем модули `DataFactory` и `Factories`
    ```yaml
    - DataFactory:
        depends: Doctrine2
        cleanup: true
    - \App\Tests\Helper\Factories
    ```
3. Исправляем в классе `IntegrationTests\Command\AddFollowersCommandCest` метод `testExecuteReturnsResult`
    ```php
    /**
     * @dataProvider executeDataProvider
     */
    public function testExecuteReturnsResult(FunctionalTester $I, Example $example): void
    {
        $author = $I->have(User::class);
        $params = ['authorId' => $author->getId()];
        $inputs = $example['followersCount'] === null ? ["\n"] : [$example['followersCount']."\n"];
        $output = $I->runSymfonyConsoleCommand(self::COMMAND, $params, $inputs, $example['exitCode']);
        $I->assertStringEndsWith($example['expected'], $output);
    }
    ```
4. Перегенерируем акторы командой `./vendor/bin/codecept build`
5. Запускаем тесты командой `./vendor/bin/codecept run functional`, видим, что всё работает

## Добавляем интеграционные тесты ленты

1. Исправляем файл `tests/_support/Helper/Factories.php`
    ```php
    <?php
   
    namespace App\Tests\Helper;
   
    use App\Entity\Subscription;
    use App\Entity\Tweet;
    use App\Entity\User;
    use Codeception\Module;
    use Codeception\Module\DataFactory;
    use League\FactoryMuffin\Faker\Facade;
   
    class Factories extends Module
    {
        public function _beforeSuite($settings = [])
        {
            /** @var DataFactory $factory */
            $factory = $this->getModule('DataFactory');
   
            $factory->_define(
                User::class,
                [
                    'login' => Facade::text(20)(),
                    'password' => Facade::text(20)(),
                    'age' => Facade::randomNumber(2)(),
                    'is_active' => true,
                    'phone' => '+0'.Facade::randomNumber(9, true)(),
                    'email' => Facade::email()(),
                    'preferred' => 'email',
                ]
            );
            $factory->_define(
                Tweet::class,
                [
                    'author' => 'entity|'.User::class,
                ]
            );
            $factory->_define(
                Subscription::class,
                [
                    'author' => 'entity|'.User::class,
                    'follower' => 'entity|'.User::class,
                ]
            );
        }
    }
    ```
2. Создаём класс `IntegrationTests\Service\FeedServiceCest`
    ```php
    <?php
   
    namespace IntegrationTests\Service;
   
    use App\Entity\Subscription;
    use App\Entity\Tweet;
    use App\Entity\User;
    use App\Tests\FunctionalTester;
    use Codeception\Example;
   
    class FeedServiceCest
    {
        private const PRATCHETT_AUTHOR = 'Terry Pratchett';
        private const TOLKIEN_AUTHOR = 'John R.R. Tolkien';
        private const CARROLL_AUTHOR = 'Lewis Carrol';
        private const TOLKIEN1_TEXT = 'Hobbit';
        private const PRATCHETT1_TEXT = 'Colours of Magic';
        private const TOLKIEN2_TEXT = 'Lord of the Rings';
        private const PRATCHETT2_TEXT = 'Soul Music';
        private const CARROL1_TEXT = 'Alice in Wonderland';
        private const CARROL2_TEXT = 'Through the Looking-Glass';
   
        public function getFeedFromTweetsDataProvider(): array
        {
            return [
                'all authors, all tweets' => [
                    'authors' => [self::TOLKIEN_AUTHOR, self::CARROLL_AUTHOR, self::PRATCHETT_AUTHOR],
                    'tweetsCount' => 6,
                    'expected' => [
                        self::CARROL2_TEXT,
                        self::CARROL1_TEXT,
                        self::TOLKIEN2_TEXT,
                        self::TOLKIEN1_TEXT,
                        self::PRATCHETT2_TEXT,
                        self::PRATCHETT1_TEXT,
                    ]
                ]
            ];
        }
   
        public function _before(FunctionalTester $I)
        {
            $pratchett = $I->have(User::class, ['login' => self::PRATCHETT_AUTHOR]);
            $tolkien = $I->have(User::class, ['login' => self::TOLKIEN_AUTHOR]);
            $carroll = $I->have(User::class, ['login' => self::CARROLL_AUTHOR]);
            $I->have(Tweet::class, ['author' => $pratchett, 'text' => self::PRATCHETT1_TEXT]);
            sleep(1);
            $I->have(Tweet::class, ['author' => $pratchett, 'text' => self::PRATCHETT2_TEXT]);
            sleep(1);
            $I->have(Tweet::class, ['author' => $tolkien, 'text' => self::TOLKIEN1_TEXT]);
            sleep(1);
            $I->have(Tweet::class, ['author' => $tolkien, 'text' => self::TOLKIEN2_TEXT]);
            sleep(1);
            $I->have(Tweet::class, ['author' => $carroll, 'text' => self::CARROL1_TEXT]);
            sleep(1);
            $I->have(Tweet::class, ['author' => $carroll, 'text' => self::CARROL2_TEXT]);
        }
   
        /**
         * @dataProvider getFeedFromTweetsDataProvider
         */
        public function testGetFeedFromTweetsReturnsCorrectResult(FunctionalTester $I, Example $example): void
        {
            $follower = $I->have(User::class);
            foreach ($example['authors'] as $authorLogin) {
                $author = $I->grabEntityFromRepository(User::class, ['login' => $authorLogin]);
                $I->have(Subscription::class, ['author' => $author, 'follower' => $follower]);
            }
            $feedService = $I->grabService('App\Service\FeedService');
   
            $feed = $feedService->getFeedFromTweets($follower->getId(), $example['tweetsCount']);
   
            $I->assertSame($example['expected'], array_map(static fn(Tweet $tweet) => $tweet->getText(), $feed));
        }
    }
    ```
3. Запускаем тесты командой `./vendor/bin/codecept run functional`, видим, что тесты проходят
 
## Добавляем системный тест

1. Исправляем секцию `enabled` в файле `tests/acceptance.suite.yml` (отключаем модуль `PhpBrowser` и добавляем модуль
`REST`)
    ```yaml
    enabled:
        - REST:
            url: http://nginx:80
            depends: PhpBrowser
            part: Json
        - \App\Tests\Helper\Acceptance
    ```
2. Перегенерируем акторы командой `./vendor/bin/codecept build`
3. В файле `composer.json` исправляем секцию `autoload-dev`
    ```json
    "autoload-dev": {
        "psr-4": {
            "UnitTests\\": "tests/phpunit",
            "CodeceptionUnitTests\\": "tests/unit",
            "IntegrationTests\\": "tests/functional",
            "AcceptanceTests\\": "tests/acceptance"
        }
    },
    ```
4. Выполняем команду `composer dump-autoload`
5. Добавляем класс `AcceptanceTests\Api\v1\UserCest`
    ```php
    <?php
    
    namespace AcceptanceTests\Api\v1;
    
    use App\Tests\AcceptanceTester;
    use Codeception\Util\HttpCode;
    
    class UserCest
    {
        public function testAddUserAction(AcceptanceTester $I): void
        {
            $I->sendPost('/api/v4/save-user', [
                'login' => 'my_user',
                'password' => 'my_password',
                'roles' => '["ROLE_USER"]',
                'age' => 23,
                'isActive' => 'true',
            ]);
            $I->canSeeResponseCodeIs(HttpCode::OK);
            $I->canSeeResponseMatchesJsonType(['id' => 'integer:>0']);
        }
    }
    ```
6. Запускаем тесты командой `./vendor/bin/codecept run acceptance`, видим, что всё работает

## Включаем аутентификацию 

1. Исправляем файл `config/packages/security.yaml`
    ```yaml
    security:
        enable_authenticator_manager: true
        providers:
            users_in_memory:
                memory:
                    users:
                        admin:
                            password: 'my_pass'
                            roles: 'ROLE_ADMIN'
                        user:
                            password: 'other_pass'
                            roles: 'ROLE_USER'
   
        password_hashers:
            App\Entity\User: auto
            Symfony\Component\Security\Core\User\User: plaintext
   
        firewalls:
            dev:
                pattern: ^/(_(profiler|wdt)|css|images|js)/
                security: false
            main:
                http_basic:
                    realm: Secured Area
                lazy: true
                provider: users_in_memory
   
        access_control:
            - { path: ^/api/v4/save-user, roles: ROLE_ADMIN }
    ```
2. Запускаем тесты командой `./vendor/bin/codecept run acceptance`, видим ошибку

## Исправляем тест
 
1. В файл `tests/_support/AcceptanceTester.php` добавляем два метода
    ```
    public function amAdmin(): void
    {
        $this->amHttpAuthenticated('admin', 'my_pass');
    }
    
    public function amUser(): void
    {
        $this->amHttpAuthenticated('user', 'other_pass');
    }
    ```
2. Исправляем класс `AcceptanceTests\Api\v1\UserCest`
    ```php
    <?php
    
    namespace AcceptanceTests\Api\v1;
    
    use App\Tests\AcceptanceTester;
    use Codeception\Util\HttpCode;
    
    class UserCest
    {
        public function testAddUserActionForAdmin(AcceptanceTester $I): void
        {
            $I->amAdmin();
            $I->sendPost('/api/v4/save-user', $this->getAddUserParams());
            $I->canSeeResponseCodeIs(HttpCode::OK);
            $I->canSeeResponseMatchesJsonType(['id' => 'integer:>0']);
        }
    
        public function testAddUserActionForUser(AcceptanceTester $I): void
        {
            $I->amUser();
            $I->sendPost('/api/v4/save-user', $this->getAddUserParams());
            $I->canSeeResponseContains('Access Denied.');
            $I->canSeeResponseCodeIs(HttpCode::INTERNAL_SERVER_ERROR);
        }
    
        private function getAddUserParams(): array
        {
            return [
                'login' => 'other_user',
                'password' => 'other_password',
                'roles' => '["ROLE_USER"]',
                'age' => 23,
                'isActive' => 'true',
            ];
        }
    }
    ```
3. Запускаем тесты командой `./vendor/bin/codecept run acceptance`, видим, что они проходят   
