<?php

namespace App\Service;

use App\DTO\SaveUserDTO;
use App\Entity\User;
use App\Manager\SubscriptionManager;
use App\Manager\UserManager;

class SubscriptionService
{
    private UserManager $userManager;

    private SubscriptionManager $subscriptionManager;

    public function __construct(UserManager $userManager, SubscriptionManager $subscriptionManager)
    {
        $this->userManager = $userManager;
        $this->subscriptionManager = $subscriptionManager;
    }

    public function subscribe(int $authorId, int $followerId): bool
    {
        $author = $this->userManager->findUserById($authorId);
        if (!($author instanceof User)) {
            return false;
        }
        $follower = $this->userManager->findUserById($followerId);
        if (!($follower instanceof User)) {
            return false;
        }

        $this->subscriptionManager->addSubscription($author, $follower);

        return true;
    }

    public function addFollowers(User $user, string $followerLogin, int $count): int
    {
        $createdFollowers = 0;
        for ($i = 0; $i < $count; $i++) {
            $login = "{$followerLogin}_#$i";
            $password = $followerLogin;
            $age = $i;
            $isActive = true;
            $data = compact('login', 'password', 'age', 'isActive');
            $followerId = $this->userManager->saveUserFromDTO(new User(), new SaveUserDTO($data));
            if ($followerId !== null) {
                $this->subscribe($user->getId(), $followerId);
                $createdFollowers++;
            }
        }

        return $createdFollowers;
    }
}
