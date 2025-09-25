<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RepositoryProviderInterface;

class RepositorySearchService
{
    public function __construct(
        private GithubRepositoryProvider $githubProvider,
        private GitlabRepositoryProvider $gitlabProvider
    ) {}
    
    public function search(string $query): array
    {
        $githubResults = $this->githubProvider->search($query);
        $gitlabResults = $this->gitlabProvider->search($query);
        
        // Fusionner les résultats : Gitlab d'abord, puis GitHub (comme attendu par les tests)
        return array_merge($gitlabResults, $githubResults);
    }
}
