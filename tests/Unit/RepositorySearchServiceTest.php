<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\GithubRepositoryProvider;
use App\Services\GitlabRepositoryProvider;
use App\Services\RepositorySearchService;
use Mockery;
use Tests\TestCase;

class RepositorySearchServiceTest extends TestCase
{
    private GithubRepositoryProvider $mockGithubProvider;
    private GitlabRepositoryProvider $mockGitlabProvider;
    private RepositorySearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockGithubProvider = Mockery::mock(GithubRepositoryProvider::class);
        $this->mockGitlabProvider = Mockery::mock(GitlabRepositoryProvider::class);
        $this->service = new RepositorySearchService($this->mockGithubProvider, $this->mockGitlabProvider);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_merges_results_from_both_providers(): void
    {
        // Arrange
        $githubResults = [
            [
                'repository' => 'linux',
                'full_repository_name' => 'torvalds/linux',
                'description' => 'Linux kernel source tree',
                'creator' => 'torvalds'
            ],
            [
                'repository' => 'linux-utils',
                'full_repository_name' => 'user/linux-utils',
                'description' => 'Linux utilities',
                'creator' => 'user'
            ]
        ];

        $gitlabResults = [
            [
                'repository' => 'linux-project',
                'full_repository_name' => 'gitlab/linux-project',
                'description' => 'Linux project on Gitlab',
                'creator' => 'gitlab'
            ]
        ];

        $this->mockGithubProvider->shouldReceive('search')
            ->with('linux')
            ->andReturn($githubResults);

        $this->mockGitlabProvider->shouldReceive('search')
            ->with('linux')
            ->andReturn($gitlabResults);

        // Act
        $result = $this->service->search('linux');

        // Assert
        $this->assertCount(3, $result);
        
        // Vérifier l'ordre : Gitlab d'abord, puis GitHub
        $this->assertEquals('linux-project', $result[0]['repository']);
        $this->assertEquals('gitlab/linux-project', $result[0]['full_repository_name']);
        $this->assertEquals('gitlab', $result[0]['creator']);
        
        $this->assertEquals('linux', $result[1]['repository']);
        $this->assertEquals('torvalds/linux', $result[1]['full_repository_name']);
        $this->assertEquals('torvalds', $result[1]['creator']);
        
        $this->assertEquals('linux-utils', $result[2]['repository']);
        $this->assertEquals('user/linux-utils', $result[2]['full_repository_name']);
        $this->assertEquals('user', $result[2]['creator']);
    }

    public function test_search_returns_only_github_results_when_gitlab_fails(): void
    {
        // Arrange
        $githubResults = [
            [
                'repository' => 'linux',
                'full_repository_name' => 'torvalds/linux',
                'description' => 'Linux kernel source tree',
                'creator' => 'torvalds'
            ]
        ];

        $this->mockGithubProvider->shouldReceive('search')
            ->with('linux')
            ->andReturn($githubResults);

        $this->mockGitlabProvider->shouldReceive('search')
            ->with('linux')
            ->andReturn([]);

        // Act
        $result = $this->service->search('linux');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('linux', $result[0]['repository']);
        $this->assertEquals('torvalds/linux', $result[0]['full_repository_name']);
    }

    public function test_search_returns_only_gitlab_results_when_github_fails(): void
    {
        // Arrange
        $gitlabResults = [
            [
                'repository' => 'linux-project',
                'full_repository_name' => 'gitlab/linux-project',
                'description' => 'Linux project on Gitlab',
                'creator' => 'gitlab'
            ]
        ];

        $this->mockGithubProvider->shouldReceive('search')
            ->with('linux')
            ->andReturn([]);

        $this->mockGitlabProvider->shouldReceive('search')
            ->with('linux')
            ->andReturn($gitlabResults);

        // Act
        $result = $this->service->search('linux');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('linux-project', $result[0]['repository']);
        $this->assertEquals('gitlab/linux-project', $result[0]['full_repository_name']);
    }

    public function test_search_returns_empty_array_when_both_providers_fail(): void
    {
        // Arrange
        $this->mockGithubProvider->shouldReceive('search')
            ->with('linux')
            ->andReturn([]);

        $this->mockGitlabProvider->shouldReceive('search')
            ->with('linux')
            ->andReturn([]);

        // Act
        $result = $this->service->search('linux');

        // Assert
        $this->assertEmpty($result);
    }

    public function test_search_passes_query_to_both_providers(): void
    {
        // Arrange
        $this->mockGithubProvider->shouldReceive('search')
            ->with('test query')
            ->andReturn([]);

        $this->mockGitlabProvider->shouldReceive('search')
            ->with('test query')
            ->andReturn([]);

        // Act
        $this->service->search('test query');

        // Assert - Les mocks vérifient automatiquement que les méthodes ont été appelées avec les bons paramètres
        $this->assertTrue(true);
    }

    public function test_search_handles_duplicate_repository_names(): void
    {
        // Arrange
        $githubResults = [
            [
                'repository' => 'linux',
                'full_repository_name' => 'torvalds/linux',
                'description' => 'Linux kernel source tree',
                'creator' => 'torvalds'
            ]
        ];

        $gitlabResults = [
            [
                'repository' => 'linux',
                'full_repository_name' => 'gitlab/linux',
                'description' => 'Linux project on Gitlab',
                'creator' => 'gitlab'
            ]
        ];

        $this->mockGithubProvider->shouldReceive('search')
            ->with('linux')
            ->andReturn($githubResults);

        $this->mockGitlabProvider->shouldReceive('search')
            ->with('linux')
            ->andReturn($gitlabResults);

        // Act
        $result = $this->service->search('linux');

        // Assert
        $this->assertCount(2, $result);
        
        // Vérifier que les deux repositories sont présents malgré le même nom
        $this->assertEquals('gitlab/linux', $result[0]['full_repository_name']);
        $this->assertEquals('torvalds/linux', $result[1]['full_repository_name']);
    }
}
