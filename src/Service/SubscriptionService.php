<?php

namespace App\Service;

use App\DTO\AddFollowersDTO;
use App\DTO\SaveUserDTO;
use App\Entity\Subscription;
use App\Entity\User;
use App\Manager\SubscriptionManager;
use App\Manager\UserManager;
use JsonException;

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
            $phone = '+'.str_pad((string)abs(crc32($login)), 10, '0');
            $email = "$login@gmail.com";
            $preferred = random_int(0, 1) === 1 ? User::EMAIL_NOTIFICATION : User::SMS_NOTIFICATION;
            $data = compact('login', 'password', 'age', 'isActive', 'phone', 'email', 'preferred');
            $followerId = $this->userManager->saveUserFromDTO(new User(), new SaveUserDTO($data));
            if ($followerId !== null) {
                $this->subscribe($user->getId(), $followerId);
                $createdFollowers++;
            }
        }

        return $createdFollowers;
    }

    /**
     * @return string[]
     *
     * @throws JsonException
     */
    public function getFollowersMessages(User $user, string $followerLogin, int $count): array
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = (new AddFollowersDTO($user->getId(), "$followerLogin #$i", 1))->toAMQPMessage();
        }

        return $result;
    }

    /**
     * @return int[]
     */
    public function getFollowerIds(int $authorId): array
    {
        $subscriptions = $this->getSubscriptionsByAuthorId($authorId);
        $mapper = static function(Subscription $subscription) {
            return $subscription->getFollower()->getId();
        };

        return array_map($mapper, $subscriptions);
    }

    /**
     * @return Subscription[]
     */
    private function getSubscriptionsByAuthorId(int $authorId): array
    {
        $author = $this->userManager->findUserById($authorId);
        if (!($author instanceof User)) {
            return [];
        }
        return $this->subscriptionManager->findAllByAuthor($author);
    }
}
