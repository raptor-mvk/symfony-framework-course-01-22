<?php

namespace App\Client;

use Domnikl\Statsd\Client;
use Domnikl\Statsd\Connection\UdpSocket;

class StatsdAPIClient
{
    private const DEFAULT_SAMPLE_RATE = 1.0;

    private Client $client;

    public function __construct(string $host, int $port, string $namespace)
    {
        $connection = new UdpSocket($host, $port);
        $this->client = new Client($connection, $namespace);
    }

    public function increment(string $key, ?float $sampleRate = null, ?array $tags = null): void
    {
        $this->client->increment($key, $sampleRate ?? self::DEFAULT_SAMPLE_RATE, $tags ?? []);
    }
}
