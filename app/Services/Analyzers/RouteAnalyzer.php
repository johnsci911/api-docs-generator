<?php

namespace App\Services\Analyzers;

use Illuminate\Routing\Route;

/**
 * Analyzes Laravel routes to extract endpoint information
 */
class RouteAnalyzer
{
    /**
     * Analyze a route and extract its metadata
     */
    public function analyze(Route $route): array
    {
        $action = $route->getAction('controller');
        [$controller, $method] = $this->parseAction($action);

        return [
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'controller' => $controller,
            'action' => $method,
            'middleware' => $this->getMiddleware($route),
            'group' => $this->determineGroup($route->uri()),
        ];
    }

    /**
     * Parse controller@action string
     */
    protected function parseAction(string $action): array
    {
        if (str_contains($action, '@')) {
            return explode('@', $action);
        }

        // Handle invokable controllers
        if (class_exists($action)) {
            return [$action, '__invoke'];
        }

        return [$action, 'handle'];
    }

    /**
     * Get middleware applied to the route
     */
    protected function getMiddleware(Route $route): array
    {
        $middleware = $route->gatherMiddleware();

        // Filter out internal Laravel middleware
        return array_filter($middleware, function ($m) {
            return !in_array($m, ['web', 'api', 'Illuminate\\Routing\\Middleware\\SubstituteBindings']);
        });
    }

    /**
     * Determine the group/category for an endpoint based on its URI
     */
    protected function determineGroup(string $uri): string
    {
        // Remove 'api/' prefix
        $uri = preg_replace('/^api\//', '', $uri);

        // Get the first segment
        $segments = explode('/', $uri);
        $firstSegment = $segments[0] ?? 'general';

        // Convert to title case
        return ucfirst($firstSegment);
    }

    /**
     * Extract route parameters from URI
     */
    public function extractRouteParameters(string $uri): array
    {
        preg_match_all('/{([^}]+)}/', $uri, $matches);

        $parameters = [];
        foreach ($matches[1] as $param) {
            // Remove optional indicator (?)
            $name = str_replace('?', '', $param);
            $isOptional = str_contains($param, '?');

            $parameters[] = [
                'name' => $name,
                'type' => 'string', // Default type, will be refined by reflection
                'location' => 'path',
                'is_required' => !$isOptional,
                'description' => "The {$name} parameter",
            ];
        }

        return $parameters;
    }
}
