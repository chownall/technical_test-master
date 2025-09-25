<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\HttpClientInterface;
use App\Contracts\RepositoryProviderInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

class GitlabRepositoryProvider implements RepositoryProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function search(string $query): array
    {
        try {
            $url = 'https://gitlab.com/api/v4/projects?search=' . urlencode($query) . '&per_page=5&order_by=id&sort=asc';
            $response = $this->httpClient->get($url);
            
            $repositories = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            
            return $this->transformGitlabResponse($repositories);
        } catch (GuzzleException $e) {
            // Log l'erreur et retourner un tableau vide
            return [];
        } catch (JsonException $e) {
            // Log l'erreur et retourner un tableau vide
            return [];
        }
    }
    
    private function transformGitlabResponse(array $repositories): array
    {
        return array_map(function (array $repository): array {
            return [
                'repository' => $repository['name'],
                'full_repository_name' => $repository['path_with_namespace'],
                'description' => $repository['description'] ?? '',
                'creator' => $repository['namespace']['path'] ?? 'unknown',
            ];
        }, $repositories);
    }
}
