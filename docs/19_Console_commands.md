# Консольные команды в Symfony

Запускаем контейнеры командой `docker-compose up -d`

## Добавляем команду

1. Добавляем класс `App\Command\AddFollowersCommand`
    ```php
    <?php
    
    namespace App\Command;
    
    use App\Service\SubscriptionService;
    use App\Manager\UserManager;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    
    class AddFollowersCommand extends Command
    {    
        private UserManager $userManager;
    
        private SubscriptionService $subscriptionService;
    
        public function __construct(UserManager $userManager, SubscriptionService $subscriptionService)
        {
            parent::__construct();
            $this->userManager = $userManager;
            $this->subscriptionService = $subscriptionService;
        }
    
        protected function configure(): void
        {
            $this->setName('followers:add')
                ->setDescription('Adds followers to author')
                ->addArgument('authorId', InputArgument::REQUIRED, 'ID of author')
                ->addArgument('count', InputArgument::REQUIRED, 'How many followers should be added');
        }
    
        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            $authorId = (int)$input->getArgument('authorId');
            $user = $this->userManager->findUserById($authorId);
            if ($user === null) {
                $output->write("<error>User with ID $authorId doesn't exist</error>\n");
                return self::FAILURE;
            }
            $count = (int)$input->getArgument('count');
            if ($count < 0) {
                $output->write("<error>Count should be positive integer</error>\n");
                return self::FAILURE;
            }
            $result = $this->subscriptionService->addFollowers($user, "Reader #{$authorId}", $count);
            $output->write("<info>$result followers were created</info>\n");
            return self::SUCCESS;
        }
    }
    ```
2. Подключаемся в контейнер командой `docker exec -it php sh`. Дальнейшие команды выполняем из контейнера
3. Выполняем команду `php bin/console`, видим в списке нашу команду
4. Выполняем команду `php bin/console followers:add --help`, видим описание команды и её аргументы
5. Выполняем команду `php bin/console followers:add`, видим ошибку
6. Выполняем команду `php bin/console followers:add 1 100`, видим результат, проверяем, что в БД данные появились

## Делаем аргумент необязательным

1. В файле `App\Command\AddFollowersCommand`
    1. Добавляем константу `DEFAULT_FOLLOWERS` со значением 100
    2. Исправляем код метода `configure`
        ```php
        protected function configure(): void
        {
            $this->setName('followers:add')
                ->setDescription('Adds followers to author')
                ->addArgument('authorId', InputArgument::REQUIRED, 'ID of author')
                ->addArgument('count', InputArgument::OPTIONAL, 'How many followers should be added');
        }
        ```
    3. Исправляем код метода `execute`
        ```php
        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            $authorId = (int)$input->getArgument('authorId');
            $user = $this->userManager->findUserById($authorId);
            if ($user === null) {
                $output->write("<error>User with ID $authorId doesn't exist</error>\n");
                return self::FAILURE;
            }
            $count = (int)($input->getArgument('count') ?? self::DEFAULT_FOLLOWERS);
            if ($count < 0) {
                $output->write("<error>Count should be positive integer</error>\n");
                return self::FAILURE;
            }
            $result = $this->subscriptionService->addFollowers($user, "Reader #{$authorId}", $count);
            $output->write("<info>$result followers were created</info>\n");
            return self::SUCCESS;
        }
        ```
2. Выполняем команду `php bin/console followers:add --help`, видим изменившееся описание команды
3. Выполняем команду `php bin/console followers:add 1`, видим ошибку, связанную с дублированием логинов

## Добавляем опцию

1. В классе `App\Command\AddFollowersCommand`
    1. Добавляем константу `DEFAULT_LOGIN_PREFIX` со значением `Reader #`
    2. Исправляем метод `configure`
        ```php
        protected function configure(): void
        {
            $this->setName('followers:add')
                ->setDescription('Adds followers to author')
                ->addArgument('authorId', InputArgument::REQUIRED, 'ID of author')
                ->addArgument('count', InputArgument::OPTIONAL, 'How many followers should be added')
                ->addOption('login', 'l', InputOption::VALUE_REQUIRED, 'Follower login prefix');
        }
        ```
    3. Исправляем метод `execute`
        ```php
        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            $authorId = (int)$input->getArgument('authorId');
            $user = $this->userManager->findUserById($authorId);
            if ($user === null) {
                $output->write("<error>User with ID $authorId doesn't exist</error>\n");
                return self::FAILURE;
            }
            $count = (int)($input->getArgument('count') ?? self::DEFAULT_FOLLOWERS);
            if ($count < 0) {
                $output->write("<error>Count should be positive integer</error>\n");
                return self::FAILURE;
            }
            $login = $input->getOption('login') ?? self::DEFAULT_LOGIN_PREFIX;
            $result = $this->subscriptionService->addFollowers($user, $login.$authorId, $count);
            $output->write("<info>$result followers were created</info>\n");
            return self::SUCCESS;
        }
        ```
2. Выполняем команду `php bin/console followers:add --help`, видим изменившееся описание команды
3. Выполняем команды и смотрим на результат
    ```shell script
    php bin/console followers:add 1 --login=login
    php bin/console followers:add 1 --login new_login
    php bin/console followers:add 1 --loginsome_login
    php bin/console followers:add 1 -lwrong_login
    php bin/console followers:add 1 -l=other_login
    php bin/console followers:add 1 -l short_login
    ````

## Прячем команду из списка

1. В классе `App\Command\AddFollowersCommand` исправляем метод `configure`
    ```php
    protected function configure(): void
    {
        $this->setName('followers:add')
            ->setHidden(true)
            ->setDescription('Adds followers to author')
            ->addArgument('authorId', InputArgument::REQUIRED, 'ID of author')
            ->addArgument('count', InputArgument::OPTIONAL, 'How many followers should be added')
            ->addOption('login', 'l', InputOption::VALUE_REQUIRED, 'Follower login prefix');
    }
    ```
2. Выполняем команду `php bin/console`, видим, что нашей команды больше нет в списке
3. Выполняем команду `php bin/console followers:add 1 --login=hidden`, видим, что команда всё ещё работает

## Блокируем параллельный запуск команд

1. Устанавливаем пакет `symfony/lock`
2. В классе `App\Command\AddFollowersCommand`
    1. Добавляем трейт `LockableTrait`
    2. Исправляем метод `execute`
        ```php
        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            if (!$this->lock()) {
                $output->writeln('<info>Command is already running.</info>');
                return self::SUCCESS;
            }
            sleep(100);
            $authorId = (int)$input->getArgument('authorId');
            $user = $this->userManager->findUserById($authorId);
            if ($user === null) {
                $output->write("<error>User with ID $authorId doesn't exist</error>\n");
                return self::FAILURE;
            }
            $count = (int)($input->getArgument('count') ?? self::DEFAULT_FOLLOWERS);
            if ($count < 0) {
                $output->write("<error>Count should be positive integer</error>\n");
                return self::FAILURE;
            }
            $login = $input->getOption('login') ?? self::DEFAULT_LOGIN_PREFIX;
            $result = $this->subscriptionService->addFollowers($user, $login.$authorId, $count);
            $output->write("<info>$result followers were created</info>\n");
            return self::SUCCESS;
        }
        ```
3. Выполняем команды и видим, что блокировка работает
    ```shell script
    php bin/console followers:add 1 &
    php bin/console followers:add 1
    ```

## Добавляем прогрессбар   

1. В классе `App\Command\AddFollowersCommand` исправляем метод `execute`
    ```php
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('<info>Command is already running.</info>');
            return self::SUCCESS;
        }
        $authorId = (int)$input->getArgument('authorId');
        $user = $this->userManager->findUserById($authorId);
        if ($user === null) {
            $output->write("<error>User with ID $authorId doesn't exist</error>\n");
            return self::FAILURE;
        }
        $count = (int)($input->getArgument('count') ?? self::DEFAULT_FOLLOWERS);
        if ($count < 0) {
            $output->write("<error>Count should be positive integer</error>\n");
            return self::FAILURE;
        }
        $loginPrefix = $input->getOption('login') ?? self::DEFAULT_LOGIN_PREFIX;
        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();
        $createdFollowers = 0;
        for ($i = 0; $i < $count; $i++) {
            $login = $loginPrefix.$authorId."_#$i";
            $password = $login;
            $age = $i;
            $isActive = true;
            $phone = '+'.str_pad((string)abs(crc32($login)), 10, '0');
            $email = "$login@gmail.com";
            $preferred = random_int(0, 1) === 1 ? User::EMAIL_NOTIFICATION : User::SMS_NOTIFICATION;
            $data = compact('login', 'password', 'age', 'isActive', 'phone', 'email', 'preferred');
            $followerId = $this->userManager->saveUserFromDTO(new User(), new SaveUserDTO($data));
            if ($followerId !== null) {
                $this->subscriptionService->subscribe($user->getId(), $followerId);
                $createdFollowers++;
                usleep(100000);
                $progressBar->advance();
            }
        }
        $output->write("<info>$createdFollowers followers were created</info>\n");
        $progressBar->finish();

        return self::SUCCESS;
    }
    ```
2. Выполняем команду `php bin/console followers:add 1 -lmy_login`, видим заполняющийся прогрессбар
   
## Добавляем подписку на событие запуска команды

1. В классе `App\Command\AddFollowersCommand`
    1. Добавляем публичную константу `FOLLOWERS_ADD_COMMAND_NAME` со значением `followers:add`
    2. Исправляем метод `configure`
        ```php
        protected function configure(): void
        {
            $this->setName(self::FOLLOWERS_ADD_COMMAND_NAME)
                ->setHidden(true)
                ->setDescription('Adds followers to author')
                ->addArgument('authorId', InputArgument::REQUIRED, 'ID of author')
                ->addArgument('count', InputArgument::OPTIONAL, 'How many followers should be added')
                ->addOption('login', 'l', InputOption::VALUE_REQUIRED, 'Follower login prefix');
        }
        ```
2. Добавляем класс `App\EventSubscriber\CommandEventSubscriber`
    ```php
    <?php
    
    namespace App\EventSubscriber;
    
    use App\Command\AddFollowersCommand;
    use Symfony\Component\Console\ConsoleEvents;
    use Symfony\Component\Console\Event\ConsoleCommandEvent;
    use Symfony\Component\Console\Question\ConfirmationQuestion;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    
    class CommandEventSubscriber implements EventSubscriberInterface
    {
        public static function getSubscribedEvents(): array
        {
            return [
                ConsoleEvents::COMMAND => [['onCommand', 0]],
            ];
        }
    
        public function onCommand(ConsoleCommandEvent $event): void
        {
            $command = $event->getCommand();
            if ($command !== null && $command->getName() === AddFollowersCommand::FOLLOWERS_ADD_COMMAND_NAME) {
                $input = $event->getInput();
                $output = $event->getOutput();
                $helper = $command->getHelper('question');
                $question = new ConfirmationQuestion('Are you sure want to execute this command?(y/n)', false);
                if (!$helper->ask($input, $output, $question)) {
                    $event->disableCommand();
                }
            }
        }
    }
    ```
3. Выполняем команду `php bin/console followers:add 1`, видим дополнительный вопрос, возникающий по событию

## Добавляем тесты для команды

1. В классе `App\Command\AddFollowersCommand` исправляем метод `execute`
    ```php
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $authorId = (int)$input->getArgument('authorId');
        $user = $this->userManager->findUserById($authorId);
        if ($user === null) {
            $output->write("<error>User with ID $authorId doesn't exist</error>\n");
            return self::FAILURE;
        }
        $count = (int)($input->getArgument('count') ?? self::DEFAULT_FOLLOWERS);
        if ($count < 0) {
            $output->write("<error>Count should be positive integer</error>\n");
            return self::FAILURE;
        }
        $login = $input->getOption('login') ?? self::DEFAULT_LOGIN_PREFIX;
        $result = $this->subscriptionService->addFollowers($user, $login.$authorId, $count);
        $output->write("<info>$result followers were created</info>\n");

        return self::SUCCESS;
    }
    ```
2. Удаляем класс `App\EventSubscriber\CommandEventSubscriber`
3. Добавляем класс `UnitTests\Command\AddFollowersCommandTest`
    ```php
    <?php
    
    namespace UnitTests\Command;
    
    use App\Manager\UserManager;
    use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
    use Mockery;
    use Symfony\Bundle\FrameworkBundle\Console\Application;
    use Symfony\Component\Console\Tester\CommandTester;
    use Symfony\Component\Form\FormFactoryInterface;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
    use UnitTests\FixturedTestCase;
    use UnitTests\Fixtures\MultipleUsersFixture;
    
    class AddFollowersCommandTest extends FixturedTestCase
    {
        private const COMMAND = 'followers:add';
    
        private static Application $application;
    
        public function setUp(): void
        {
            parent::setUp();
    
            self::$application = new Application(self::$kernel);
            $this->addFixture(new MultipleUsersFixture());
            $this->executeFixtures();
        }
    
        public function executeDataProvider(): array
        {
            return [
                'positive' => [20, 'login', "20 followers were created\n"],
                'zero' => [0, 'other_login', "0 followers were created\n"],
                'default' => [null, 'login3', "100 followers were created\n"],
                'negative' => [-1, 'login_too', "Count should be positive integer\n"],
            ];
        }
    
        /**
         * @dataProvider executeDataProvider
         */
        public function testExecuteReturnsResult(?int $followersCount, string $login, string $expected): void
        {
            $command = self::$application->find(self::COMMAND);
            $commandTester = new CommandTester($command);
    
            /** @var UserPasswordHasherInterface $encoder */
            $encoder = self::getContainer()->get('security.password_hasher');
            /** @var PaginatedFinderInterface $finder */
            $finder = Mockery::mock(PaginatedFinderInterface::class);
            /** @var FormFactoryInterface $formFactory */
            $formFactory = self::getContainer()->get('form.factory');
            $userManager = new UserManager($this->getDoctrineManager(), $formFactory, $encoder, $finder);
            $author = $userManager->findUserByLogin(MultipleUsersFixture::PRATCHETT);
            $params = ['authorId' => $author->getId()];
            $options = ['login' => $login];
            if ($followersCount !== null) {
                $params['count'] = $followersCount;
            }
            $commandTester->execute($params, $options);
            $output = $commandTester->getDisplay();
            static::assertSame($expected, $output);
        }
    }
    ```
4. Запускаем тесты командой `./vendor/bin/simple-phpunit tests/unit/Command/AddFollowersCommandTest.php`

## Делаем интерактивный аргумент

1. В классе `App\Command\AddFollowersCommand`
    1. Исправляем метод `configure`
        ```php
        protected function configure(): void
        {
            $this->setName(self::FOLLOWERS_ADD_COMMAND_NAME)
                ->setHidden(true)
                ->setDescription('Adds followers to author')
                ->addArgument('authorId', InputArgument::REQUIRED, 'ID of author')
                ->addOption('login', 'l', InputOption::VALUE_REQUIRED, 'Follower login prefix');
        }
        ```
    2. Исправляем метод `execute`
        ```php
        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            $authorId = (int)$input->getArgument('authorId');
            $user = $this->userManager->findUserById($authorId);
            if ($user === null) {
                $output->write("<error>User with ID $authorId doesn't exist</error>\n");
                return self::FAILURE;
            }
            $helper = $this->getHelper('question');
            $question = new Question('How many followers you want to add?', self::DEFAULT_FOLLOWERS);
            $count = (int)$helper->ask($input, $output, $question);
            if ($count < 0) {
                $output->write("<error>Count should be positive integer</error>\n");
                return self::FAILURE;
            }
            $login = $input->getOption('login') ?? self::DEFAULT_LOGIN_PREFIX;
            $result = $this->subscriptionService->addFollowers($user, $login.$authorId, $count);
            $output->write("<info>$result followers were created</info>\n");
    
            return self::SUCCESS;
        }
        ```
2. Выполняем команду `php bin/console followers:add 1`, видим дополнительный вопрос по количеству добавляемых фолловеров
3. В классе `UnitTests\Command\AddFollowersCommandTest` исправляем метод `testExecuteReturnsResult`
    ```php
    /**
     * @dataProvider executeDataProvider
     */
    public function testExecuteReturnsResult(?int $followersCount, string $login, string $expected): void
    {
        $command = self::$application->find(self::COMMAND);
        $commandTester = new CommandTester($command);

        /** @var UserPasswordHasherInterface $encoder */
        $encoder = self::getContainer()->get('security.password_hasher');
        /** @var PaginatedFinderInterface $finder */
        $finder = Mockery::mock(PaginatedFinderInterface::class);
        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::getContainer()->get('form.factory');
        $userManager = new UserManager($this->getDoctrineManager(), $formFactory, $encoder, $finder);
        $author = $userManager->findUserByLogin(MultipleUsersFixture::PRATCHETT);
        $params = ['authorId' => $author->getId()];
        $inputs = $followersCount === null ? ["\n"] : ["$followersCount\n"];
        $commandTester->setInputs($inputs);
        $commandTester->execute($params);
        $output = $commandTester->getDisplay();

        static::assertSame($expected, $output);
    }
    ```
4. Запускаем тесты командой `./vendor/bin/simple-phpunit tests/unit/Command/AddFollowerCommandTest.php`, видим ошибки

## Исправляем ошибку в тестах

1. В классе `UnitTests\Command\AddFollowersCommand` исправляем метод `testExecuteReturnsResult`
    ```php
    /**
     * @dataProvider executeDataProvider
     */
    public function testExecuteReturnsResult(?int $followersCount, string $login, string $expected): void
    {
        $command = self::$application->find(self::COMMAND);
        $commandTester = new CommandTester($command);

        /** @var UserPasswordHasherInterface $encoder */
        $encoder = self::getContainer()->get('security.password_hasher');
        /** @var PaginatedFinderInterface $finder */
        $finder = Mockery::mock(PaginatedFinderInterface::class);
        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::getContainer()->get('form.factory');
        $userManager = new UserManager($this->getDoctrineManager(), $formFactory, $encoder, $finder);
        $author = $userManager->findUserByLogin(MultipleUsersFixture::PRATCHETT);
        $params = ['authorId' => $author->getId()];
        $options = ['login' => $login];
        $inputs = $followersCount === null ? ["\n"] : ["$followersCount\n"];
        $commandTester->setInputs($inputs);
        $commandTester->execute($params, $options);
        $output = $commandTester->getDisplay();

        static::assertStringEndsWith($expected, $output);
    }
    ```
2. Ещё раз запускаем тесты командой `./vendor/bin/simple-phpunit tests/unit/Command/AddFollowerCommandTest.php`, видим,
   что тесты проходят
