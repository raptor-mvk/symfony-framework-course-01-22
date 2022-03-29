<?php

namespace App\Consumer\SendSmsNotification\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class Message
{
    #[Assert\Type('numeric')]
    private int $userId;

    #[Assert\Type('string')]
    #[Assert\Length(max: 60)]
    private string $text;

    public static function createFromQueue(string $messageBody): self
    {
        $message = json_decode($messageBody, true, 512, JSON_THROW_ON_ERROR);
        $result = new self();
        $result->userId = $message['userId'];
        $result->text = $message['text'];

        return $result;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
