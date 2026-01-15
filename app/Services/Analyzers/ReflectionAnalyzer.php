<?php

namespace App\Services\Analyzers;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Uses PHP Reflection to analyze controller methods
 */
class ReflectionAnalyzer
{
    /**
     * Analyze a controller method using reflection
     */
    public function analyzeController(string $controller, string $method, ?string $uri = null): array
    {
        try {
            $reflection = new ReflectionClass($controller);
            $methodReflection = $reflection->getMethod($method);

            return [
                'description' => $this->parseDocBlock($methodReflection),
                'parameters' => $this->getMethodParameters($methodReflection, $uri),
                'responses' => $this->inferResponses($methodReflection),
                'is_deprecated' => $this->isDeprecated($methodReflection),
                'form_request' => $this->getFormRequest($methodReflection),
            ];
        } catch (\ReflectionException $e) {
            return [
                'description' => null,
                'parameters' => [],
                'responses' => [],
                'is_deprecated' => false,
            ];
        }
    }

    /**
     * Parse PHPDoc comment to extract description
     */
    protected function parseDocBlock(ReflectionMethod $method): ?string
    {
        $docComment = $method->getDocComment();

        if (!$docComment) {
            return null;
        }

        // Extract description (first line of doc comment)
        preg_match('/@description\s+(.+)/i', $docComment, $matches);
        if (isset($matches[1])) {
            return trim($matches[1]);
        }

        // Fallback: get first non-empty line that's not a tag
        $lines = explode("\n", $docComment);
        foreach ($lines as $line) {
            $line = trim($line, "/* \t\n\r");
            if ($line && !str_starts_with($line, '@')) {
                return $line;
            }
        }

        return null;
    }

    /**
     * Get method parameters using reflection
     */
    protected function getMethodParameters(ReflectionMethod $method, ?string $uri = null): array
    {
        $parameters = [];
        $coveredNames = [];
        $docComment = $method->getDocComment();

        // Extract path parameters from URI
        $pathParams = [];
        if ($uri) {
            preg_match_all('/{([^}]+)}/', $uri, $matches);
            $pathParams = $matches[1] ?? [];
            $pathParams = array_map(fn($p) => str_replace('?', '', $p), $pathParams);
        }

        // Get parameters from method signature
        foreach ($method->getParameters() as $param) {
            if ($this->isFrameworkParameter($param)) {
                continue;
            }

            $originalName = $param->getName();
            $name = $originalName;
            $location = 'body';

            if ($uri && (in_array($name, $pathParams) || (count($pathParams) === 1 && $name === 'id'))) {
                $location = 'path';
                if (count($pathParams) === 1) {
                    $name = $pathParams[0];
                }
            }

            // Heuristic for GET parameters
            // Note: We don't have the HTTP method here easily, but we can check the URI
            // or just default to query if it's not in the path and we suspect it's a GET
            // Actually, we should probably pass the HTTP method down, but for now:
            // if it's not path, let's keep it as body unless we know otherwise.

            $parameters[$name] = [
                'name' => $name,
                'type' => $this->getParameterType($param),
                'location' => $location,
                'is_required' => !$param->isOptional(),
                'default_value' => $param->isDefaultValueAvailable() ?
                    $param->getDefaultValue() : null,
                'description' => $this->getParameterDescriptionFromDocBlock($docComment, $originalName) ?? "The {$name} parameter",
            ];

            // Track that this original name is covered
            $coveredNames[$originalName] = $name;
        }

        // Add parameters from @param tags that are not in the signature
        if ($docComment) {
            // Regex to capture: @param [type] $[name] [description]
            // Handles leading * and case-insensitive @param
            // Supports complex types like string|null or App\Models\User
            preg_match_all('/@param\s+([^\s\$]+)\s+\$(\w+)(?:[\s\t]+(.*))?/', $docComment, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $type = $match[1];
                $name = $match[2];
                $description = isset($match[3]) ? trim($match[3]) : '';

                // Check if this parameter name (or its original signature name) is already covered
                if (isset($parameters[$name]) || isset($coveredNames[$name])) {
                    continue;
                }

                if (!in_array($type, ['Request', 'FormRequest'])) {
                    // Normalize file types
                    if (in_array(strtolower($type), ['file', 'image', 'uploadedfile'])) {
                        $type = 'file';
                    }

                    // Check if description contains "required" (flexible check)
                    $isRequired = (bool) preg_match('/\brequired\b/i', $description);

                    $parameters[$name] = [
                        'name' => $name,
                        'type' => $type,
                        'location' => in_array($name, $pathParams) ? 'path' : 'body',
                        'is_required' => $isRequired,
                        'default_value' => null,
                        'description' => $description ?: "The {$name} parameter",
                    ];
                }
            }
        }

        // Final pass: if location is body but URI is api/users (no path params)
        // and we are in a controller index method, it's likely query.
        // This is a bit hacky, but helps without full route context.
        if (str_ends_with($method->getName(), 'index')) {
            foreach ($parameters as &$p) {
                if ($p['location'] === 'body') {
                    $p['location'] = 'query';
                }
            }
        }

        return array_values($parameters);
    }

    /**
     * Extract parameter description from docblock
     */
    protected function getParameterDescriptionFromDocBlock(?string $docComment, string $name): ?string
    {
        if (!$docComment) return null;
        preg_match('/@param\s+\w+\s+\$' . preg_quote($name, '/') . '\s*(.*)/', $docComment, $matches);
        return isset($matches[1]) ? trim($matches[1]) : null;
    }

    /**
     * Check if parameter is a framework class (Request, etc.)
     */
    protected function isFrameworkParameter(ReflectionParameter $param): bool
    {
        $type = $param->getType();

        if (!$type || $type->isBuiltin()) {
            return false;
        }

        $typeName = $type->getName();

        // Skip common Laravel/framework types
        $frameworkTypes = [
            'Illuminate\\Http\\Request',
            'Illuminate\\Foundation\\Http\\FormRequest',
        ];

        foreach ($frameworkTypes as $frameworkType) {
            if ($typeName === $frameworkType || is_subclass_of($typeName, $frameworkType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get parameter type from reflection
     */
    protected function getParameterType(ReflectionParameter $param): string
    {
        $type = $param->getType();

        if (!$type) {
            return 'string';
        }

        if ($type->isBuiltin()) {
            return $type->getName();
        }

        // For non-builtin types, return the class name
        return $type->getName();
    }

    /**
     * Infer possible responses from method
     */
    protected function inferResponses(ReflectionMethod $method): array
    {
        $responses = [];

        // Default success response
        $responses[] = [
            'status_code' => 200,
            'description' => 'Successful response',
            'is_error' => false,
        ];

        // Check for common error responses in doc comments
        $docComment = $method->getDocComment();
        if ($docComment) {
            if (str_contains($docComment, '404') || str_contains($docComment, 'Not Found')) {
                $responses[] = [
                    'status_code' => 404,
                    'description' => 'Resource not found',
                    'is_error' => true,
                    'example' => [
                        'error' => 'Resource not found',
                        'message' => 'The requested resource was not found on this server.'
                    ]
                ];
            }

            if (str_contains($docComment, '401') || str_contains($docComment, 'Unauthorized')) {
                $responses[] = [
                    'status_code' => 401,
                    'description' => 'Unauthorized',
                    'is_error' => true,
                    'example' => [
                        'message' => 'Unauthenticated.'
                    ]
                ];
            }

            if (str_contains($docComment, '422') || str_contains($docComment, 'Validation')) {
                $responses[] = [
                    'status_code' => 422,
                    'description' => 'Validation error',
                    'is_error' => true,
                    'example' => [
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'field_name' => ['The field_name field is required.']
                        ]
                    ]
                ];
            }
        }

        return $responses;
    }

    /**
     * Check if method is deprecated
     */
    protected function isDeprecated(ReflectionMethod $method): bool
    {
        $docComment = $method->getDocComment();

        if (!$docComment) {
            return false;
        }

        return str_contains($docComment, '@deprecated');
    }

    /**
     * Get the FormRequest class name if used in method parameters
     */
    protected function getFormRequest(ReflectionMethod $method): ?string
    {
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();
                if (is_subclass_of($typeName, 'Illuminate\\Foundation\\Http\\FormRequest')) {
                    return $typeName;
                }
            }
        }

        return null;
    }
}
