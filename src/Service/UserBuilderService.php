<?php

namespace App\Service;

use App\Entity\User;
use App\Manager\TweetManager;
use App\Manager\UserManager;

class UserBuilderService
{
    private TweetManager $tweetManager;

    private UserManager $userManager;

    public function __construct(TweetManager $tweetManager, UserManager $userManager)
    {
        $this->tweetManager = $tweetManager;
        $this->userManager = $userManager;
    }

    /**
     * @param string[] $texts
     */
    public function createUserWithTweets(string $login, array $texts): User
    {
        $user = $this->userManager->create($login);
        foreach ($texts as $text) {
            $this->tweetManager->postTweet($user, $text);
        }

        return $user;
    }

    /**
     * @return User[]
     */
    public function createUserWithFollower(string $login, string $followerLogin): array
    {
        $user = $this->userManager->create($login);
        $follower = $this->userManager->create($followerLogin);
        $this->userManager->subscribeUser($user, $follower);

        return [$user, $follower];
    }
}
