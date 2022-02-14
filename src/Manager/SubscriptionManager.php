<?php

namespace App\Manager;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addSubscription(User $author, User $follower): void
    {
        $subscription = new Subscription();
        $subscription->setAuthor($author);
        $subscription->setFollower($follower);
        $subscription->setCreatedAt();
        $subscription->setUpdatedAt();
        $author->addSubscriptionFollower($subscription);
        $follower->addSubscriptionAuthor($subscription);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();
    }

}
