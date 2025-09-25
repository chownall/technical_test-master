<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\HttpClientInterface;
use App\Services\GitlabRepositoryProvider;
use GuzzleHttp\Exception\RequestException;
use JsonException;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;

class GitlabRepositoryProviderTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;
    private GitlabRepositoryProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHttpClient = Mockery::mock(HttpClientInterface::class);
        $this->provider = new GitlabRepositoryProvider($this->mockHttpClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_search_returns_repositories_when_api_succeeds(): void
    {
        // Arrange
        $gitlabApiResponse = [
            [
                'name' => 'linux',
                'path_with_namespace' => 'x1b2j_open_source/linux',
                'description' => 'Linux project',
                'namespace' => [
                    'path' => 'x1b2j_open_source'
                ]
            ],
            [
                'name' => 'linux-utils',
                'path_with_namespace' => 'developer/linux-utils',
                'description' => 'Linux utilities',
                'namespace' => [
                    'path' => 'developer'
                ]
            ]
        ];

        $mockStream = Mockery::mock(StreamInterface::class);
        $mockStream->shouldReceive('getContents')
            ->andReturn(json_encode($gitlabApiResponse));

        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')
            ->andReturn($mockStream);

        $this->mockHttpClient->shouldReceive('get')
            ->with('https://gitlab.com/api/v4/projects?search=linux&per_page=5&order_by=id&sort=asc')
            ->andReturn($mockResponse);

        // Act
        $result = $this->provider->search('linux');

        // Assert
        $this->assertCount(2, $result);
        
        // Vérifier le premier repository
        $this->assertEquals('linux', $result[0]['repository']);
        $this->assertEquals('x1b2j_open_source/linux', $result[0]['full_repository_name']);
        $this->assertEquals('Linux project', $result[0]['description']);
        $this->assertEquals('x1b2j_open_source', $result[0]['creator']);
        
        // Vérifier le second repository
        $this->assertEquals('linux-utils', $result[1]['repository']);
        $this->assertEquals('developer/linux-utils', $result[1]['full_repository_name']);
        $this->assertEquals('Linux utilities', $result[1]['description']);
        $this->assertEquals('developer', $result[1]['creator']);
    }

    public function test_search_returns_empty_array_when_api_throws_guzzle_exception(): void
    {
        // Arrange
        $this->mockHttpClient->shouldReceive('get')
            ->with('https://gitlab.com/api/v4/projects?search=linux&per_page=5&order_by=id&sort=asc')
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
            ->with('https://gitlab.com/api/v4/projects?search=linux&per_page=5&order_by=id&sort=asc')
            ->andReturn($mockResponse);

        // Act
        $result = $this->provider->search('linux');

        // Assert
        $this->assertEmpty($result);
    }

    public function test_search_returns_empty_array_when_no_repositories(): void
    {
        // Arrange
        $gitlabApiResponse = [];

        $mockStream = Mockery::mock(StreamInterface::class);
        $mockStream->shouldReceive('getContents')
            ->andReturn(json_encode($gitlabApiResponse));

        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')
            ->andReturn($mockStream);

        $this->mockHttpClient->shouldReceive('get')
            ->with('https://gitlab.com/api/v4/projects?search=linux&per_page=5&order_by=id&sort=asc')
            ->andReturn($mockResponse);

        // Act
        $result = $this->provider->search('linux');

        // Assert
        $this->assertEmpty($result);
    }

    public function test_search_handles_empty_description(): void
    {
        // Arrange
        $gitlabApiResponse = [
            [
                'name' => 'test-repo',
                'path_with_namespace' => 'user/test-repo',
                'description' => null,
                'namespace' => [
                    'path' => 'user'
                ]
            ]
        ];

        $mockStream = Mockery::mock(StreamInterface::class);
        $mockStream->shouldReceive('getContents')
            ->andReturn(json_encode($gitlabApiResponse));

        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')
            ->andReturn($mockStream);

        $this->mockHttpClient->shouldReceive('get')
            ->with('https://gitlab.com/api/v4/projects?search=test&per_page=5&order_by=id&sort=asc')
            ->andReturn($mockResponse);

        // Act
        $result = $this->provider->search('test');

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('test-repo', $result[0]['repository']);
        $this->assertEquals('user/test-repo', $result[0]['full_repository_name']);
        $this->assertEquals('', $result[0]['description']);
        $this->assertEquals('user', $result[0]['creator']);
    }

    public function test_search_handles_missing_namespace(): void
    {
        // Arrange
        $gitlabApiResponse = [
            [
                'name' => 'test-repo',
                'path_with_namespace' => 'user/test-repo',
                'description' => 'Test repository',
                'namespace' => []
            ]
        ];

        $mockStream = Mockery::mock(StreamInterface::class);
        $mockStream->shouldReceive('getContents')
            ->andReturn(json_encode($gitlabApiResponse));

        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')
            ->andReturn($mockStream);

        $this->mockHttpClient->shouldReceive('get')
            ->with('https://gitlab.com/api/v4/projects?search=test&per_page=5&order_by=id&sort=asc')
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
        $gitlabApiResponse = [];

        $mockStream = Mockery::mock(StreamInterface::class);
        $mockStream->shouldReceive('getContents')
            ->andReturn(json_encode($gitlabApiResponse));

        $mockResponse = Mockery::mock(ResponseInterface::class);
        $mockResponse->shouldReceive('getBody')
            ->andReturn($mockStream);

        $this->mockHttpClient->shouldReceive('get')
            ->with('https://gitlab.com/api/v4/projects?search=linux+kernel&per_page=5&order_by=id&sort=asc')
            ->andReturn($mockResponse);

        // Act
        $result = $this->provider->search('linux kernel');

        // Assert
        $this->assertEmpty($result);
    }
}
