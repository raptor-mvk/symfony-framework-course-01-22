<?php

namespace FeedBundle\MessageHandler\SendNotification;

use FeedBundle\DTO\SendNotificationAsyncDTO;
use FeedBundle\DTO\SendNotificationDTO;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class Handler
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(SendNotificationDTO $message): void
    {
        $envelope = new Envelope(
            new SendNotificationAsyncDTO($message->getUserId(), $message->getText()),
            [new AmqpStamp($message->getPreferred())]
        );
        $this->messageBus->dispatch($envelope);
    }
}
