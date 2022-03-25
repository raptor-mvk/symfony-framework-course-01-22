<?php

namespace App\Consumer\AddFollowers\Input;

use Symfony\Component\Validator\Constraints as Assert;

class Message
{
    #[Assert\Type('numeric')]
    private int $userId;

    #[Assert\Type('string')]
    #[Assert\Length(max: 32)]
    private string $followerLogin;

    #[Assert\Type('numeric')]
    private int $count;

    public static function createFromQueue(string $messageBody): self
    {
        $message = json_decode($messageBody, true, 512, JSON_THROW_ON_ERROR);
        $result = new self();
        $result->userId = $message['userId'];
        $result->followerLogin = $message['followerLogin'];
        $result->count = $message['count'];

        return $result;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getFollowerLogin(): string
    {
        return $this->followerLogin;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
