<?php

declare(strict_types=1);

namespace App\Contracts;

use Psr\Http\Message\ResponseInterface;

interface HttpClientInterface
{
    public function get(string $url): ResponseInterface;
}
