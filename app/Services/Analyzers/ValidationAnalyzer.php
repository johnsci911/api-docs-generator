<?php

namespace App\Services\Analyzers;

use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;

/**
 * Analyzes FormRequest classes to extract validation rules
 */
class ValidationAnalyzer
{
    /**
     * Analyze a FormRequest class to extract validation rules
     */
    public function analyzeFormRequest(string $formRequestClass): array
    {
        if (!class_exists($formRequestClass)) {
            return [];
        }

        if (!is_subclass_of($formRequestClass, FormRequest::class)) {
            return [];
        }

        try {
            $instance = new $formRequestClass();
            $rules = $instance->rules();

            return $this->extractParameters($rules);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Extract parameters from validation rules
     */
    protected function extractParameters(array $rules): array
    {
        $parameters = [];

        foreach ($rules as $field => $fieldRules) {
            $rulesArray = is_string($fieldRules) ?
                explode('|', $fieldRules) : $fieldRules;

            $parameters[] = [
                'name' => $field,
                'type' => $this->determineDataType($rulesArray),
                'location' => 'body',
                'is_required' => $this->isRequired($rulesArray),
                'description' => "The {$field} field",
                'validation_rules' => $rulesArray,
            ];
        }

        return $parameters;
    }

    /**
     * Determine data type from validation rules
     */
    protected function determineDataType(array $rules): string
    {
        foreach ($rules as $rule) {
            $ruleName = is_string($rule) ? explode(':', $rule)[0] : $rule;

            $typeMap = [
                'integer' => 'integer',
                'numeric' => 'number',
                'boolean' => 'boolean',
                'array' => 'array',
                'file' => 'file',
                'image' => 'file',
                'email' => 'string',
                'url' => 'string',
                'date' => 'string',
                'json' => 'object',
            ];

            if (isset($typeMap[$ruleName])) {
                return $typeMap[$ruleName];
            }
        }

        return 'string'; // Default type
    }

    /**
     * Check if field is required based on validation rules
     */
    protected function isRequired(array $rules): bool
    {
        foreach ($rules as $rule) {
            $ruleName = is_string($rule) ? explode(':', $rule)[0] : $rule;

            if ($ruleName === 'required') {
                return true;
            }
        }

        return false;
    }
}
