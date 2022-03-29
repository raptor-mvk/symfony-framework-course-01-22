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
