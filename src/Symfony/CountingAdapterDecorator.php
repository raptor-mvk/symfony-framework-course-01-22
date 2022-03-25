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
