<?php

namespace App\DTO;

use JsonException;
use JMS\Serializer\Annotation as JMS;

class AddFollowersDTO
{
    #[JMS\Exclude]
    private array $payload;

    #[JMS\Type('int')]
    #[JMS\SerializedName('userId')]
    private int $userId;

    #[JMS\Type('string')]
    #[JMS\SerializedName('followerLogin')]
    private string $followersLogin;

    #[JMS\Type('int')]
    private int $count;

    public function __construct(int $userId, string $followerLogin, int $count)
    {
        $this->payload = ['userId' => $userId, 'followerLogin' => $followerLogin, 'count' => $count];
        $this->userId = $userId;
        $this->followersLogin = $followerLogin;
        $this->count = $count;
    }

    /**
     * @throws JsonException
     */
    public function toAMQPMessage(): string
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR);
    }
}
