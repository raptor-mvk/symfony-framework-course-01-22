<?php

namespace UnitTests\Fixtures;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MultipleSubscriptionsFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /** @var User $pratchett */
        $pratchett = $this->getReference(MultipleUsersFixture::PRATCHETT);
        /** @var User $tolkien */
        $tolkien = $this->getReference(MultipleUsersFixture::TOLKIEN);
        /** @var User $carroll */
        $carroll = $this->getReference(MultipleUsersFixture::CARROLL);
        /** @var User $allFollower */
        $allFollower = $this->getReference(MultipleUsersFixture::ALL_FOLLOWER);
        /** @var User $carrollPratchettFollower */
        $carrollPratchettFollower = $this->getReference(MultipleUsersFixture::CARROLL_PRATCHETT_FOLLOWER);
        /** @var User $carrollTolkienFollower */
        $carrollTolkienFollower = $this->getReference(MultipleUsersFixture::CARROLL_TOLKIEN_FOLLOWER);
        $this->makeSubscription($manager, $pratchett, $allFollower);
        $this->makeSubscription($manager, $pratchett, $carrollPratchettFollower);
        $this->makeSubscription($manager, $tolkien, $allFollower);
        $this->makeSubscription($manager, $tolkien, $carrollTolkienFollower);
        $this->makeSubscription($manager, $carroll, $allFollower);
        $this->makeSubscription($manager, $carroll, $carrollPratchettFollower);
        $this->makeSubscription($manager, $carroll, $carrollTolkienFollower);
        $manager->flush();
    }

    private function makeSubscription(ObjectManager $manager, User $author, User $follower): void
    {
        $subscription = new Subscription();
        $subscription->setAuthor($author);
        $subscription->setFollower($follower);
        $manager->persist($subscription);
    }
}
