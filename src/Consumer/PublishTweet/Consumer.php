<?php

namespace App\Consumer\PublishTweet;

use App\Consumer\PublishTweet\Input\Message;
use App\Consumer\PublishTweet\Output\UpdateFeedMessage;
use App\Entity\Tweet;
use App\Service\AsyncService;
use App\Service\SubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Consumer implements ConsumerInterface
{
    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private SubscriptionService $subscriptionService;

    private AsyncService $asyncService;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, SubscriptionService $subscriptionService, AsyncService $asyncService)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->subscriptionService = $subscriptionService;
        $this->asyncService = $asyncService;
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

        $tweetRepository = $this->entityManager->getRepository(Tweet::class);
        $tweet = $tweetRepository->find($message->getTweetId());
        if (!($tweet instanceof Tweet)) {
            return $this->reject(sprintf('Tweet ID %s was not found', $message->getTweetId()));
        }

        $followers = $this->subscriptionService->getFollowers($tweet->getAuthor()->getId());

        foreach ($followers as $follower) {
            $message = (new UpdateFeedMessage($tweet, $follower))->toAMQPMessage();
            $this->asyncService->publishToExchange(AsyncService::UPDATE_FEED, $message, (string)$follower->getId());
        }

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
