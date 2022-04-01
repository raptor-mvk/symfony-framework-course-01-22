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
