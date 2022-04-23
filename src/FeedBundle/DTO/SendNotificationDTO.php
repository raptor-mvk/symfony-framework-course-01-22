<?php

namespace FeedBundle\DTO;

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
