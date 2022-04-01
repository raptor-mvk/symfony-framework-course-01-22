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
