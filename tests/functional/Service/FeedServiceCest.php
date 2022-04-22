<?php

namespace IntegrationTests\Service;

use App\Entity\Subscription;
use App\Entity\Tweet;
use App\Entity\User;
use App\Tests\FunctionalTester;
use Codeception\Example;

class FeedServiceCest
{
    private const PRATCHETT_AUTHOR = 'Terry Pratchett';
    private const TOLKIEN_AUTHOR = 'John R.R. Tolkien';
    private const CARROLL_AUTHOR = 'Lewis Carrol';
    private const TOLKIEN1_TEXT = 'Hobbit';
    private const PRATCHETT1_TEXT = 'Colours of Magic';
    private const TOLKIEN2_TEXT = 'Lord of the Rings';
    private const PRATCHETT2_TEXT = 'Soul Music';
    private const CARROL1_TEXT = 'Alice in Wonderland';
    private const CARROL2_TEXT = 'Through the Looking-Glass';

    public function getFeedFromTweetsDataProvider(): array
    {
        return [
            'all authors, all tweets' => [
                'authors' => [self::TOLKIEN_AUTHOR, self::CARROLL_AUTHOR, self::PRATCHETT_AUTHOR],
                'tweetsCount' => 6,
                'expected' => [
                    self::CARROL2_TEXT,
                    self::CARROL1_TEXT,
                    self::TOLKIEN2_TEXT,
                    self::TOLKIEN1_TEXT,
                    self::PRATCHETT2_TEXT,
                    self::PRATCHETT1_TEXT,
                ]
            ]
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $pratchett = $I->have(User::class, ['login' => self::PRATCHETT_AUTHOR]);
        $tolkien = $I->have(User::class, ['login' => self::TOLKIEN_AUTHOR]);
        $carroll = $I->have(User::class, ['login' => self::CARROLL_AUTHOR]);
        $I->have(Tweet::class, ['author' => $pratchett, 'text' => self::PRATCHETT1_TEXT]);
        sleep(1);
        $I->have(Tweet::class, ['author' => $pratchett, 'text' => self::PRATCHETT2_TEXT]);
        sleep(1);
        $I->have(Tweet::class, ['author' => $tolkien, 'text' => self::TOLKIEN1_TEXT]);
        sleep(1);
        $I->have(Tweet::class, ['author' => $tolkien, 'text' => self::TOLKIEN2_TEXT]);
        sleep(1);
        $I->have(Tweet::class, ['author' => $carroll, 'text' => self::CARROL1_TEXT]);
        sleep(1);
        $I->have(Tweet::class, ['author' => $carroll, 'text' => self::CARROL2_TEXT]);
    }

    /**
     * @dataProvider getFeedFromTweetsDataProvider
     */
    public function testGetFeedFromTweetsReturnsCorrectResult(FunctionalTester $I, Example $example): void
    {
        $follower = $I->have(User::class);
        foreach ($example['authors'] as $authorLogin) {
            $author = $I->grabEntityFromRepository(User::class, ['login' => $authorLogin]);
            $I->have(Subscription::class, ['author' => $author, 'follower' => $follower]);
        }
        $feedService = $I->grabService('App\Service\FeedService');

        $feed = $feedService->getFeedFromTweets($follower->getId(), $example['tweetsCount']);

        $I->assertSame($example['expected'], array_map(static fn(Tweet $tweet) => $tweet->getText(), $feed));
    }
}
