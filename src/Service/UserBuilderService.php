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
}
