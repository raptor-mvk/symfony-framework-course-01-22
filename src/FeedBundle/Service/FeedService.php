<?php

namespace FeedBundle\Service;

use FeedBundle\DTO\TweetDTO;
use FeedBundle\Entity\Feed;
use Doctrine\ORM\EntityManagerInterface;

class FeedService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getFeed(int $userId, int $count): array
    {
        $feed = $this->getFeedFromRepository($userId);

        return $feed === null ? [] : array_slice($feed->getTweets(), -$count);
    }

    public function putTweet(TweetDTO $tweetDTO, int $userId): bool
    {
        $feed = $this->getFeedFromRepository($userId);
        if ($feed === null) {
            return false;
        }
        $tweets = $feed->getTweets();
        $tweets[] = $tweetDTO->getPayload();
        $feed->setTweets($tweets);
        $this->entityManager->persist($feed);
        $this->entityManager->flush();

        return true;
    }

    private function getFeedFromRepository(int $userId): ?Feed
    {
        $feedRepository = $this->entityManager->getRepository(Feed::class);
        $feed = $feedRepository->findOneBy(['readerId' => $userId]);
        if (!($feed instanceof Feed)) {
            $feed = new Feed();
            $feed->setReaderId($userId);
            $feed->setTweets([]);
        }

        return $feed;
    }
}
