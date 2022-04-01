# Очереди: расширенные возможности

Запускаем контейнеры командой `docker-compose up -d`

## Добавляем функционал ленты и нотификаций

1. Добавляем класс `App\Entity\Feed`
    ```php
    <?php
    
    namespace App\Entity;
    
    use DateTime;
    use Doctrine\ORM\Mapping as ORM;
    use Gedmo\Mapping\Annotation as Gedmo;
    
    #[ORM\Table(name: 'feed')]
    #[ORM\UniqueConstraint(columns: ['reader_id'])]
    #[ORM\Entity]
    class Feed
    {
        #[ORM\Column(name: 'id', type: 'bigint', unique:true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private int $id;
    
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(name: 'reader_id', referencedColumnName: 'id')]
        private User $reader;
    
        #[ORM\Column(type: 'json', nullable: true)]
        private ?array $tweets;
    
        #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
        #[Gedmo\Timestampable(on: 'create')]
        private DateTime $createdAt;

        #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
        #[Gedmo\Timestampable(on: 'update')]
        private DateTime $updatedAt;
    
        public function getId(): int
        {
            return $this->id;
        }
    
        public function setId(int $id): void
        {
            $this->id = $id;
        }
    
        public function getReader(): User
        {
            return $this->reader;
        }
    
        public function setReader(User $reader): void
        {
            $this->reader = $reader;
        }
    
        public function getTweets(): ?array
        {
            return $this->tweets;
        }
    
        public function setTweets(?array $tweets): void
        {
            $this->tweets = $tweets;
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
2. Добавляем класс `App\Entity\EmailNotification`
    ```php
    <?php
    
    namespace App\Entity;
    
    use DateTime;
    use Doctrine\ORM\Mapping as ORM;
    use Gedmo\Mapping\Annotation as Gedmo;
    
    #[ORM\Table(name: 'email_notification')]
    #[ORM\Entity]
    class EmailNotification
    {
        #[ORM\Column(name: 'id', type: 'bigint', unique:true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private int $id;
    
        #[ORM\Column(type: 'string', length: 128, nullable: false)]
        private string $email;
    
        #[ORM\Column(type: 'string', length: 512, nullable: false)]
        private string $text;
    
        #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
        #[Gedmo\Timestampable(on: 'create')]
        private DateTime $createdAt;

        #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
        #[Gedmo\Timestampable(on: 'update')]
        private DateTime $updatedAt;
    
        public function getId(): int
        {
            return $this->id;
        }
    
        public function setId(int $id): void
        {
            $this->id = $id;
        }
    
        public function getEmail(): string
        {
            return $this->email;
        }
    
        public function setEmail(string $email): void
        {
            $this->email = $email;
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
    }
    ```
3. Добавляем класс `App\Entity\SmsNotification`
    ```php
    <?php
    
    namespace App\Entity;
    
    use DateTime;
    use Doctrine\ORM\Mapping as ORM;
    use Gedmo\Mapping\Annotation as Gedmo;
    
    #[ORM\Table(name: 'sms_notification')]
    #[ORM\Entity]
    class SmsNotification
    {
        #[ORM\Column(name: 'id', type: 'bigint', unique:true)]
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'IDENTITY')]
        private int $id;
    
        #[ORM\Column(type: 'string', length: 11, nullable: false)]
        private string $phone;

        #[ORM\Column(type: 'string', length: 60, nullable: false)]
        private string $text;
    
        #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
        #[Gedmo\Timestampable(on: 'create')]
        private DateTime $createdAt;

        #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
        #[Gedmo\Timestampable(on: 'update')]
        private DateTime $updatedAt;
    
        public function getId(): int
        {
            return $this->id;
        }
    
        public function setId(int $id): void
        {
            $this->id = $id;
        }
    
        public function getPhone(): string
        {
            return $this->phone;
        }
    
        public function setPhone(string $phone): void
        {
            $this->phone = $phone;
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
    }
    ```
4. В классе `App\Entity\User`
    1. добавляем новые поля `phone`, `email`, `preferred` и геттеры и сеттеры для них
        ```php
        #[ORM\Column(type: 'string', length: 11, nullable: true)]
        private ?string $phone;

        #[ORM\Column(type: 'string', length: 128, nullable: true)]
        private ?string $email;
 
        #[ORM\Column(type: 'string', length: 10, nullable: true)]
        private ?string $preferred;

        public function getPhone(): ?string
        {
            return $this->phone;
        }

        public function setPhone(?string $phone): void
        {
            $this->phone = $phone;
        }

        public function getEmail(): ?string
        {
            return $this->email;
        }
 
        public function setEmail(?string $email): void
        {
            $this->email = $email;
        }
 
        public function getPreferred(): ?string
        {
            return $this->preferred;
        }
 
        public function setPreferred(?string $preferred): void
        {
            $this->preferred = $preferred;
        }
        ```
    2. добавляем новые константы
        ```php
        public const EMAIL_NOTIFICATION = 'email';
        public const SMS_NOTIFICATION = 'sms';
        ```
5. Заходим в контейнер командой `docker exec -it php sh`. Дальнейшие команды выполняем из контейнера
6. Создаём миграцию и применяем её командами
    ```shell
    php bin/console doctrine:migrations:diff
    php bin/console doctrine:migrations:migrate
    ```
7. В классе `App\Entity\Tweet` добавляем два новых метода:
    ```php
    public function toFeed(): array
    {
        return [
            'id' => $this->id,
            'author' => $this->getAuthor()->getLogin(),
            'text' => $this->text,
            'createdAt' => $this->createdAt->format('Y-m-d h:i:s'),
        ];
    }
   
    /**
     * @throws JsonException
     */
    public function toAMPQMessage(): string
    {
        return json_encode(['tweetId' => $this->id], JSON_THROW_ON_ERROR);
    }   
    ```
8. Добавляем класс `App\Service\FeedService`
    ```php
    <?php
    
    namespace App\Service;
    
    use App\Entity\Feed;
    use App\Entity\Tweet;
    use App\Entity\User;
    use Doctrine\ORM\EntityManagerInterface;
    
    class FeedService
    {
        private EntityManagerInterface $entityManager;
    
        private SubscriptionService $subscriptionService;
    
        private AsyncService $asyncService;
    
        public function __construct(EntityManagerInterface $entityManager, SubscriptionService $subscriptionService, AsyncService $asyncService)
        {
            $this->entityManager = $entityManager;
            $this->subscriptionService = $subscriptionService;
            $this->asyncService = $asyncService;
        }
    
        public function getFeed(int $userId, int $count): array
        {
            $feed = $this->getFeedFromRepository($userId);
    
            return $feed === null ? [] : array_slice($feed->getTweets(), -$count);
        }
    
        public function spreadTweetAsync(Tweet $tweet): void
        {
            $this->asyncService->publishToExchange(AsyncService::PUBLISH_TWEET, $tweet->toAMPQMessage());
        }
    
        public function spreadTweetSync(Tweet $tweet): void
        {
            $followerIds = $this->subscriptionService->getFollowerIds($tweet->getAuthor()->getId());
    
            foreach ($followerIds as $followerId) {
                $this->putTweet($tweet, $followerId);
            }
        }
    
        public function putTweet(Tweet $tweet, int $userId): bool
        {
            $feed = $this->getFeedFromRepository($userId);
            if ($feed === null) {
                return false;
            }
            $tweets = $feed->getTweets();
            $tweets[] = $tweet->toFeed();
            $feed->setTweets($tweets);
            $this->entityManager->persist($feed);
            $this->entityManager->flush();
    
            return true;
        }
    
        private function getFeedFromRepository(int $userId): ?Feed
        {
            $userRepository = $this->entityManager->getRepository(User::class);
            $reader = $userRepository->find($userId);
            if (!($reader instanceof User)) {
                return null;
            }
    
            $feedRepository = $this->entityManager->getRepository(Feed::class);
            $feed = $feedRepository->findOneBy(['reader' => $reader]);
            if (!($feed instanceof Feed)) {
                $feed = new Feed();
                $feed->setReader($reader);
                $feed->setTweets([]);
            }
    
            return $feed;
        }
    }
    ```
9. Добавляем класс `App\Controller\Api\GetFeed\v1\Controller`
    ```php
    <?php
    
    namespace App\Controller\Api\GetFeed\v1;
    
    use App\Service\FeedService;
    use FOS\RestBundle\Controller\AbstractFOSRestController;
    use FOS\RestBundle\Controller\Annotations as Rest;
    use FOS\RestBundle\View\View;
    
    class Controller extends AbstractFOSRestController
    {
        /** @var int */
        private const DEFAULT_FEED_SIZE = 20;
    
        private FeedService $feedService;
    
        public function __construct(FeedService $feedService)
        {
            $this->feedService = $feedService;
        }
    
        /**
         * @Rest\Get("/api/v1/get-feed")
         *
         * @Rest\QueryParam(name="userId", requirements="\d+")
         * @Rest\QueryParam(name="count", requirements="\d+", nullable=true)
         */
        public function getFeedAction(int $userId, ?int $count = null): View
        {
            $count = $count ?? self::DEFAULT_FEED_SIZE;
            $tweets = $this->feedService->getFeed($userId, $count);
            $code = empty($tweets) ? 204 : 200;
    
            return View::create(['tweets' => $tweets], $code);
        }
    }
    ```
10. В классе `App\Manager\TweetManager` исправляем метод `saveTweet`
     ```php
     /**
      * @throws \Psr\Cache\InvalidArgumentException
      */
     public function saveTweet(int $authorId, string $text): ?Tweet {
         $tweet = new Tweet();
         $userRepository = $this->entityManager->getRepository(User::class);
         $author = $userRepository->find($authorId);
         if (!($author instanceof User)) {
             return null;
         }
         $tweet->setAuthor($author);
         $tweet->setText($text);
         $this->entityManager->persist($tweet);
         $this->entityManager->flush();

         $this->cache->invalidateTags([self::CACHE_TAG]);

         return $tweet;
     }
     ```
11. В классе `App\Controller\Api\SaveTweet\v1\Controller`
     1. Добавляем инъекцию `FeedService`
         ```php
         private FeedService $feedService;
    
         public function __construct(TweetManager $tweetManager, FeedService $feedService)
         {
             $this->tweetManager = $tweetManager;
             $this->feedService = $feedService;
         }
         ```
     2. Исправляем метод `saveTweetAction`:
         ```php
         /**
          * @throws \Psr\Cache\InvalidArgumentException
          */
         #[Rest\Post(path: '/api/v1/tweet')]
         #[RequestParam(name: 'authorId', requirements: '\d+')]
         #[RequestParam(name: 'text')]
         #[RequestParam(name: 'async', requirements: '0|1', nullable: true)]
         public function saveTweetAction(int $authorId, string $text, ?int $async): Response
         {
             $tweet = $this->tweetManager->saveTweet($authorId, $text);
             $success = $tweet !== null;
             if ($success) {
                 if ($async === 1) {
                     $this->feedService->spreadTweetAsync($tweet);
                 } else {
                     $this->feedService->spreadTweetSync($tweet);
                 }
             }
             $code = $success ? 200 : 400;
   
             return $this->handleView($this->view(['success' => $success], $code));
         }
         ```
12. В класс `App\Service\AsyncService` добавляем новые константы для продюсеров:
     ```php
     public const PUBLISH_TWEET = 'publish_tweet';
     public const SEND_NOTIFICATION = 'send_notification';
     ```
13. Исправляем класс `App\DTO\SaveUserDTO`
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
     
         public ?string $phone;
     
         public ?string $email;
     
         public ?string $preferred;
     
         public function __construct(array $data)
         {
             $this->login = $data['login'] ?? '';
             $this->password = $data['password'] ?? '';
             $this->age = $data['age'] ?? 0;
             $this->isActive = $data['isActive'] ?? false;
             $this->followers = $data['followers'] ?? [];
             $this->roles = $data['roles'] ?? [];
             $this->phone = $data['phone'] ?? '';
             $this->email = $data['email'] ?? '';
             $this->preferred = $data['preferred'] ?? '';
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
                 'phone' => $user->getPhone(),
                 'email' => $user->getEmail(),
                 'preferred' => $user->getPreferred(),
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
14. В классе `App\Manager\UserManager` исправляем метод `saveUserFromDTO`
     ```php
     public function saveUserFromDTO(User $user, SaveUserDTO $saveUserDTO): ?int
     {
         $user->setLogin($saveUserDTO->login);
         $user->setPassword($this->userPasswordHasher->hashPassword($user, $saveUserDTO->password));
         $user->setAge($saveUserDTO->age);
         $user->setIsActive($saveUserDTO->isActive);
         $user->setRoles($saveUserDTO->roles);
         $user->setPhone($saveUserDTO->phone);
         $user->setEmail($saveUserDTO->email);
         $user->setPreferred($saveUserDTO->preferred);
         $this->entityManager->persist($user);
         $this->entityManager->flush();

         return $user->getId();
     }
     ```
15. В классе `App\Service\SubscriptionService` исправляем метод `AddFollowers`
     ```php
     public function addFollowers(User $user, string $followerLogin, int $count): int
     {
         $createdFollowers = 0;
         for ($i = 0; $i < $count; $i++) {
             $login = "{$followerLogin}_#$i";
             $password = $followerLogin;
             $age = $i;
             $isActive = true;
             $phone = '+'.str_pad((string)abs(crc32($login)), 10, '0');
             $email = "$login@gmail.com";
             $preferred = random_int(0, 1) === 1 ? User::EMAIL_NOTIFICATION : User::SMS_NOTIFICATION;
             $data = compact('login', 'password', 'age', 'isActive', 'phone', 'email', 'preferred');
             $followerId = $this->userManager->saveUserFromDTO(new User(), new SaveUserDTO($data));
             if ($followerId !== null) {
                 $this->subscribe($user->getId(), $followerId);
                 $createdFollowers++;
             }
         }

         return $createdFollowers;
     }
     ```
16. В классе `App\Consumer\AddFollowers\Consumer` в методе `execute` убираем `sleep` и запланированную ошибку
17. В контейнере запускаем консьюмер командой `php bin/console rabbitmq:consumer add_followers -m 1000`
18. Выполняем запрос Add followers из Postman-коллекции v8 с параметрами `async` = 1 и `count` = 1000, проверяем, что
     фолловеры добавились

## Добавляем консьюмеры

1. Добавляем класс `App\Manager\EmailNotificationManager`
    ```php
    <?php
    
    namespace App\Manager;
    
    use App\Entity\EmailNotification;
    use Doctrine\ORM\EntityManagerInterface;
    
    class EmailNotificationManager
    {
        private EntityManagerInterface $entityManager;
    
        public function __construct(EntityManagerInterface $entityManager)
        {
            $this->entityManager = $entityManager;
        }
    
        public function saveEmailNotification(string $email, string $text): void {
            $emailNotification = new EmailNotification();
            $emailNotification->setEmail($email);
            $emailNotification->setText($text);
            $this->entityManager->persist($emailNotification);
            $this->entityManager->flush();
        }
    }
    ```
2. Добавляем класс `App\Manager\SmsNotificationManager`
    ```php
    <?php
    
    namespace App\Manager;
    
    use App\Entity\SmsNotification;
    use Doctrine\ORM\EntityManagerInterface;
    
    final class SmsNotificationManager
    {
        private EntityManagerInterface $entityManager;
    
        public function __construct(EntityManagerInterface $entityManager)
        {
            $this->entityManager = $entityManager;
        }
    
        public function saveSmsNotification(string $phone, string $text): void {
            $smsNotification = new SmsNotification();
            $smsNotification->setPhone($phone);
            $smsNotification->setText($text);
            $this->entityManager->persist($smsNotification);
            $this->entityManager->flush();
        }
    }
    ```
3. Добавляем класс `App\Consumer\SendEmailNotification\Input\Message`
    ```php
    <?php
    
    namespace App\Consumer\SendEmailNotification\Input;
    
    use Symfony\Component\Validator\Constraints as Assert;
    
    final class Message
    {
        #[Assert\Type('numeric')]
        private int $userId;

        #[Assert\Type('string')]
        #[Assert\Length(max: 512)]
        private string $text;
    
        public static function createFromQueue(string $messageBody): self
        {
            $message = json_decode($messageBody, true, 512, JSON_THROW_ON_ERROR);
            $result = new self();
            $result->userId = $message['userId'];
            $result->text = $message['text'];
    
            return $result;
        }
    
        public function getUserId(): int
        {
            return $this->userId;
        }
    
        public function getText(): string
        {
            return $this->text;
        }
    }
    ```
4. Добавляем класс `App\Consumer\SendEmailNotification\Consumer`
    ```php
    <?php
    
    namespace App\Consumer\SendEmailNotification;
    
    use App\Consumer\SendEmailNotification\Input\Message;
    use App\Entity\User;
    use App\Manager\EmailNotificationManager;
    use Doctrine\ORM\EntityManagerInterface;
    use JsonException;
    use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
    use PhpAmqpLib\Message\AMQPMessage;
    use Symfony\Component\Validator\Validator\ValidatorInterface;
    
    class Consumer implements ConsumerInterface
    {
        private EntityManagerInterface $entityManager;
    
        private ValidatorInterface $validator;
    
        private EmailNotificationManager $emailNotificationManager;
    
        public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, EmailNotificationManager $emailNotificationManager)
        {
            $this->entityManager = $entityManager;
            $this->validator = $validator;
            $this->emailNotificationManager = $emailNotificationManager;
        }
    
        public function execute(AMQPMessage $msg): int
        {
            try {
                $message = Message::createFromQueue($msg->getBody());
                $errors = $this->validator->validate($message);
                if ($errors->count() > 0) {
                    return $this->reject((string)$errors);
                }
            } catch (JsonException $e) {
                return $this->reject($e->getMessage());
            }
    
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->find($message->getUserId());
            if (!($user instanceof User)) {
                return $this->reject(sprintf('User ID %s was not found', $message->getUserId()));
            }
    
            $this->emailNotificationManager->saveEmailNotification($user->getEmail(), $message->getText());
    
            $this->entityManager->clear();
            $this->entityManager->getConnection()->close();
    
            return self::MSG_ACK;
        }
    
        private function reject(string $error): int
        {
            echo "Incorrect message: $error";
    
            return self::MSG_REJECT;
        }
    }
    ```
5. Добавляем класс `App\Consumer\SendSmsNotification\Input\Message`
    ```php
    <?php
    
    namespace App\Consumer\SendSmsNotification\Input;
    
    use Symfony\Component\Validator\Constraints as Assert;
    
    final class Message
    {
        #[Assert\Type('numeric')]
        private int $userId;
    
        #[Assert\Type('string')]
        #[Assert\Length(max: 60)]
        private string $text;
    
        public static function createFromQueue(string $messageBody): self
        {
            $message = json_decode($messageBody, true, 512, JSON_THROW_ON_ERROR);
            $result = new self();
            $result->userId = $message['userId'];
            $result->text = $message['text'];
    
            return $result;
        }
    
        public function getUserId(): int
        {
            return $this->userId;
        }
    
        public function getText(): string
        {
            return $this->text;
        }
    }
    ```
6. Добавляем класс `App\Consumer\SendSmsNotification\Consumer`
    ```php
    <?php
    
    namespace App\Consumer\SendSmsNotification;
    
    use App\Consumer\SendSmsNotification\Input\Message;
    use App\Entity\User;
    use App\Manager\SmsNotificationManager;
    use Doctrine\ORM\EntityManagerInterface;
    use JsonException;
    use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
    use PhpAmqpLib\Message\AMQPMessage;
    use Symfony\Component\Validator\Validator\ValidatorInterface;
    
    class Consumer implements ConsumerInterface
    {
        private EntityManagerInterface $entityManager;
    
        private ValidatorInterface $validator;
    
        private SmsNotificationManager $smsNotificationManager;
    
        public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, SmsNotificationManager $smsNotificationManager)
        {
            $this->entityManager = $entityManager;
            $this->validator = $validator;
            $this->smsNotificationManager = $smsNotificationManager;
        }
    
        public function execute(AMQPMessage $msg): int
        {
            try {
                $message = Message::createFromQueue($msg->getBody());
                $errors = $this->validator->validate($message);
                if ($errors->count() > 0) {
                    return $this->reject((string)$errors);
                }
            } catch (JsonException $e) {
                return $this->reject($e->getMessage());
            }
    
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->find($message->getUserId());
            if (!($user instanceof User)) {
                return $this->reject(sprintf('User ID %s was not found', $message->getUserId()));
            }
    
            $this->smsNotificationManager->saveSmsNotification($user->getPhone(), $message->getText());
    
            $this->entityManager->clear();
            $this->entityManager->getConnection()->close();
    
            return self::MSG_ACK;
        }
    
        private function reject(string $error): int
        {
            echo "Incorrect message: $error";
    
            return self::MSG_REJECT;
        }
    }
    ```
7. Добавляем класс `App\DTO\SendNotificationDTO`
    ```php
    <?php
    
    namespace App\DTO;
    
    class SendNotificationDTO
    {
        private array $payload;
    
        public function __construct(int $userId, string $text)
        {
            $this->payload = ['userId' => $userId, 'text' => $text];
        }
    
        public function toAMQPMessage(): string
        {
            return json_encode($this->payload);
        }
    }
    ```
8. В класс `App\Manager\SubscriptionManager` добавляем метод `findAllByAuthor`
    ```php
    /**
     * @return Subscription[]
     */
    public function findAllByAuthor(User $author): array
    {
        $subscriptionRepository = $this->entityManager->getRepository(Subscription::class);
        return $subscriptionRepository->findBy(['author' => $author]) ?? [];
    }
    ```
9. В класс `App\Service\SubscriptionService` добавляем методы `getFollowerIds` и `getSubscriptionsByAuthorId`
    ```php
    /**
     * @return int[]
     */
    public function getFollowerIds(int $authorId): array
    {
        $subscriptions = $this->getSubscriptionsByAuthorId($authorId);
        $mapper = static function(Subscription $subscription) {
            return $subscription->getFollower()->getId();
        };

        return array_map($mapper, $subscriptions);
    }

    /**
     * @return Subscription[]
     */
    private function getSubscriptionsByAuthorId(int $authorId): array
    {
        $author = $this->userManager->findUserById($authorId);
        if (!($author instanceof User)) {
            return [];
        }
        return $this->subscriptionManager->findAllByAuthor($author);
    }
    ```
10. Создаём класс `App\Consumer\PublishTweet\Input\Message`
     ```php
     <?php
   
     namespace App\Consumer\PublishTweet\Input;
   
     use Symfony\Component\Validator\Constraints;
   
     final class Message
     {
         #[Constraints\Regex('/^\d+$/')]
         private int $tweetId;
   
         public static function createFromQueue(string $messageBody): self
         {
             $message = json_decode($messageBody, true, 512, JSON_THROW_ON_ERROR);
             $result = new self();
             $result->tweetId = $message['tweetId'];
   
             return $result;
         }
   
         /**
          * @return int
          */
         public function getTweetId(): int
         {
             return $this->tweetId;
         }
     }
     ``` 
11. Создаём класс `App\Consumer\PublishTweet\Consumer`
     ```php
     <?php
   
     namespace App\Consumer\PublishTweet;
   
     use App\Consumer\PublishTweet\Input\Message;
     use App\DTO\SendNotificationDTO;
     use App\Entity\Tweet;
     use App\Entity\User;
     use App\Service\AsyncService;
     use App\Service\FeedService;
     use App\Service\SubscriptionService;
     use Doctrine\ORM\EntityManagerInterface;
     use JsonException;
     use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
     use PhpAmqpLib\Message\AMQPMessage;
     use Symfony\Component\Validator\Validator\ValidatorInterface;
   
     class Consumer implements ConsumerInterface
     {
         private EntityManagerInterface $entityManager;
   
         private ValidatorInterface $validator;
   
         private SubscriptionService $subscriptionService;
   
         private FeedService $feedService;
   
         private AsyncService $asyncService;
   
         public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, SubscriptionService $subscriptionService, FeedService $feedService, AsyncService $asyncService)
         {
             $this->entityManager = $entityManager;
             $this->validator = $validator;
             $this->subscriptionService = $subscriptionService;
             $this->feedService = $feedService;
             $this->asyncService = $asyncService;
         }
   
         public function execute(AMQPMessage $msg): int
         {
             try {
                 $message = Message::createFromQueue($msg->getBody());
                 $errors = $this->validator->validate($message);
                 if ($errors->count() > 0) {
                     return $this->reject((string)$errors);
                 }
             } catch (JsonException $e) {
                 return $this->reject($e->getMessage());
             }
   
             $tweetRepository = $this->entityManager->getRepository(Tweet::class);
             $userRepository = $this->entityManager->getRepository(User::class);
             $tweet = $tweetRepository->find($message->getTweetId());
             if (!($tweet instanceof Tweet)) {
                 return $this->reject(sprintf('Tweet ID %s was not found', $message->getTweetId()));
             }
   
             $followerIds = $this->subscriptionService->getFollowerIds($tweet->getAuthor()->getId());
   
             foreach ($followerIds as $followerId) {
                 $this->feedService->putTweet($tweet, $followerId);
                 /** @var User $user */
                 $user = $userRepository->find($followerId);
                 if ($user !== null) {
                     $message = (new SendNotificationDTO($followerId, $tweet->getText()))->toAMQPMessage();
                     $this->asyncService->publishToExchange(
                         AsyncService::SEND_NOTIFICATION,
                         $message,
                         $user->getPreferred()
                     );
                 }
             }
   
             $this->entityManager->clear();
             $this->entityManager->getConnection()->close();
   
             return self::MSG_ACK;
         }
   
         private function reject(string $error): int
         {
             echo "Incorrect message: $error";
   
             return self::MSG_REJECT;
         }
     }
     ```
12. В файл `config/services.yaml` добавляем к сервису `App\Service\AsyncService` регистрацию новых продюсеров:
     ```yaml
     - ['registerProducer', [!php/const App\Service\AsyncService::PUBLISH_TWEET, '@old_sound_rabbit_mq.publish_tweet_producer']]
     - ['registerProducer', [!php/const App\Service\AsyncService::SEND_NOTIFICATION, '@old_sound_rabbit_mq.send_notification_producer']]    
     ```
13. Добавляем описание новых продюсеров и консьюмеров в файл `config/packages/old_sound_rabbit_mq.yaml`
     1. в секцию `producers`
         ```yaml
         publish_tweet:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.publish_tweet', type: direct}
         send_notification:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.send_notification', type: topic}
         ```
     2. в секцию `consumers`
         ```yaml
         publish_tweet:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.publish_tweet', type: direct}
             queue_options: {name: 'old_sound_rabbit_mq.consumer.publish_tweet'}
             callback: App\Consumer\PublishTweet\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         send_notification.email:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.send_notification', type: topic}
             queue_options: 
                 name: 'old_sound_rabbit_mq.consumer.send_notification.email'
                 routing_keys: [!php/const App\Entity\User::EMAIL_NOTIFICATION]
             callback: App\Consumer\SendEmailNotification\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         send_notification.sms:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.send_notification', type: topic}
             queue_options: 
                 name: 'old_sound_rabbit_mq.consumer.send_notification.sms'
                 routing_keys: [!php/const App\Entity\User::SMS_NOTIFICATION]
             callback: App\Consumer\SendSmsNotification\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         ```
14. Запускаем все новые консьюмеры командами
     ```shell
     php bin/console rabbitmq:consumer publish_tweet -m 1000 &
     php bin/console rabbitmq:consumer send_notification.sms -m 1000 &
     php bin/console rabbitmq:consumer send_notification.email -m 1000 &
     ```
15. Выполняем запрос Post tweet из Postman-коллекции v8 с параметром `async` = 1
16. Видим, что сообщения из точки обмена `old_sound_rabbit_mq.send_notification` распределились по двум очередям
    `old_sound_rabbit_mq.consumer.send_notification.email` и `old_sound_rabbit_mq.consumer.send_notification.sms`
   
## Добавляем supervisor

1. Добавляем файл `docker\supervisor\Dockerfile`
    ```dockerfile
    FROM php:8.0.14-fpm-alpine
    
    # Install dev dependencies
    RUN apk update \
    && apk upgrade --available \
    && apk add --virtual build-deps \
    autoconf \
    build-base \
    icu-dev \
    libevent-dev \
    openssl-dev \
    zlib-dev \
    libzip \
    libzip-dev \
    zlib \
    zlib-dev \
    bzip2 \
    git \
    libpng \
    libpng-dev \
    libjpeg \
    libjpeg-turbo-dev \
    libwebp-dev \
    libmemcached-dev \
    freetype \
    freetype-dev \
    postgresql-dev \
    curl \
    wget \
    bash
    
    # Install Composer
    RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
    
    # Install PHP extensions
    RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
    RUN docker-php-ext-install -j$(getconf _NPROCESSORS_ONLN) \
    intl \
    gd \
    bcmath \
    pcntl \
    pdo_pgsql \
    sockets \
    zip
    RUN pecl channel-update pecl.php.net \
    && pecl install -o -f \
    redis \
    event \
    memcached \
    && rm -rf /tmp/pear \
    && echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini \
    && echo "extension=event.so" > /usr/local/etc/php/conf.d/event.ini \
    && echo "extension=memcached.so" > /usr/local/etc/php/conf.d/memcached.ini
    
    RUN apk add supervisor && mkdir /var/log/supervisor
    ```
2. Добавляем файл `docker\supervisor\supervisord.conf`
    ```ini
    [supervisord]
    logfile=/var/log/supervisor/supervisord.log
    pidfile=/var/run/supervisord.pid
    nodaemon=true
    
    [include]
    files=/app/supervisor/*.conf
    ```
3. Добавляем в `docker-compose.yml` сервис
    ```yaml
    supervisor:
       build: docker/supervisor
       container_name: 'supervisor'
       volumes:
           - ./:/app
           - ./docker/supervisor/supervisord.conf:/etc/supervisor/supervisord.conf
       working_dir: /app
       command: ["supervisord", "-c", "/etc/supervisor/supervisord.conf"]
    ```
4. Добавляем конфигурацию для запуска консьюмеров в файле `supervisor/consumer.conf`
    ```ini
    [program:add_followers]
    command=php /app/bin/console rabbitmq:consumer -m 1000 add_followers --env=dev -vv
    process_name=add_follower_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.add_followers.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.add_followers.error.log
    stderr_capture_maxbytes=1MB
    
    [program:publish_tweet]
    command=php /app/bin/console rabbitmq:consumer -m 1000 publish_tweet --env=dev -vv
    process_name=publish_tweet_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.publish_tweet.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.publish_tweet.error.log
    stderr_capture_maxbytes=1MB

    [program:send_notification_email]
    command=php /app/bin/console rabbitmq:consumer -m 1000 send_notification.email --env=dev -vv
    process_name=send_notification_email_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.send_notification_email.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.send_notification_email.error.log
    stderr_capture_maxbytes=1MB
    
    [program:send_notification_sms]
    command=php /app/bin/console rabbitmq:consumer -m 1000 send_notification.sms --env=dev -vv
    process_name=send_notification_sms_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.send_notification_sms.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.send_notification_sms.error.log
    stderr_capture_maxbytes=1MB
    ```
5. Перезапускаем контейнеры
    ```shell
    docker-compose stop
    docker-compose up -d
    ```
6. Проверяем в RabbitMQ, что консьюмеры запущены
7. Выполняем несколько запросов Post tweet из Postman-коллекции v8 с параметром `async` = 1, проверяем, что консьюмеры
   всё ещё живы, хотя лимит в 1000 сообщений был превышен

## Добавляем согласованное хэширование

1. Входим в контейнер `rabbit-mq` командой `docker exec -it rabbit-mq sh` и выполняем в нём команду
    ```shell
    rabbitmq-plugins enable rabbitmq_consistent_hash_exchange
    ```
2. Создаём класс `App\Consumer\UpdateFeed\Input\Message`
    ```php
    <?php
    
    namespace App\Consumer\UpdateFeed\Input;
    
    use Symfony\Component\Validator\Constraints;
    
    final class Message
    {
        #[Constraints\Regex('/^\d+$/')]
        private int $tweetId;

        #[Constraints\Regex('/^\d+$/')]
        private int $followerId;
    
        public static function createFromQueue(string $messageBody): self
        {
            $message = json_decode($messageBody, true, 512, JSON_THROW_ON_ERROR);
            $result = new self();
            $result->tweetId = $message['tweetId'];
            $result->followerId = $message['followerId'];
    
            return $result;
        }
    
        public function getTweetId(): int
        {
            return $this->tweetId;
        }
    
        public function getFollowerId(): int
        {
            return $this->followerId;
        }
    }
    ``` 
3. Создаём класс `App\Consumer\UpdateFeed\Consumer`
    ```php
    <?php
    
    namespace App\Consumer\UpdateFeed;
    
    use App\Consumer\UpdateFeed\Input\Message;
    use App\DTO\SendNotificationDTO;
    use App\Entity\Tweet;
    use App\Entity\User;
    use App\Service\AsyncService;
    use App\Service\FeedService;
    use Doctrine\ORM\EntityManagerInterface;
    use JsonException;
    use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
    use PhpAmqpLib\Message\AMQPMessage;
    use Symfony\Component\Validator\Validator\ValidatorInterface;
    
    class Consumer implements ConsumerInterface
    {
        private EntityManagerInterface $entityManager;
    
        private ValidatorInterface $validator;
    
        private FeedService $feedService;
    
        private AsyncService $asyncService;
    
        public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, FeedService $feedService, AsyncService $asyncService)
        {
            $this->entityManager = $entityManager;
            $this->validator = $validator;
            $this->feedService = $feedService;
            $this->asyncService = $asyncService;
        }
    
        public function execute(AMQPMessage $msg): int
        {
            try {
                $message = Message::createFromQueue($msg->getBody());
                $errors = $this->validator->validate($message);
                if ($errors->count() > 0) {
                    return $this->reject((string)$errors);
                }
            } catch (JsonException $e) {
                return $this->reject($e->getMessage());
            }
    
            $tweetRepository = $this->entityManager->getRepository(Tweet::class);
            $userRepository = $this->entityManager->getRepository(User::class);
            $tweet = $tweetRepository->find($message->getTweetId());
            if (!($tweet instanceof Tweet)) {
                return $this->reject(sprintf('Tweet ID %s was not found', $message->getTweetId()));
            }
    
            $this->feedService->putTweet($tweet, $message->getFollowerId());
            /** @var User $user */
            $user = $userRepository->find($message->getFollowerId());
            if ($user !== null) {
                $message = (new SendNotificationDTO($message->getFollowerId(), $tweet->getText()))->toAMQPMessage();
                $this->asyncService->publishToExchange(
                    AsyncService::SEND_NOTIFICATION,
                    $message,
                    $user->getPreferred()
                );
            }
    
            $this->entityManager->clear();
            $this->entityManager->getConnection()->close();
    
            return self::MSG_ACK;
        }
    
        private function reject(string $error): int
        {
            echo "Incorrect message: $error";
    
            return self::MSG_REJECT;
        }
    }
    ```
4. Добавляем класс `App\Consumer\PublishTweet\Output\UpdateFeedMessage`
    ```php
    <?php
    
    namespace App\Consumer\PublishTweet\Output;
    
    final class UpdateFeedMessage
    {
        private array $payload;
    
        public function __construct(int $tweetId, int $followerId)
        {
            $this->payload = ['tweetId' => $tweetId, 'followerId' => $followerId];
        }
    
        public function toAMQPMessage(): string
        {
            return json_encode($this->payload, JSON_THROW_ON_ERROR);
        }
    }
    ```
5. В классе `App\Service\AsyncService` добавляем новую константу:
    ```php
    public const UPDATE_FEED = 'update_feed';
    ```
6. В файл `config/services.yaml` добавляем к сервису `App\Service\AsyncService` регистрацию нового продюсера:
    ```yaml
    - ['registerProducer', [!php/const App\Service\AsyncService::UPDATE_FEED, '@old_sound_rabbit_mq.update_feed_producer']]
    ```
7. В классе `App\Consumer\PublishTweet\Consumer` исправляем метод `execute`
    ```php
    public function execute(AMQPMessage $msg): int
    {
        try {
            $message = Message::createFromQueue($msg->getBody());
            $errors = $this->validator->validate($message);
            if ($errors->count() > 0) {
                return $this->reject((string)$errors);
            }
        } catch (JsonException $e) {
            return $this->reject($e->getMessage());
        }

        $tweetRepository = $this->entityManager->getRepository(Tweet::class);
        $tweet = $tweetRepository->find($message->getTweetId());
        if (!($tweet instanceof Tweet)) {
            return $this->reject(sprintf('Tweet ID %s was not found', $message->getTweetId()));
        }

        $followerIds = $this->subscriptionService->getFollowerIds($tweet->getAuthor()->getId());

        foreach ($followerIds as $followerId) {
            $message = (new UpdateFeedMessage($tweet->getId(), $followerId))->toAMQPMessage();
            $this->asyncService->publishToExchange(AsyncService::UPDATE_FEED, $message, (string)$followerId);
        }

        $this->entityManager->clear();
        $this->entityManager->getConnection()->close();

        return self::MSG_ACK;
    }
    ```
8. Добавляем описание нового продюсера и консьюмера в файл `config/packages/old_sound_rabbit_mq.yaml`
    1. в секцию `producers`
        ```yaml
        update_feed:
            connection: default
            exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
        ```
    2. в секцию `consumers`
         ```yaml
         update_feed_0:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
             queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_0', routing_key: '1'}
             callback: App\Consumer\UpdateFeed\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         update_feed_1:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
             queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_1', routing_key: '1'}
             callback: App\Consumer\UpdateFeed\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         update_feed_2:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
             queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_2', routing_key: '1'}
             callback: App\Consumer\UpdateFeed\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         update_feed_3:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
             queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_3', routing_key: '1'}
             callback: App\Consumer\UpdateFeed\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         update_feed_4:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
             queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_4', routing_key: '1'}
             callback: App\Consumer\UpdateFeed\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         update_feed_5:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
             queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_5', routing_key: '1'}
             callback: App\Consumer\UpdateFeed\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         update_feed_6:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
             queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_6', routing_key: '1'}
             callback: App\Consumer\UpdateFeed\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         update_feed_7:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
             queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_7', routing_key: '1'}
             callback: App\Consumer\UpdateFeed\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         update_feed_8:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
             queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_8', routing_key: '1'}
             callback: App\Consumer\UpdateFeed\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         update_feed_9:
             connection: default
             exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
             queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_9', routing_key: '1'}
             callback: App\Consumer\UpdateFeed\Consumer
             idle_timeout: 300
             idle_timeout_exit_code: 0
             graceful_max_execution:
                 timeout: 1800
                 exit_code: 0
             qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
         ```
9. Добавляем новые консьюмеры в конфигурацию `supervisor` в файле `supervisor/consumer.conf`
    ```ini
    [program:update_feed_0]
    command=php /app/bin/console rabbitmq:consumer -m 1000 update_feed_0 --env=dev -vv
    process_name=update_feed_0_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_1]
    command=php /app/bin/console rabbitmq:consumer -m 1000 update_feed_1 --env=dev -vv
    process_name=update_feed_1_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_2]
    command=php /app/bin/console rabbitmq:consumer -m 1000 update_feed_2 --env=dev -vv
    process_name=update_feed_2_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_3]
    command=php /app/bin/console rabbitmq:consumer -m 1000 update_feed_3 --env=dev -vv
    process_name=update_feed_3_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_4]
    command=php /app/bin/console rabbitmq:consumer -m 1000 update_feed_4 --env=dev -vv
    process_name=update_feed_4_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_5]
    command=php /app/bin/console rabbitmq:consumer -m 1000 update_feed_5 --env=dev -vv
    process_name=update_feed_5_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_6]
    command=php /app/bin/console rabbitmq:consumer -m 1000 update_feed_6 --env=dev -vv
    process_name=update_feed_6_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_7]
    command=php /app/bin/console rabbitmq:consumer -m 1000 update_feed_7 --env=dev -vv
    process_name=update_feed_7_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_8]
    command=php /app/bin/console rabbitmq:consumer -m 1000 update_feed_8 --env=dev -vv
    process_name=update_feed_8_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    
    [program:update_feed_9]
    command=php /app/bin/console rabbitmq:consumer -m 1000 update_feed_9 --env=dev -vv
    process_name=update_feed_9_%(process_num)02d
    numprocs=1
    directory=/tmp
    autostart=true
    autorestart=true
    startsecs=3
    startretries=10
    user=www-data
    redirect_stderr=false
    stdout_logfile=/app/var/log/supervisor.update_feed.out.log
    stdout_capture_maxbytes=1MB
    stderr_logfile=/app/var/log/supervisor.update_feed.error.log
    stderr_capture_maxbytes=1MB
    ```
10. Перезапускаем контейнер `supervisor` командой `docker-compose restart supervisor`
11. Видим, что в RabbitMQ появились очереди с консьюмерами и точка обмена типа `x-consistent-hash`
12. Выполняем запрос Post tweet из Postman-коллекции v8 с параметром `async` = 1
13. В интерфейсе RabbitMQ можно увидеть, что в некоторые очереди насыпались сообщения, но сложно оценить равномерность
    распределения

## Добавляем мониторинг

1. В классе `App\Consumer\UpdateFeed\Consumer`
    1. Добавляем инъекцию `StatsdAPIClient` и строковый ключ `$key`
        ```php
        private StatsdAPIClient $statsdAPIClient;
    
        private string $key;
    
        public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, FeedService $feedService, AsyncService $asyncService, StatsdAPIClient $statsdAPIClient, string $key)
        {
            $this->entityManager = $entityManager;
            $this->validator = $validator;
            $this->feedService = $feedService;
            $this->asyncService = $asyncService;
            $this->statsdAPIClient = $statsdAPIClient;
            $this->key = $key;
        }
        ```
    2. Добавляем в метод `execute` увеличение счётчика обработанных сообщений конкретным консьюмером
        ```php
        public function execute(AMQPMessage $msg): int
        {
            try {
                $message = Message::createFromQueue($msg->getBody());
                $errors = $this->validator->validate($message);
                if ($errors->count() > 0) {
                    return $this->reject((string)$errors);
                }
            } catch (JsonException $e) {
                return $this->reject($e->getMessage());
            }
    
            $tweetRepository = $this->entityManager->getRepository(Tweet::class);
            $userRepository = $this->entityManager->getRepository(User::class);
            $tweet = $tweetRepository->find($message->getTweetId());
            if (!($tweet instanceof Tweet)) {
                return $this->reject(sprintf('Tweet ID %s was not found', $message->getTweetId()));
            }
    
            $this->feedService->putTweet($tweet, $message->getFollowerId());
            /** @var User $user */
            $user = $userRepository->find($message->getFollowerId());
            if ($user !== null) {
                $message = (new SendNotificationDTO($message->getFollowerId(), $tweet->getText()))->toAMQPMessage();
                $this->asyncService->publishToExchange(
                    AsyncService::SEND_NOTIFICATION,
                    $message,
                    $user->getPreferred()
                );
            }
    
            $this->statsdAPIClient->increment($this->key);
            $this->entityManager->clear();
            $this->entityManager->getConnection()->close();
    
            return self::MSG_ACK;
        }
        ```
2. Добавляем в `config/services.yaml` инъекцию идентификаторов в консьюмеры
    ```yaml
    App\Consumer\UpdateFeed\Consumer0:
        class: App\Consumer\UpdateFeed\Consumer
        arguments:
            $key: 'update_feed_0'

    App\Consumer\UpdateFeed\Consumer1:
        class: App\Consumer\UpdateFeed\Consumer
        arguments:
            $key: 'update_feed_1'

    App\Consumer\UpdateFeed\Consumer2:
        class: App\Consumer\UpdateFeed\Consumer
        arguments:
            $key: 'update_feed_2'

    App\Consumer\UpdateFeed\Consumer3:
        class: App\Consumer\UpdateFeed\Consumer
        arguments:
            $key: 'update_feed_3'

    App\Consumer\UpdateFeed\Consumer4:
        class: App\Consumer\UpdateFeed\Consumer
        arguments:
            $key: 'update_feed_4'

    App\Consumer\UpdateFeed\Consumer5:
        class: App\Consumer\UpdateFeed\Consumer
        arguments:
            $key: 'update_feed_5'

    App\Consumer\UpdateFeed\Consumer6:
        class: App\Consumer\UpdateFeed\Consumer
        arguments:
            $key: 'update_feed_6'

    App\Consumer\UpdateFeed\Consumer7:
        class: App\Consumer\UpdateFeed\Consumer
        arguments:
            $key: 'update_feed_7'

    App\Consumer\UpdateFeed\Consumer8:
        class: App\Consumer\UpdateFeed\Consumer
        arguments:
            $key: 'update_feed_8'

    App\Consumer\UpdateFeed\Consumer9:
        class: App\Consumer\UpdateFeed\Consumer
        arguments:
            $key: 'update_feed_9'            
    ```
3. В файл `config/packages/old_sound_rabbit_mq.yaml` в секции `consumers` исправляем коллбэки для каждого консьюмера на
`App\Consumer\UpdateFeed\ConsumerK`
    ```yaml
    update_feed_0:
      connection: default
      exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
      queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_0', routing_key: '1'}
      callback: App\Consumer\UpdateFeed\Consumer0
      idle_timeout: 300
      idle_timeout_exit_code: 0
      graceful_max_execution:
        timeout: 1800
        exit_code: 0
      qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
    update_feed_1:
      connection: default
      exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
      queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_1', routing_key: '1'}
      callback: App\Consumer\UpdateFeed\Consumer1
      idle_timeout: 300
      idle_timeout_exit_code: 0
      graceful_max_execution:
        timeout: 1800
        exit_code: 0
      qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
    update_feed_2:
      connection: default
      exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
      queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_2', routing_key: '1'}
      callback: App\Consumer\UpdateFeed\Consumer2
      idle_timeout: 300
      idle_timeout_exit_code: 0
      graceful_max_execution:
        timeout: 1800
        exit_code: 0
      qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
    update_feed_3:
      connection: default
      exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
      queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_3', routing_key: '1'}
      callback: App\Consumer\UpdateFeed\Consumer3
      idle_timeout: 300
      idle_timeout_exit_code: 0
      graceful_max_execution:
        timeout: 1800
        exit_code: 0
      qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
    update_feed_4:
      connection: default
      exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
      queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_4', routing_key: '1'}
      callback: App\Consumer\UpdateFeed\Consumer4
      idle_timeout: 300
      idle_timeout_exit_code: 0
      graceful_max_execution:
        timeout: 1800
        exit_code: 0
      qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
    update_feed_5:
      connection: default
      exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
      queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_5', routing_key: '1'}
      callback: App\Consumer\UpdateFeed\Consumer5
      idle_timeout: 300
      idle_timeout_exit_code: 0
      graceful_max_execution:
        timeout: 1800
        exit_code: 0
      qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
    update_feed_6:
      connection: default
      exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
      queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_6', routing_key: '1'}
      callback: App\Consumer\UpdateFeed\Consumer6
      idle_timeout: 300
      idle_timeout_exit_code: 0
      graceful_max_execution:
        timeout: 1800
        exit_code: 0
      qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
    update_feed_7:
      connection: default
      exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
      queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_7', routing_key: '1'}
      callback: App\Consumer\UpdateFeed\Consumer7
      idle_timeout: 300
      idle_timeout_exit_code: 0
      graceful_max_execution:
        timeout: 1800
        exit_code: 0
      qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
    update_feed_8:
      connection: default
      exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
      queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_8', routing_key: '1'}
      callback: App\Consumer\UpdateFeed\Consumer8
      idle_timeout: 300
      idle_timeout_exit_code: 0
      graceful_max_execution:
        timeout: 1800
        exit_code: 0
      qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
    update_feed_9:
      connection: default
      exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
      queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_9', routing_key: '1'}
      callback: App\Consumer\UpdateFeed\Consumer9
      idle_timeout: 300
      idle_timeout_exit_code: 0
      graceful_max_execution:
        timeout: 1800
        exit_code: 0
      qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
    ```
4. Перезапускаем контейнер `supervisor` командой `docker-compose restart supervisor`
5. Выполняем несколько запросов Post tweet из Postman-коллекции v8 с параметром `async` = 1
6. Заходим в Grafana по адресу `localhost:3000` с логином / паролем `admin` / `admin`
7. Добавляем Data source с типом Graphite и url `http://graphite:80`
8. Добавляем Dashboard и Panel
9. Для созданной Panel выбираем `Inspect > Panel JSON`, вставляем в поле содержимое файла `grafana_panel.json` и
сохраняем
10. Видим, что распределение не очень равномерное

## Балансируем консьюмеры

1. В файл `config/packages/old_sound_rabbit_mq.yaml` в секции `consumers` исправляем для каждого консьюмера значение на
`routing_key` на 20
     ```yaml
     update_feed_0:
         connection: default
         exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
         queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_0', routing_key: '20'}
         callback: App\Consumer\UpdateFeed\Consumer0
         idle_timeout: 300
         idle_timeout_exit_code: 0
         graceful_max_execution:
             timeout: 1800
             exit_code: 0
         qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
     update_feed_1:
         connection: default
         exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
         queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_1', routing_key: '20'}
         callback: App\Consumer\UpdateFeed\Consumer1
         idle_timeout: 300
         idle_timeout_exit_code: 0
         graceful_max_execution:
             timeout: 1800
             exit_code: 0
         qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
     update_feed_2:
         connection: default
         exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
         queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_2', routing_key: '20'}
         callback: App\Consumer\UpdateFeed\Consumer2
         idle_timeout: 300
         idle_timeout_exit_code: 0
         graceful_max_execution:
             timeout: 1800
             exit_code: 0
         qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
     update_feed_3:
         connection: default
         exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
         queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_3', routing_key: '20'}
         callback: App\Consumer\UpdateFeed\Consumer3
         idle_timeout: 300
         idle_timeout_exit_code: 0
         graceful_max_execution:
             timeout: 1800
             exit_code: 0
         qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
     update_feed_4:
         connection: default
         exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
         queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_4', routing_key: '20'}
         callback: App\Consumer\UpdateFeed\Consumer4
         idle_timeout: 300
         idle_timeout_exit_code: 0
         graceful_max_execution:
             timeout: 1800
             exit_code: 0
         qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
     update_feed_5:
         connection: default
         exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
         queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_5', routing_key: '20'}
         callback: App\Consumer\UpdateFeed\Consumer5
         idle_timeout: 300
         idle_timeout_exit_code: 0
         graceful_max_execution:
             timeout: 1800
             exit_code: 0
         qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
     update_feed_6:
         connection: default
         exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
         queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_6', routing_key: '20'}
         callback: App\Consumer\UpdateFeed\Consumer6
         idle_timeout: 300
         idle_timeout_exit_code: 0
         graceful_max_execution:
             timeout: 1800
             exit_code: 0
         qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
     update_feed_7:
         connection: default
         exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
         queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_7', routing_key: '20'}
         callback: App\Consumer\UpdateFeed\Consumer7
         idle_timeout: 300
         idle_timeout_exit_code: 0
         graceful_max_execution:
             timeout: 1800
             exit_code: 0
         qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
     update_feed_8:
         connection: default
         exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
         queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_8', routing_key: '20'}
         callback: App\Consumer\UpdateFeed\Consumer8
         idle_timeout: 300
         idle_timeout_exit_code: 0
         graceful_max_execution:
             timeout: 1800
             exit_code: 0
         qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
     update_feed_9:
         connection: default
         exchange_options: {name: 'old_sound_rabbit_mq.update_feed', type: x-consistent-hash}
         queue_options: {name: 'old_sound_rabbit_mq.consumer.update_feed_9', routing_key: '20'}
         callback: App\Consumer\UpdateFeed\Consumer9
         idle_timeout: 300
         idle_timeout_exit_code: 0
         graceful_max_execution:
             timeout: 1800
             exit_code: 0
         qos_options: {prefetch_size: 0, prefetch_count: 1, global: false}
     ```
2. Перезапускаем контейнер `supervisor` командой `docker-compose restart supervisor`
3. Выполняем несколько запросов Post tweet из Postman-коллекции v8 с параметром `async` = 1
4. Видим, что распределение стало гораздо равномернее   

