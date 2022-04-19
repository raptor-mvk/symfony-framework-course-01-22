<?php

namespace CodeceptionUnitTests\Entity;

use App\Entity\Tweet;
use App\Entity\User;
use Codeception\Test\Unit;
use DateTime;
use Symfony\Bridge\PhpUnit\ClockMock;

class TweetTest extends Unit
{
    private const NOW_TIME = '@now';

    public function tweetDataProvider(): array
    {
        $expectedPositive = [
            'id' => 5,
            'author' => 'Terry Pratchett',
            'text' => 'The Colour of Magic',
            'createdAt' => self::NOW_TIME,
        ];
        $positiveTweet = $this->addAuthor($this->makeTweet($expectedPositive), $expectedPositive);
        $expectedNoAuthor = [
            'id' => 30,
            'author' => null,
            'text' => 'Unknown book',
            'createdAt' => self::NOW_TIME,
        ];
        $expectedNoCreatedAt = [
            'id' => 42,
            'author' => 'Douglas Adams',
            'text' => 'The Hitchhiker\'s Guide to the Galaxy',
            'createdAt' => '',
        ];
        return [
            'positive' => [
                $positiveTweet,
                $expectedPositive,
                0,
            ],
            'no author' => [
                $this->makeTweet($expectedNoAuthor),
                $expectedNoAuthor,
                0
            ],
            'no createdAt' => [
                $this->addAuthor($this->makeTweet($expectedNoCreatedAt), $expectedNoCreatedAt),
                $expectedNoCreatedAt,
                null
            ],
            'positive with delay' => [
                $positiveTweet,
                $expectedPositive,
                2
            ],
        ];
    }

    /**
     * @dataProvider tweetDataProvider
     * @group time-sensitive
     */
    public function testToFeedReturnsCorrectValues(Tweet $tweet, array $expected, ?int $delay = null): void
    {
        ClockMock::register(Tweet::class);
        if ($expected['createdAt'] === self::NOW_TIME) {
            $expected['createdAt'] = DateTime::createFromFormat('U',(string)time())->format('Y-m-d h:i:s');
        }
        $tweet = $this->setCreatedAtWithDelay($tweet, $delay);
        $actual = $tweet->toFeed();

        static::assertSame($expected, $actual, 'Tweet::toFeed should return correct result');
    }

    private function makeTweet(array $data): Tweet
    {
        $tweet = new Tweet();
        $tweet->setId($data['id']);
        $tweet->setText($data['text']);

        return $tweet;
    }

    private function addAuthor(Tweet $tweet, array $data): Tweet
    {
        $author = new User();
        $author->setLogin($data['author']);
        $tweet->setAuthor($author);

        return $tweet;
    }

    private function setCreatedAtWithDelay(Tweet $tweet, ?int $delay = null): Tweet
    {
        if ($delay !== null) {
            \sleep($delay);
            $tweet->setCreatedAt();
        }

        return $tweet;
    }
}
