<?php

namespace FeedBundle\Consumer\UpdateFeed;

use Exception;
use StatsdBundle\Client\StatsdAPIClient;
use FeedBundle\Consumer\UpdateFeed\Input\Message;
use FeedBundle\DTO\SendNotificationDTO;
use FeedBundle\Service\FeedService;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

class Consumer implements ConsumerInterface
{
    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private FeedService $feedService;

    private MessageBusInterface $messageBus;

    private StatsdAPIClient $statsdAPIClient;

    private string $key;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, FeedService $feedService, MessageBusInterface $messageBus, StatsdAPIClient $statsdAPIClient, string $key)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->feedService = $feedService;
        $this->messageBus = $messageBus;
        $this->statsdAPIClient = $statsdAPIClient;
        $this->key = $key;
    }

    public function execute(AMQPMessage $msg): int
    {
        try {
            $message = Message::createFromQueue($msg->getBody());
            $errors = $this->validator->validate($message);
            if ($errors->count() > 0) {
                return $this->reject((string)$errors);
            }
        } catch (JsonException $e) {
            return $this->reject($e->getMessage());
        }

        $tweetDTO = $message->getTweetDTO();
        try {
            $this->entityManager->getConnection()->beginTransaction();
            $this->feedService->putTweet($tweetDTO, $message->getFollowerId());
/*            if ($message->getFollowerId() === 5) {
                sleep(2);
                throw new Exception();
            }*/
            $notificationMessage = new SendNotificationDTO($message->getFollowerId(), $tweetDTO->getText(), $message->getPreferred());
            $this->messageBus->dispatch($notificationMessage);
            $this->entityManager->getConnection()->commit();
        } catch (Throwable $e) {
            $this->entityManager->getConnection()->rollBack();
            return self::MSG_REJECT_REQUEUE;
        }

        $this->statsdAPIClient->increment($this->key);
        $this->entityManager->clear();
        $this->entityManager->getConnection()->close();

        return self::MSG_ACK;
    }

    private function reject(string $error): int
    {
        echo "Incorrect message: $error";

        return self::MSG_REJECT;
    }
}
