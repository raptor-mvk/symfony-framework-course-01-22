<?php

namespace UnitTests\Service;

use App\Entity\Tweet;
use App\Manager\SubscriptionManager;
use App\Manager\TweetManager;
use App\Manager\UserManager;
use App\Service\AsyncService;
use App\Service\FeedService;
use App\Service\SubscriptionService;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Mockery;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use UnitTests\FixturedTestCase;
use UnitTests\Fixtures\MultipleSubscriptionsFixture;
use UnitTests\Fixtures\MultipleTweetsFixture;
use UnitTests\Fixtures\MultipleUsersFixture;

class FeedServiceTest extends FixturedTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->addFixture(new MultipleUsersFixture());
        $this->addFixture(new MultipleTweetsFixture());
        $this->addFixture(new MultipleSubscriptionsFixture());
        $this->executeFixtures();
    }

    public function getFeedFromTweetsDataProvider(): array
    {
        return [
            'all authors, all tweets' => [
                MultipleUsersFixture::ALL_FOLLOWER,
                6,
                [
                    'Through the Looking-Glass',
                    'Alice in Wonderland',
                    'Soul Music',
                    'Lords of the Rings',
                    'Colours of Magic',
                    'Hobbit',
                ]
            ]
        ];
    }

    /**
     * @dataProvider getFeedFromTweetsDataProvider
     */
    public function testGetFeedFromTweetsReturnsCorrectResult(string $followerLogin, int $count, array $expected): void
    {
        /** @var UserPasswordHasherInterface $encoder */
        $encoder = self::getContainer()->get('security.password_hasher');
        /** @var TagAwareCacheInterface $cache */
        $cache = self::getContainer()->get('redis_adapter');
        /** @var PaginatedFinderInterface $finder */
        $finder = Mockery::mock(PaginatedFinderInterface::class);
        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::getContainer()->get('form.factory');
        $userManager = new UserManager($this->getDoctrineManager(), $formFactory, $encoder, $finder);
        $subscriptionManager = new SubscriptionManager($this->getDoctrineManager());
        $tweetManager = new TweetManager($this->getDoctrineManager(), $cache);
        $subscriptionService = new SubscriptionService($userManager, $subscriptionManager);
        $feedService = new FeedService(
            $this->getDoctrineManager(),
            $subscriptionService,
            Mockery::mock(AsyncService::class),
            $tweetManager
        );
        $follower= $userManager->findUserByLogin($followerLogin);

        $feed = $feedService->getFeedFromTweets($follower->getId(), $count);

        self::assertSame($expected, array_map(static fn(Tweet $tweet) => $tweet->getText(), $feed));
    }
}
