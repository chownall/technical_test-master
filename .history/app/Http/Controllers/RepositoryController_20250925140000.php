<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchRepositoryRequest;
use App\Services\RepositorySearchService;

class RepositoryController extends Controller
{
    public function __construct(
        private RepositorySearchService $searchService
    ) {}

    public function search(SearchRepositoryRequest $request): array
    {
        return $this->searchService->search($request->validated()['q']);
    }
}
