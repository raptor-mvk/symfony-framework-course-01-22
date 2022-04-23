<?php

namespace FeedBundle\Consumer\UpdateFeed\Input;

use FeedBundle\DTO\TweetDTO;
use Symfony\Component\Validator\Constraints;

final class Message
{
    private TweetDTO $tweetDTO;

    #[Constraints\Regex('/^\d+$/')]
    private int $followerId;

    private string $preferred;

    public static function createFromQueue(string $messageBody): self
    {
        $message = json_decode($messageBody, true, 512, JSON_THROW_ON_ERROR);
        $result = new self();
        $result->tweetDTO = new TweetDTO((int)$message['id'], $message['author'], $message['text'], $message['createdAt']);
        $result->followerId = $message['followerId'];
        $result->preferred = $message['preferred'];

        return $result;
    }

    public function getTweetDTO(): TweetDTO
    {
        return $this->tweetDTO;
    }

    public function getFollowerId(): int
    {
        return $this->followerId;
    }

    public function getPreferred(): string
    {
        return $this->preferred;
    }
}
