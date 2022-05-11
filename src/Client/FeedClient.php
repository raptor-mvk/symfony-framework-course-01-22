<?php

namespace App\Client;

use GuzzleHttp\Client;

class FeedClient
{
    private Client $client;

    private string $baseUrl;

    public function __construct(Client $client, string $baseUrl)
    {
        $this->client = $client;
        $this->baseUrl = $baseUrl;
    }

    public function getFeed(int $userId, int $count): array
    {
        $response = $this->client->get("{$this->baseUrl}/server-api/v1/get-feed", [
            'query' => [
                'userId' => $userId,
                'count' => $count,
            ],
        ]);
        $responseData = json_decode($response->getBody(), true);

        return $responseData['tweets'];
    }
}
