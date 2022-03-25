# Кэширование

## Memcached в качестве кэша Doctrine

### Устанавливаем Memcached

1. Добавляем в файл `docker/Dockerfile`
    1. Установку пакета `libmemcached-dev` через `apk`
    2. Установку расширения `memcached` через `pecl`
    3. Включение расширения командой `echo "extension=memcached.so" > /usr/local/etc/php/conf.d/memcached.ini`
2. Добавляем сервис Memcached в `docker-compose.yml`
    ```yaml
    memcached:
        image: memcached:latest
        container_name: 'memcached'
        restart: always
        ports:
           - 11211:11211
    ```
3. В файл `.env` добавляем
    ```shell
    MEMCACHED_DSN=memcached://memcached:11211
    ```
4. Пересобираем и запускаем контейнеры командой `docker-compose up -d --build`
5. Подключаемся к Memcached командой `telnet 127.0.0.1 11211` и проверяем, что он пустой (команда `stats items`)

### Добавляем данные и метод для их извлечения

1. Добавим в БД 10 тысяч случайных твитов запросом
    ```sql
    INSERT INTO tweet (created_at, updated_at, author_id, text)
    SELECT NOW(), NOW(), 1, md5(random()::TEXT) FROM generate_series(1,10000);
    ```
2. Добавляем класс `App\Repository\TweetRepository`
    ```php
    <?php
    
    namespace App\Repository;
    
    use App\Entity\Tweet;
    use Doctrine\ORM\EntityRepository;
    
    class TweetRepository extends EntityRepository
    {
        /**
         * @return Tweet[]
         */
        public function getTweets(int $page, int $perPage): array
        {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb->select('t')
                ->from($this->getClassName(), 't')
                ->orderBy('t.id', 'DESC')
                ->setFirstResult($perPage * $page)
                ->setMaxResults($perPage);
    
            return $qb->getQuery()->getResult();
        }
    }
    ```
3. В класс `App\Manager\TweetManager` добавляем метод `getTweets`
    ```php
    /**
     * @return Tweet[]
     */
    public function getTweets(int $page, int $perPage): array
    {
        /** @var TweetRepository $TweetRepository */
        $tweetRepository = $this->entityManager->getRepository(Tweet::class);

        return $tweetRepository->getTweets($page, $perPage);
    }
    ```
4. Добавляем класс `App\Controller\Api\GetTweets\v1\Controller`
    ```php
    <?php
    
    namespace App\Controller\Api\GetTweets\v1;
    
    use App\Entity\Tweet;
    use App\Manager\TweetManager;
    use FOS\RestBundle\Controller\AbstractFOSRestController;
    use FOS\RestBundle\Controller\Annotations as Rest;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    
    class Controller extends AbstractFOSRestController
    {
        private TweetManager $tweetManager;
    
        public function __construct(TweetManager $tweetManager)
        {
            $this->tweetManager = $tweetManager;
        }
    
        #[Rest\Get(path: '/api/v1/tweet')]
        public function getTweetsAction(Request $request): Response
        {
            $perPage = $request->query->get('perPage');
            $page = $request->query->get('page');
            $tweets = $this->tweetManager->getTweets($page ?? 0, $perPage ?? 20);
            $code = empty($tweets) ? 204 : 200;
            $view = $this->view(['tweets' => array_map(static fn(Tweet $tweet) => $tweet->toArray(), $tweets)], $code);
    
            return $this->handleView($view);
        }
    }
    ```
5. В классе `App\Entity\Tweet`
    1. Исправляем атрибут перед классом
        ```php
        #[ORM\Entity(repositoryClass: TweetRepository::class)]
        ```
    2. Исправляем метод `toArray`
        ```php
        public function toArray(): array
        {
            return [
                'id' => $this->id,
                'login' => $this->author->getLogin(),
                'text' => $this->text,
                'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
                'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            ];
        }
        ```
6. Выполняем запрос Get Tweet list из Postman-коллекции v6, видим, что результат возвращается

### Включаем кэширование в Doctrine

1. Входим в контейнер командой `docker exec -it php sh`, устанавливаем пакет `doctrine/cache:^1.10`
2. Исправляем файл `config/packages/doctrine.yaml`:
    1. Добавляем в секцию `orm`
        ```yaml
        metadata_cache_driver:
            type: service
            id: doctrine.cache.memcached
        query_cache_driver:
            type: service
            id: doctrine.cache.memcached
        result_cache_driver:
            type: service
            id: doctrine.cache.memcached
        ```
    2. Добавляем секцию `services`
        ```yaml
        services:
            memcached.doctrine:
                class: Memcached
                factory: Symfony\Component\Cache\Adapter\MemcachedAdapter::createConnection
                arguments:
                    - '%env(MEMCACHED_DSN)%'
                    - PREFIX_KEY: 'my_app_doctrine'
        
            doctrine.cache.memcached:
                class: Doctrine\Common\Cache\MemcachedCache
                calls:
                    - ['setMemcached', ['@memcached.doctrine']]
        ```    
3. Выполняем запрос Get Tweet list из Postman-коллекции v6 для прогрева кэша
4. Проверяем, что кэш прогрелся
    1. В Memcached выполняем `stats items`, видим там запись (или две записи)
    2. Выводим каждую запись командой `stats cachedump K 1000`, где K - идентификатор записи
    3. Получаем содержимое ключей командой `get KEY`, где `KEY` - ключ из записи
    4. Удостоверяемся, что это query и metadata кэши

### Добавляем кэширование результата запроса

1. Включаем result cache в класс `App\Repository\TweetRepository` в методе `getTweets` в последней строке
    ```php
    return $qb->getQuery()->enableResultCache(null, "tweets_{$page}_{$perPage}")->getResult();
    ```
2. Выполняем запрос Get Tweet list из Postman-коллекции v6 для прогрева кэша
3. В Memcached выполняем `get my_app_doctrine[tweets_PAGE_PER_PAGE][1]`, где `PAGE` и `PER_PAGE` - значения
одноимённых параметров запроса, видим содержимое result cache

## Redis в качестве кэша на уровне приложения

### Подключаем redis

1. Для включения кэша на уровне приложения в файле `config/packages/cache.yaml` добавляем в секцию `cache`
    ```yaml
    app: cache.adapter.redis
    default_redis_provider: '%env(REDIS_DSN)%'
    ```
2. В файл `.env` добавляем
    ```shell
    REDIS_DSN=redis://redis:6379
    ```
3. В `docker-compose.yml` добавляем проброс порта в сервис `redis`
    ```yaml
    redis:
        container_name: 'redis'
        image: redis
        ports:
          - 6379:6379
    ```
4. Перезапускаем контейнеры
    ```shell
    docker-compose stop
    docker-compose up -d
    ```
5. Подключаемся к Redis командой `telnet 127.0.0.1 6379`
6. Выполняем `keys *`, видим записи от Sentry

### Подключаем кэш на уровне приложения

1. Добавляем кэш в класс `App\Manager\TweetManager`
    1. Добавляем инъекцию `CacheItemPoolInterface`
        ```
        private CacheItemPoolInterface $cacheItemPool;

        public function __construct(EntityManagerInterface $entityManager, CacheItemPoolInterface $cacheItemPool)
        {
            $this->entityManager = $entityManager;
            $this->cacheItemPool = $cacheItemPool;
        }
        ```
    2. Исправляем метод `getTweets`
        ```php
        /**
         * @return Tweet[]
         *
         * @throws \Psr\Cache\InvalidArgumentException
         */
        public function getTweets(int $page, int $perPage): array
        {
            /** @var TweetRepository $tweetRepository */
            $tweetRepository = $this->entityManager->getRepository(Tweet::class);
    
            $tweetsItem = $this->cacheItemPool->getItem("tweets_{$page}_{$perPage}");
            if (!$tweetsItem->isHit()) {
                $tweets = $tweetRepository->getTweets($page, $perPage);
                $tweetsItem->set(array_map(static fn(Tweet $tweet) => $tweet->toArray(), $tweets));
                $this->cacheItemPool->save($tweetsItem);
            }
    
            return $tweetsItem->get();
        }
        ```
2. В классе `App\Controller\Api\GetTweets\v1\Controller` исправляем метод `getTweetsAction`
    ```php
    #[Rest\Get(path: '/api/v1/tweet')]
    public function getTweetsAction(Request $request): Response
    {
        $perPage = $request->query->get('perPage');
        $page = $request->query->get('page');
        $tweets = $this->tweetManager->getTweets($page ?? 0, $perPage ?? 20);
        $code = empty($tweets) ? 204 : 200;
        $view = $this->view(['tweets' => $tweets], $code);

        return $this->handleView($view);
    }
    ```   
3. Выполняем запрос Get Tweet list из Postman-коллекции v6 для прогрева кэша
4. В Redis ищем ключи от приложения командой `keys *tweets*`
5. Выводим найденный ключ командой `get KEY`, где `KEY` - найденный ключ

### Подсчитываем количество cache hit/miss

1. Добавляем декоратор для подсчёта cache hit/miss (класс `App\Symfony\CountingAdapterDecorator`)
    ```php
    <?php
    
    namespace App\Symfony;
    
    use App\Client\StatsdAPIClient;
    use Psr\Cache\CacheItemInterface;
    use Psr\Cache\InvalidArgumentException;
    use Psr\Log\LoggerAwareInterface;
    use Psr\Log\LoggerInterface;
    use Symfony\Component\Cache\Adapter\AbstractAdapter;
    use Symfony\Component\Cache\Adapter\AdapterInterface;
    use Symfony\Component\Cache\CacheItem;
    use Symfony\Component\Cache\ResettableInterface;
    use Symfony\Contracts\Cache\CacheInterface;
    
    class CountingAdapterDecorator implements AdapterInterface, CacheInterface, LoggerAwareInterface, ResettableInterface
    {
        private const STATSD_HIT_PREFIX = 'cache.hit.';
        private const STATSD_MISS_PREFIX = 'cache.miss.';
    
        private AbstractAdapter $adapter;
        private StatsdAPIClient $statsdAPIClient;
    
        public function __construct(AbstractAdapter $adapter, StatsdAPIClient $statsdAPIClient)
        {
            $this->adapter = $adapter;
            $this->statsdAPIClient = $statsdAPIClient;
            $this->adapter->setCallbackWrapper(null);
        }
    
        public function getItem($key): CacheItem
        {
            $result = $this->adapter->getItem($key);
            $this->incCounter($result);
    
            return $result;
        }
    
        /**
         * @param string[] $keys
         *
         * @return iterable
         *
         * @throws InvalidArgumentException
         */
        public function getItems(array $keys = []): array
        {
            $result = $this->adapter->getItems($keys);
            foreach ($result as $item) {
                $this->incCounter($item);
            }
    
            return $result;
        }
    
        public function clear(string $prefix = ''): bool
        {
            return $this->adapter->clear($prefix);
        }
    
        public function get(string $key, callable $callback, float $beta = null, array &$metadata = null)
        {
            return $this->adapter->get($key, $callback, $beta, $metadata);
        }
    
        public function delete(string $key): bool
        {
            return $this->adapter->delete($key);
        }
    
        public function hasItem($key): bool
        {
            return $this->adapter->hasItem($key);
        }
    
        public function deleteItem($key): bool
        {
            return $this->adapter->deleteItem($key);
        }
    
        public function deleteItems(array $keys): bool
        {
            return $this->adapter->deleteItems($keys);
        }
    
        public function save(CacheItemInterface $item): bool
        {
            return $this->adapter->save($item);
        }
    
        public function saveDeferred(CacheItemInterface $item): bool
        {
            return $this->adapter->saveDeferred($item);
        }
    
        public function commit(): bool
        {
            return $this->adapter->commit();
        }
    
        public function setLogger(LoggerInterface $logger): void
        {
            $this->adapter->setLogger($logger);
        }
    
        public function reset(): void
        {
            $this->adapter->reset();
        }
    
        private function incCounter(CacheItemInterface $cacheItem): void
        {
            if ($cacheItem->isHit()) {
                $this->statsdAPIClient->increment(self::STATSD_HIT_PREFIX.$cacheItem->getKey());
            } else {
                $this->statsdAPIClient->increment(self::STATSD_MISS_PREFIX.$cacheItem->getKey());
            }
        }
    }
    ```
2. В файл `config/services.yaml` добавляем
    ```yaml
    redis_client:
        class: Redis
        factory: Symfony\Component\Cache\Adapter\RedisAdapter::createConnection
        arguments:
            - '%env(REDIS_DSN)%'

    redis_adapter:
        class: Symfony\Component\Cache\Adapter\RedisAdapter
        arguments:
            - '@redis_client'
            - 'my_app'

    redis_adapter_decorated:
        class: App\Symfony\CountingAdapterDecorator
        arguments:
            - '@redis_adapter'

    App\Manager\TweetManager:
        arguments:
            $cacheItemPool: '@redis_adapter_decorated'
    ```
3. Выполняем два одинаковых запроса Get Tweet list из Postman-коллекции v6 для прогрева кэша и появления метрик
4. Заходим в Grafana, добавляем новую панель
5. Добавляем на панель метрики `sumSeries(stats_counts.my_app.cache.hit.*)` и
   `sumSeries(stats_counts.my_app.cache.miss.*)`

### Инвалидация кэша с помощью тэгов

1. В файле `config/services.yaml`
    1. в секции `services` убираем декоратор
        ```yaml
        redis_adapter_decorated:
            class: App\Symfony\CountingAdapterDecorator
            arguments:
                - '@redis_adapter'
        ```
    2. в секции `services.redis_adapter.class` класс на `RedisTagAwareAdapter`
        ```yaml
        class: Symfony\Component\Cache\Adapter\RedisTagAwareAdapter
        ```
    3. в секции `services.App\Service\TweetService.arguments` меняем имя параметра на `$cache` и сервис на
    `redis_adapter`
        ```yaml
        $cache: '@redis_adapter'
        ```
2. В классе `App\Entity\Tweet` исправляем аннотации для полей `createdAt` и `updatedAt`
    ```php
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'create')]
    private DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'update')]
    private DateTime $updatedAt;
    ```
3. В классе `App\Manager\TweetManager`
    1. Добавляем константу с именем тэга
        ```php
        private const CACHE_TAG = 'tweets';
        ```
    2. Меняем зависимость от `CacheItemPoolInterface` на `TagAwareCacheInterface`
        ```php
        private TagAwareCacheInterface $cache;
    
        public function __construct(EntityManagerInterface $entityManager, TagAwareCacheInterface $cache)
        {
            $this->entityManager = $entityManager;
            $this->cache = $cache;
        }
        ```
    3. Исправляем метод `getTweets`
        ```php
        /**
         * @return Tweet[]
         *
         * @throws \Psr\Cache\InvalidArgumentException
         */
        public function getTweets(int $page, int $perPage): array
        {
            /** @var TweetRepository $tweetRepository */
            $tweetRepository = $this->entityManager->getRepository(Tweet::class);
    
            /** @var ItemInterface $organizationsItem */
            return $this->cache->get(
                "tweets_{$page}_{$perPage}",
                function(ItemInterface $item) use ($tweetRepository, $page, $perPage) {
                    $tweets = $tweetRepository->getTweets($page, $perPage);
                    $tweetsSerialized = array_map(static fn(Tweet $tweet) => $tweet->toArray(), $tweets);
                    $item->set($tweetsSerialized);
                    $item->tag(self::CACHE_TAG);

                    return $tweetsSerialized;
                }
            );
        }
        ```
    4. Добавляем метод `saveTweet`
        ```php
        /**
         * @throws \Psr\Cache\InvalidArgumentException
         */
        public function saveTweet(int $authorId, string $text): bool {
            $tweet = new Tweet();
            $userRepository = $this->entityManager->getRepository(User::class);
            $author = $userRepository->find($authorId);
            if (!($author instanceof User)) {
                return false;
            }
            $tweet->setAuthor($author);
            $tweet->setText($text);
            $this->entityManager->persist($tweet);
            $this->entityManager->flush();
            $this->cache->invalidateTags([self::CACHE_TAG]);
    
            return true;
        }
        ```
4. Добавим класс `App\Controller\Api\SaveTweet\v1\Controller`
    ```php
    <?php
    
    namespace App\Controller\Api\SaveTweet\v1;
    
    use App\Controller\Common\ErrorResponseTrait;
    use App\Manager\TweetManager;
    use FOS\RestBundle\Controller\AbstractFOSRestController;
    use FOS\RestBundle\Controller\Annotations as Rest;
    use FOS\RestBundle\Controller\Annotations\RequestParam;
    use Symfony\Component\HttpFoundation\Response;
    
    class Controller extends AbstractFOSRestController
    {
        use ErrorResponseTrait;
    
        private TweetManager $tweetManager;
    
        public function __construct(TweetManager $tweetManager)
        {
            $this->tweetManager = $tweetManager;
        }
    
        #[Rest\Post(path: '/api/v1/tweet')]
        #[RequestParam(name: 'authorId', requirements: '\d+')]
        #[RequestParam(name: 'text')]
        public function saveTweetAction(int $authorId, string $text): Response
        {
            $tweetId = $this->tweetManager->saveTweet($authorId, $text);
            [$data, $code] = ($tweetId === null) ? [['success' => false], 400] : [['tweet' => $tweetId], 200];
            return $this->handleView($this->view($data, $code));
        }
    }
    ```
5. Выполняем запрос Post tweet из Postman-коллекции v6, видим ошибку
6. Заходим в контейнер с приложением командой `docker exec -it php sh`
7. В контейнере выполняем команду `php bin/console doctrine:cache:clear-metadata`   
8. Ещё раз выполняем запрос Post tweet из Postman-коллекции v6, видим успешное сохранение
9. В Redis выполняем `flushall`
10. Выполняем несколько запросов Get Tweet list из Postman-коллекции v6 с разными значениями параметров для прогрева кэша
11. Проверяем, что в Redis есть ключи для твитов командой `keys *tweets*`
12. Выполняем запрос Post tweet из Postman-коллекции v6
13. Проверяем, что в Redis удалились все ключи командой `keys *tweets*`
