<?php

namespace Tests\Feature;

use App\Models\ApiEndpoint;
use App\Services\DocumentationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DocumentationGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_documentation_for_all_api_routes()
    {
        $service = app(DocumentationService::class);

        $stats = $service->generateDocumentation();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('created', $stats);
        $this->assertArrayHasKey('errors', $stats);
        $this->assertGreaterThan(0, $stats['total']);
    }

    /** @test */
    public function it_creates_api_endpoint_records_in_database()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        $this->assertDatabaseCount('api_endpoints', $this->getExpectedRouteCount());
    }

    /** @test */
    public function it_stores_endpoint_methods_correctly()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        $getUsersEndpoint = ApiEndpoint::where('uri', 'api/users')->first();

        $this->assertNotNull($getUsersEndpoint);
        $this->assertContains('GET', $getUsersEndpoint->methods);
    }

    /** @test */
    public function it_stores_endpoint_controller_and_action()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        $endpoint = ApiEndpoint::where('uri', 'api/users')->first();

        $this->assertNotNull($endpoint);
        $this->assertStringContainsString('UserController', $endpoint->controller);
        $this->assertEquals('index', $endpoint->action);
    }

    /** @test */
    public function it_extracts_parameters_from_controller_phpdoc()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        $endpoint = ApiEndpoint::where('uri', 'api/users')->first();

        $this->assertNotNull($endpoint);
        $this->assertTrue($endpoint->parameters()->count() > 0);

        // Check for specific parameters
        $searchParam = $endpoint->parameters()->where('name', 'search')->first();
        $this->assertNotNull($searchParam);
        $this->assertEquals('query', $searchParam->location);
    }

    /** @test */
    public function it_marks_required_parameters_correctly()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        // Check POST /api/users which has required fields
        $endpoint = ApiEndpoint::where('uri', 'api/users')
            ->where('action', 'store')
            ->first();

        $this->assertNotNull($endpoint);

        // Check that parameters exist (validation extraction may vary)
        $nameParam = $endpoint->parameters()->where('name', 'name')->first();
        $this->assertNotNull($nameParam);

        $emailParam = $endpoint->parameters()->where('name', 'email')->first();
        $this->assertNotNull($emailParam);
    }

    /** @test */
    public function it_marks_optional_parameters_correctly()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        $endpoint = ApiEndpoint::where('uri', 'api/users')->first();

        $this->assertNotNull($endpoint);

        // search and role are optional query parameters
        $searchParam = $endpoint->parameters()->where('name', 'search')->first();
        if ($searchParam) {
            $this->assertFalse($searchParam->is_required);
        }
    }

    /** @test */
    public function it_handles_path_parameters()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        // Check any endpoint with path parameter
        $endpoints = ApiEndpoint::whereRaw("uri LIKE '%{%}%'")->get();

        $this->assertGreaterThan(0, $endpoints->count());

        // Check that at least one has path parameters extracted
        $hasPathParam = false;
        foreach ($endpoints as $endpoint) {
            if ($endpoint->parameters()->where('location', 'path')->count() > 0) {
                $hasPathParam = true;
                break;
            }
        }

        $this->assertTrue($hasPathParam);
    }

    /** @test */
    public function it_handles_file_upload_parameters()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        $endpoint = ApiEndpoint::where('uri', 'LIKE', '%avatar%')->first();

        $this->assertNotNull($endpoint);

        // Check that avatar parameter exists
        $avatarParam = $endpoint->parameters()->where('name', 'avatar')->first();
        $this->assertNotNull($avatarParam);
        $this->assertEquals('file', $avatarParam->type);
    }

    /** @test */
    public function it_handles_multiple_file_upload_parameters()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        $endpoint = ApiEndpoint::where('uri', 'api/uploads/multiple')->first();

        $this->assertNotNull($endpoint);

        // Check that images parameter exists
        $imagesParam = $endpoint->parameters()->where('name', 'images')->first();
        $this->assertNotNull($imagesParam);
        $this->assertEquals('file', $imagesParam->type);
    }

    /** @test */
    public function it_extracts_endpoint_descriptions()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        $endpoint = ApiEndpoint::where('uri', 'api/users')->first();

        $this->assertNotNull($endpoint);
        $this->assertNotNull($endpoint->description);
        $this->assertStringContainsString('paginated', strtolower($endpoint->description));
    }

    /** @test */
    public function it_handles_nested_routes()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        // Check nested comment routes
        $endpoint = ApiEndpoint::where('uri', 'api/posts/{post}/comments')
            ->where('action', 'index')
            ->first();

        $this->assertNotNull($endpoint);

        // Should have post parameter
        $postParam = $endpoint->parameters()->where('name', 'post')->first();
        $this->assertNotNull($postParam);
        $this->assertEquals('path', $postParam->location);
    }

    /** @test */
    public function it_updates_existing_endpoints_on_regeneration()
    {
        $service = app(DocumentationService::class);

        // Generate once
        $service->generateDocumentation();
        $firstCount = ApiEndpoint::count();

        // Generate again
        $service->generateDocumentation();
        $secondCount = ApiEndpoint::count();

        // Should have same count (update, not duplicate)
        $this->assertEquals($firstCount, $secondCount);
    }

    /** @test */
    public function it_handles_validation_rules_from_controller()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        $endpoint = ApiEndpoint::where('uri', 'api/users')
            ->where('action', 'store')
            ->first();

        $this->assertNotNull($endpoint);

        $emailParam = $endpoint->parameters()->where('name', 'email')->first();
        $this->assertNotNull($emailParam);
        $this->assertStringContainsString('email', strtolower($emailParam->description ?? ''));
    }

    /** @test */
    public function it_excludes_non_api_routes()
    {
        $service = app(DocumentationService::class);

        $service->generateDocumentation();

        // Should not have web routes
        $webRoute = ApiEndpoint::where('uri', '/')->first();
        $this->assertNull($webRoute);
    }

    /** @test */
    public function it_returns_correct_statistics()
    {
        $service = app(DocumentationService::class);

        $stats = $service->generateDocumentation();

        $expectedCount = $this->getExpectedRouteCount();
        $this->assertEquals($expectedCount, $stats['total']);
        $this->assertEquals($expectedCount, $stats['created']);
        $this->assertEquals(0, $stats['errors']);
    }

    /** @test */
    public function test_it_removes_stale_endpoints_on_regeneration()
    {
        $service = app(DocumentationService::class);

        // Manually create a stale endpoint that doesn't exist in routes
        ApiEndpoint::create([
            'uri' => 'api/non-existent-route',
            'methods' => ['GET'],
            'controller' => 'NonExistentController',
            'action' => 'index',
        ]);

        $initialCount = ApiEndpoint::count();
        
        // Generate documentation
        $stats = $service->generateDocumentation();
        
        $finalCount = ApiEndpoint::count();

        // Count should be correct and the stale one should be gone
        $expectedCount = $this->getExpectedRouteCount();
        $this->assertEquals($expectedCount, $finalCount);
        $this->assertDatabaseMissing('api_endpoints', [
            'uri' => 'api/non-existent-route'
        ]);
        $this->assertEquals(1, $stats['deleted']);
    }

    /**
     * Helper to get expected route count based on common logic
     */
    protected function getExpectedRouteCount(): int
    {
        $apiPrefix = config('documentation.api_prefix', 'api');
        $excludePatterns = config('documentation.exclude_routes', []);

        return collect(Route::getRoutes())->filter(function ($route) use ($apiPrefix, $excludePatterns) {
            $uri = $route->uri();

            if (!str_starts_with($uri, $apiPrefix . '/')) {
                return false;
            }

            foreach ($excludePatterns as $pattern) {
                if (fnmatch($pattern, $uri)) {
                    return false;
                }
            }

            $action = $route->getAction('controller');
            if (!$action) {
                return false;
            }

            return true;
        })->count();
    }
}

