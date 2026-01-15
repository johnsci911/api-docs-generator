<?php

namespace App\Services;

use App\Models\ApiEndpoint;
use App\Services\Analyzers\RouteAnalyzer;
use App\Services\Analyzers\ReflectionAnalyzer;
use App\Services\Analyzers\ValidationAnalyzer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for generating API documentation automatically
 */
class DocumentationService
{
    protected RouteAnalyzer $routeAnalyzer;
    protected ReflectionAnalyzer $reflectionAnalyzer;
    protected ValidationAnalyzer $validationAnalyzer;

    public function __construct(
        RouteAnalyzer $routeAnalyzer,
        ReflectionAnalyzer $reflectionAnalyzer,
        ValidationAnalyzer $validationAnalyzer
    ) {
        $this->routeAnalyzer = $routeAnalyzer;
        $this->reflectionAnalyzer = $reflectionAnalyzer;
        $this->validationAnalyzer = $validationAnalyzer;
    }

    /**
     * Generate documentation for all API routes
     */
    public function generateDocumentation(): array
    {
        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => 0,
        ];

        DB::beginTransaction();

        try {
            $apiRoutes = $this->getApiRoutes();
            $stats['total'] = count($apiRoutes);
            $processedIds = [];

            foreach ($apiRoutes as $route) {
                try {
                    $endpoint = $this->analyzeAndSaveEndpoint($route);
                    $processedIds[] = $endpoint->id;
                    $stats['created']++;
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error("Error analyzing route: " . $e->getMessage(), [
                        'route' => $route->uri(),
                        'exception' => $e,
                    ]);
                }
            }

            // Cleanup stale endpoints
            $stats['deleted'] = ApiEndpoint::whereNotIn('id', $processedIds)->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $stats;
    }

    /**
     * Get all API routes from the application
     */
    protected function getApiRoutes(): array
    {
        $apiPrefix = config('documentation.api_prefix', 'api');
        $excludePatterns = config('documentation.exclude_routes', []);

        $routes = collect(Route::getRoutes())->filter(function ($route) use ($apiPrefix, $excludePatterns) {
            $uri = $route->uri();

            // Must start with API prefix
            if (!str_starts_with($uri, $apiPrefix . '/')) {
                return false;
            }

            // Check exclude patterns
            foreach ($excludePatterns as $pattern) {
                if (fnmatch($pattern, $uri)) {
                    return false;
                }
            }

            // Must have a controller action
            $action = $route->getAction('controller');
            if (!$action) {
                return false;
            }

            return true;
        });

        return $routes->all();
    }

    /**
     * Analyze a route and save its documentation
     */
    protected function analyzeAndSaveEndpoint($route): ApiEndpoint
    {
        // Analyze the route
        $routeData = $this->routeAnalyzer->analyze($route);

        // Analyze the controller method
        $reflectionData = $this->reflectionAnalyzer->analyzeController(
            $routeData['controller'],
            $routeData['action'],
            $routeData['uri']
        );

        // Merge data
        $endpointData = array_merge($routeData, $reflectionData);

        // Analyze FormRequest if available
        if (isset($reflectionData['form_request'])) {
            $validationParameters = $this->validationAnalyzer->analyzeFormRequest($reflectionData['form_request']);

            // Merge parameters, validation rules take precedence for body params
            $existingParams = collect($endpointData['parameters'])->keyBy('name');
            foreach ($validationParameters as $vParam) {
                // If the parameter already exists and is in the body, update it
                if ($existingParams->has($vParam['name'])) {
                    $existing = $existingParams->get($vParam['name']);
                    if ($existing['location'] === 'body') {
                        $existingParams->put($vParam['name'], array_merge($existing, $vParam));
                    }
                } else {
                    $existingParams->put($vParam['name'], $vParam);
                }
            }
            $endpointData['parameters'] = $existingParams->values()->all();
        }

        // Find or create endpoint
        $endpoint = ApiEndpoint::updateOrCreate(
            [
                'uri' => $endpointData['uri'],
                'controller' => $endpointData['controller'],
                'action' => $endpointData['action'],
            ],
            [
                'methods' => $endpointData['methods'],
                'description' => $endpointData['description'] ?? null,
                'group' => $endpointData['group'] ?? null,
                'is_deprecated' => $endpointData['is_deprecated'] ?? false,
                'middleware' => $endpointData['middleware'] ?? [],
            ]
        );

        // Save parameters
        if (isset($endpointData['parameters'])) {
            $this->saveParameters($endpoint, $endpointData['parameters']);
        }

        // Save responses
        if (isset($endpointData['responses'])) {
            $this->saveResponses($endpoint, $endpointData['responses']);
        }

        return $endpoint;
    }

    /**
     * Save parameters for an endpoint
     */
    protected function saveParameters(ApiEndpoint $endpoint, array $parameters): void
    {
        // Delete existing parameters
        $endpoint->parameters()->delete();

        // Create new parameters
        foreach ($parameters as $param) {
            $endpoint->parameters()->create($param);
        }
    }

    /**
     * Save responses for an endpoint
     */
    protected function saveResponses(ApiEndpoint $endpoint, array $responses): void
    {
        // Delete existing responses
        $endpoint->responses()->delete();

        // Create new responses
        foreach ($responses as $response) {
            $endpoint->responses()->create($response);
        }
    }
}
