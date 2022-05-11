<?php

namespace App\Consumer\PublishTweet\Output;

use App\Entity\Tweet;
use App\Entity\User;

final class UpdateFeedMessage
{
    private array $payload;

    public function __construct(Tweet $tweet, User $follower)
    {
        $this->payload = array_merge($tweet->toFeed(), ['followerId' => $follower->getId(), 'preferred' => $follower->getPreferred()]);
    }

    public function toAMQPMessage(): string
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR, 512);
    }
}
