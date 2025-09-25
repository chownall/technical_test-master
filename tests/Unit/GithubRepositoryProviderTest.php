<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\HttpClientInterface;
use App\Services\GithubRepositoryProvider;
use GuzzleHttp\Exception\RequestException;
use JsonException;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;

class GithubRepositoryProviderTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;
    private GithubRepositoryProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHttpClient = Mockery::mock(HttpClientInterface::class);
        $this->provider = new GithubRepositoryProvider($this->mockHttpClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_returns_repositories_when_api_succeeds(): void
    {
        // Arrange
        $githubApiResponse = [
            'items' => [
                [
                    'name' => 'linux',
                    'full_name' => 'torvalds/linux',
                    'description' => 'Linux kernel source tree',
                    'owner' => [
                        'login' => 'torvalds',
                        'username' => 'torvalds'
                    ]
                ],
                [
                    'name' => 'linux-command',
                    'full_name' => 'jaywcjlove/linux-command',
                    'description' => 'Linux命令大全搜索工具',
                    'owner' => [
                        'login' => 'jaywcjlove'
                    ]
                ]
            ]
        ];

        $mockStream = Mockery::mock(StreamInterface::class);
        $mockStream->shouldReceive('getContents')
            ->andReturn(json_encode($githubApiResponse));

        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')
            ->andReturn($mockStream);

        $this->mockHttpClient->shouldReceive('get')
            ->with('https://api.github.com/search/repositories?per_page=5&q=linux')
            ->andReturn($mockResponse);

        // Act
        $result = $this->provider->search('linux');

        // Assert
        $this->assertCount(2, $result);
        
        // Vérifier le premier repository
        $this->assertEquals('linux', $result[0]['repository']);
        $this->assertEquals('torvalds/linux', $result[0]['full_repository_name']);
        $this->assertEquals('Linux kernel source tree', $result[0]['description']);
        $this->assertEquals('torvalds', $result[0]['creator']);
        
        // Vérifier le second repository
        $this->assertEquals('linux-command', $result[1]['repository']);
        $this->assertEquals('jaywcjlove/linux-command', $result[1]['full_repository_name']);
        $this->assertEquals('Linux命令大全搜索工具', $result[1]['description']);
        $this->assertEquals('jaywcjlove', $result[1]['creator']);
    }

    public function test_search_returns_empty_array_when_api_throws_guzzle_exception(): void
    {
        // Arrange
        $this->mockHttpClient->shouldReceive('get')
            ->with('https://api.github.com/search/repositories?per_page=5&q=linux')
            ->andThrow(new RequestException('API Error', Mockery::mock(\Psr\Http\Message\RequestInterface::class)));

        // Act
        $result = $this->provider->search('linux');

        // Assert
        $this->assertEmpty($result);
    }

    public function test_search_returns_empty_array_when_json_decode_fails(): void
    {
        // Arrange
        $mockStream = Mockery::mock(StreamInterface::class);
        $mockStream->shouldReceive('getContents')
            ->andReturn('invalid json');

        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')
            ->andReturn($mockStream);

        $this->mockHttpClient->shouldReceive('get')
            ->with('https://api.github.com/search/repositories?per_page=5&q=linux')
            ->andReturn($mockResponse);

        // Act
        $result = $this->provider->search('linux');

        // Assert
        $this->assertEmpty($result);
    }

    public function test_search_returns_empty_array_when_no_items_in_response(): void
    {
        // Arrange
        $githubApiResponse = [
            'items' => []
        ];

        $mockStream = Mockery::mock(StreamInterface::class);
        $mockStream->shouldReceive('getContents')
            ->andReturn(json_encode($githubApiResponse));

        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')
            ->andReturn($mockStream);

        $this->mockHttpClient->shouldReceive('get')
            ->with('https://api.github.com/search/repositories?per_page=5&q=linux')
            ->andReturn($mockResponse);

        // Act
        $result = $this->provider->search('linux');

        // Assert
        $this->assertEmpty($result);
    }

    public function test_search_handles_owner_with_username_fallback(): void
    {
        // Arrange
        $githubApiResponse = [
            'items' => [
                [
                    'name' => 'test-repo',
                    'full_name' => 'user/test-repo',
                    'description' => 'Test repository',
                    'owner' => [
                        'username' => 'user',
                        'login' => 'user'
                    ]
                ]
            ]
        ];

        $mockStream = Mockery::mock(StreamInterface::class);
        $mockStream->shouldReceive('getContents')
            ->andReturn(json_encode($githubApiResponse));

        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')
            ->andReturn($mockStream);

        $this->mockHttpClient->shouldReceive('get')
            ->with('https://api.github.com/search/repositories?per_page=5&q=test')
            ->andReturn($mockResponse);

        // Act
        $result = $this->provider->search('test');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('test-repo', $result[0]['repository']);
        $this->assertEquals('user/test-repo', $result[0]['full_repository_name']);
        $this->assertEquals('Test repository', $result[0]['description']);
        $this->assertEquals('user', $result[0]['creator']);
    }

    public function test_search_handles_owner_without_username_or_login(): void
    {
        // Arrange
        $githubApiResponse = [
            'items' => [
                [
                    'name' => 'test-repo',
                    'full_name' => 'user/test-repo',
                    'description' => 'Test repository',
                    'owner' => []
                ]
            ]
        ];

        $mockStream = Mockery::mock(StreamInterface::class);
        $mockStream->shouldReceive('getContents')
            ->andReturn(json_encode($githubApiResponse));

        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')
            ->andReturn($mockStream);

        $this->mockHttpClient->shouldReceive('get')
            ->with('https://api.github.com/search/repositories?per_page=5&q=test')
            ->andReturn($mockResponse);

        // Act
        $result = $this->provider->search('test');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('test-repo', $result[0]['repository']);
        $this->assertEquals('user/test-repo', $result[0]['full_repository_name']);
        $this->assertEquals('Test repository', $result[0]['description']);
        $this->assertEquals('unknown', $result[0]['creator']);
    }

    public function test_search_url_encoding(): void
    {
        // Arrange
        $githubApiResponse = ['items' => []];

        $mockStream = Mockery::mock(StreamInterface::class);
        $mockStream->shouldReceive('getContents')
            ->andReturn(json_encode($githubApiResponse));

        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')
            ->andReturn($mockStream);

        $this->mockHttpClient->shouldReceive('get')
            ->with('https://api.github.com/search/repositories?per_page=5&q=linux+kernel')
            ->andReturn($mockResponse);

        // Act
        $result = $this->provider->search('linux kernel');

        // Assert
        $this->assertEmpty($result);
    }
}
