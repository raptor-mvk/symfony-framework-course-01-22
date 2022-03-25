<?php

namespace App\Manager;

use App\Entity\Tweet;
use App\Entity\User;
use App\Repository\TweetRepository;
use Doctrine\ORM\EntityManagerInterface;

class TweetManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
     */
    public function getTweets(int $page, int $perPage): array
    {
        /** @var TweetRepository $TweetRepository */
        $tweetRepository = $this->entityManager->getRepository(Tweet::class);

        return $tweetRepository->getTweets($page, $perPage);
    }
}
