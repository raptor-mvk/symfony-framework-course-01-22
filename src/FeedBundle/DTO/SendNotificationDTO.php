<?php

namespace FeedBundle\DTO;

use JsonException;
use JMS\Serializer\Annotation as JMS;

class SendNotificationDTO
{
    #[JMS\Exclude]
    private array $payload;

    #[JMS\Type('int')]
    #[JMS\SerializedName('userId')]
    private int $userId;

    #[JMS\Type('string')]
    private string $text;

    #[JMS\Type('string')]
    private string $preferred;

    public function __construct(int $userId, string $text, string $preferred)
    {
        $this->payload = ['userId' => $userId, 'text' => $text];
        $this->userId = $userId;
        $this->text = $text;
        $this->preferred = $preferred;
    }

    /**
     * @throws JsonException
     */
    public function toAMQPMessage(): string
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR);
    }
}
