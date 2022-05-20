<?php

namespace FeedBundle\DTO;

use JMS\Serializer\Annotation as JMS;

class SendNotificationAsyncDTO
{
    #[JMS\Type('int')]
    #[JMS\SerializedName('userId')]
    private int $userId;

    #[JMS\Type('string')]
    private string $text;

    public function __construct(int $userId, string $text)
    {
        $this->userId = $userId;
        $this->text = $text;
    }
}
