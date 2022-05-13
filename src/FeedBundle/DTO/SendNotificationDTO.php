<?php

namespace FeedBundle\DTO;

use JsonException;
use JMS\Serializer\Annotation as JMS;

class SendNotificationDTO
{
    private array $payload;

    #[JMS\Type('int')]
    #[JMS\SerializedName('userId')]
    private int $userId;

    #[JMS\Type('string')]
    private string $text;

    public function __construct(int $userId, string $text)
    {
        $this->payload = ['userId' => $userId, 'text' => $text];
        $this->userId = $userId;
        $this->text = $text;
    }

    /**
     * @throws JsonException
     */
    public function toAMQPMessage(): string
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR);
    }
}
