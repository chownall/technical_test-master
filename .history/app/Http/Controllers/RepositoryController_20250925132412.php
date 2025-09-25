<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepositoryController extends Controller
{
    public function __construct(
        private Client $httpClient
    ) {}

    public function search(Request $request): array|JsonResponse
    {
        // Validation des paramètres avec réponses HTTP appropriées
        $query = $request->get('q');
        
        if ($query === null) {
            return response()->json(['error' => 'The query parameter \'q\' must be provided'], 422);
        }
        
        if (!is_string($query)) {
            return response()->json(['error' => 'The query parameter \'q\' must be a string'], 422);
        }
        
        if (strlen($query) > 256) {
            return response()->json(['error' => 'The query parameter \'q\' cannot be longer than 256 chars.'], 422);
        }

        try {
            $url = 'https://api.github.com/search/repositories?per_page=5&q=' . urlencode($query);
            $response = $this->httpClient->get($url);
        } catch (GuzzleException $e) {
            return response()->json(['error' => 'Unable to contact API server.'], 500);
        }

        $responseData = json_decode($response->getBody()->getContents(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Unable to parse JSON response.'], 500);
        }

        $repositories = $responseData['items'] ?? [];
        if (empty($repositories)) {
            return [];
        }

        $return = [];
        foreach ($repositories as $repository) {
            $return[] = [
                'repository' => $repository['name'],
                'full_repository_name' => $repository['full_name'],
                'description' => $repository['description'],
                'creator' => $repository['owner']['login'] ?? $repository['owner']['username'] ?? 'unknown',
            ];
        }

        return $return;
    }
}
