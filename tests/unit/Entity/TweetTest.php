<?php

namespace UnitTests\Entity;

use App\Entity\Tweet;
use App\Entity\User;
use DateTime;
use PHPUnit\Framework\TestCase;

class TweetTest extends TestCase
{
    public function tweetDataProvider(): array
    {
        $expectedPositive = [
            'id' => 5,
            'author' => 'Terry Pratchett',
            'text' => 'The Colour of Magic',
            'createdAt' => (new DateTime())->format('Y-m-d h:i:s'),
        ];
        $positiveTweet = $this->addAuthor($this->makeTweet($expectedPositive), $expectedPositive);
        $expectedNoAuthor = [
            'id' => 30,
            'author' => null,
            'text' => 'Unknown book',
            'createdAt' => (new DateTime())->format('Y-m-d h:i:s'),
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
     */
    public function testToFeedReturnsCorrectValues(Tweet $tweet, array $expected, ?int $delay = null): void
    {
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
