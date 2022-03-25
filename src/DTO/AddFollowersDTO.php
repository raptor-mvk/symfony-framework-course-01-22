<?php

namespace App\DTO;

use JsonException;

class AddFollowersDTO
{
    private array $payload;

    public function __construct(int $userId, string $followerLogin, int $count)
    {
        $this->payload = ['userId' => $userId, 'followerLogin' => $followerLogin, 'count' => $count];
    }

    /**
     * @throws JsonException
     */
    public function toAMQPMessage(): string
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR);
    }
}
