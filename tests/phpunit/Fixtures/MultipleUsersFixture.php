<?php

namespace UnitTests\Fixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MultipleUsersFixture extends Fixture
{
    public const PRATCHETT = 'Terry Pratchett';
    public const TOLKIEN = 'John R.R. Tolkien';
    public const CARROLL = 'Lewis Carrol';
    public const ALL_FOLLOWER = 'Follows all';
    public const CARROLL_PRATCHETT_FOLLOWER = 'Follows Carrol and Pratchett';
    public const CARROLL_TOLKIEN_FOLLOWER = 'Follows Carrol and Tolkien';

    public function load(ObjectManager $manager): void
    {
        $this->addReference(self::PRATCHETT, $this->makeUser($manager, self::PRATCHETT));
        $this->addReference(self::TOLKIEN, $this->makeUser($manager, self::TOLKIEN));
        $this->addReference(self::CARROLL, $this->makeUser($manager, self::CARROLL));
        $this->addReference(self::ALL_FOLLOWER, $this->makeUser($manager, self::ALL_FOLLOWER));
        $this->addReference(
            self::CARROLL_PRATCHETT_FOLLOWER,
            $this->makeUser($manager, self::CARROLL_PRATCHETT_FOLLOWER)
        );
        $this->addReference(
            self::CARROLL_TOLKIEN_FOLLOWER,
            $this->makeUser($manager, self::CARROLL_TOLKIEN_FOLLOWER)
        );
        $manager->flush();
    }

    private function makeUser(ObjectManager $manager, string $login): User
    {
        $user = new User();
        $user->setLogin($login);
        $user->setPassword("{$login}_password");
        $user->setRoles([]);
        $user->setPhone('+1111111111');
        $user->setEmail('user@nomail.com');
        $user->setPreferred('email');
        $user->setAge(100);
        $user->setIsActive(true);
        $manager->persist($user);

        return $user;
    }
}
