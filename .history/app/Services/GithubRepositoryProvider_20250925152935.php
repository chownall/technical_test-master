<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\HttpClientInterface;
use App\Contracts\RepositoryProviderInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

class GithubRepositoryProvider implements RepositoryProviderInterface
{
    public function __construct( // property promotion PHP8
        private HttpClientInterface $httpClient
    ) {}

    public function search(string $query): array
    {
        try {
            $url = 'https://api.github.com/search/repositories?per_page=5&q=' . urlencode($query);
            $response = $this->httpClient->get($url);
            
            $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $repositories = $responseData['items'] ?? [];
            
            return $this->transformGithubResponse($repositories);
        } catch (GuzzleException $e) {
            // Log l'erreur et retourner un tableau vide
            return [];
        } catch (JsonException $e) {
            // Log l'erreur et retourner un tableau vide
            return [];
        }
    }
    
    private function transformGithubResponse(array $repositories): array
    {
        return array_map(function (array $repository): array {
            return [
                'repository' => $repository['name'],
                'full_repository_name' => $repository['full_name'],
                'description' => $repository['description'],
                'creator' => $repository['owner']['login'] ?? $repository['owner']['username'] ?? 'unknown',
            ];
        }, $repositories);
    }
}
