<?php

namespace App\Consumer\PublishTweet;

use App\Consumer\PublishTweet\Input\Message;
use App\DTO\SendNotificationDTO;
use App\Entity\Tweet;
use App\Entity\User;
use App\Service\AsyncService;
use App\Service\FeedService;
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

    private FeedService $feedService;

    private AsyncService $asyncService;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, SubscriptionService $subscriptionService, FeedService $feedService, AsyncService $asyncService)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->subscriptionService = $subscriptionService;
        $this->feedService = $feedService;
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
        $userRepository = $this->entityManager->getRepository(User::class);
        $tweet = $tweetRepository->find($message->getTweetId());
        if (!($tweet instanceof Tweet)) {
            return $this->reject(sprintf('Tweet ID %s was not found', $message->getTweetId()));
        }

        $followerIds = $this->subscriptionService->getFollowerIds($tweet->getAuthor()->getId());

        foreach ($followerIds as $followerId) {
            $this->feedService->putTweet($tweet, $followerId);
            /** @var User $user */
            $user = $userRepository->find($followerId);
            if ($user !== null) {
                $message = (new SendNotificationDTO($followerId, $tweet->getText()))->toAMQPMessage();
                $this->asyncService->publishToExchange(
                    AsyncService::SEND_NOTIFICATION,
                    $message,
                    $user->getPreferred()
                );
            }
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
