<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\HttpClientInterface;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class GuzzleHttpClient implements HttpClientInterface
{
    public function __construct(
        private Client $client
    ) {}

    public function get(string $url): ResponseInterface
    {
        return $this->client->get($url);
    }
}
