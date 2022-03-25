<?php

namespace App\Manager;

use App\Entity\Tweet;
use App\Entity\User;
use App\Repository\TweetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;

class TweetManager
{
    private EntityManagerInterface $entityManager;

    private CacheItemPoolInterface $cacheItemPool;

    public function __construct(EntityManagerInterface $entityManager, CacheItemPoolInterface $cacheItemPool)
    {
        $this->entityManager = $entityManager;
        $this->cacheItemPool = $cacheItemPool;
    }

    public function postTweet(User $author, string $text): void
    {
        $tweet = new Tweet();
        $tweet->setAuthor($author);
        $tweet->setText($text);
        $tweet->setCreatedAt();
        $tweet->setUpdatedAt();
        $author->addTweet($tweet);
        $this->entityManager->persist($tweet);
        $this->entityManager->flush();
    }

    /**
     * @return Tweet[]
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getTweets(int $page, int $perPage): array
    {
        /** @var TweetRepository $tweetRepository */
        $tweetRepository = $this->entityManager->getRepository(Tweet::class);

        $tweetsItem = $this->cacheItemPool->getItem("tweets_{$page}_{$perPage}");
        if (!$tweetsItem->isHit()) {
            $tweets = $tweetRepository->getTweets($page, $perPage);
            $tweetsItem->set(array_map(static fn(Tweet $tweet) => $tweet->toArray(), $tweets));
            $this->cacheItemPool->save($tweetsItem);
        }

        return $tweetsItem->get();
    }
}
