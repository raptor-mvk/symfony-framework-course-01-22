<?php

namespace UnitTests\Fixtures;

use App\Entity\Tweet;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MultipleTweetsFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /** @var User $pratchett */
        $pratchett = $this->getReference(MultipleUsersFixture::PRATCHETT);
        /** @var User $tolkien */
        $tolkien = $this->getReference(MultipleUsersFixture::TOLKIEN);
        /** @var User $carroll */
        $carroll = $this->getReference(MultipleUsersFixture::CARROLL);
        $this->makeTweet($manager, $tolkien, 'Hobbit');
        $this->makeTweet($manager, $pratchett, 'Colours of Magic');
        $this->makeTweet($manager, $tolkien, 'Lords of the Rings');
        $this->makeTweet($manager, $pratchett, 'Soul Music');
        $this->makeTweet($manager, $carroll, 'Alice in Wonderland');
        $this->makeTweet($manager, $pratchett, 'Through the Looking-Glass');
        $manager->flush();
    }

    private function makeTweet(ObjectManager $manager, User $author, string $text): void
    {
        $tweet = new Tweet();
        $tweet->setAuthor($author);
        $tweet->setText($text);
        $manager->persist($tweet);
        sleep(1);
    }
}
