<?php

namespace FeedBundle\Service;

use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class AsyncService
{
    public const SEND_NOTIFICATION = 'send_notification';

    /** @var ProducerInterface[] */
    private array $producers;

    public function __construct()
    {
        $this->producers = [];
    }

    public function registerProducer(string $producerName, ProducerInterface $producer): void
    {
        $this->producers[$producerName] = $producer;
    }

    public function publishToExchange(string $producerName, string $message, ?string $routingKey = null, ?array $additionalProperties = null): bool
    {
        if (isset($this->producers[$producerName])) {
            $this->producers[$producerName]->publish($message, $routingKey ?? '', $additionalProperties ?? []);

            return true;
        }

        return false;
    }

    public function publishMultipleToExchange(string $producerName, array $messages, ?string $routingKey = null, ?array $additionalProperties = null): int
    {
        $sentCount = 0;
        if (isset($this->producers[$producerName])) {
            foreach ($messages as $message) {
                $this->producers[$producerName]->publish($message, $routingKey ?? '', $additionalProperties ?? []);
                $sentCount++;
            }

            return $sentCount;
        }

        return $sentCount;
    }
}
