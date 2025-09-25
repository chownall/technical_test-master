<?php

declare(strict_types=1);

namespace App\Contracts;

interface RepositoryProviderInterface
{
    public function search(string $query): array;
}
