<?php

namespace FeedBundle\DTO;

class TweetDTO
{
    private array $payload;

    public function __construct(int $id, string $author, string $text, string $createdAt)
    {
        $this->payload = [
            'id' => $id,
            'author' => $author,
            'text' => $text,
            'createdAt' => $createdAt
        ];
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getText(): string
    {
        return $this->payload['text'];
    }
}
