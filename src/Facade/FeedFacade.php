<?php

namespace App\Facade;

use App\Client\FeedClient;

class FeedFacade
{
    private FeedClient $feedClient;

    public function __construct(FeedClient $feedClient)
    {
        $this->feedClient = $feedClient;
    }

    public function getFeed(int $userId, int $count): array
    {
        return $this->feedClient->getFeed($userId, $count);
    }
}
