<?php

namespace App\Http\Controllers;

use App\Models\ApiEndpoint;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentationController extends Controller
{
    /**
     * Display the dashboard with all API endpoints.
     */
    public function index(Request $request)
    {
        $query = ApiEndpoint::with(['parameters', 'responses']);

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('uri', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('controller', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        // Method filter
        if ($method = $request->input('method')) {
            $query->whereJsonContains('methods', strtoupper($method));
        }

        // Group/Controller filter
        if ($group = $request->input('group')) {
            $query->where('group', $group);
        }

        $endpoints = $query
            ->orderBy('group')
            ->orderBy('uri')
            ->get();

        // Get unique groups for filter dropdown
        $groups = ApiEndpoint::select('group')
            ->distinct()
            ->whereNotNull('group')
            ->orderBy('group')
            ->pluck('group');

        // Get unique methods for filter
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        // Stats
        $stats = [
            'total' => ApiEndpoint::count(),
            'get' => ApiEndpoint::whereJsonContains('methods', 'GET')->count(),
            'post' => ApiEndpoint::whereJsonContains('methods', 'POST')->count(),
            'put' => ApiEndpoint::whereJsonContains('methods', 'PUT')->count(),
            'patch' => ApiEndpoint::whereJsonContains('methods', 'PATCH')->count(),
            'delete' => ApiEndpoint::whereJsonContains('methods', 'DELETE')->count(),
        ];

        return Inertia::render('Dashboard', [
            'endpoints' => $endpoints,
            'groups' => $groups,
            'methods' => $methods,
            'stats' => $stats,
            'filters' => [
                'search' => $request->input('search', ''),
                'method' => $request->input('method', ''),
                'group' => $request->input('group', ''),
            ],
        ]);
    }

    /**
     * Display the documentation for a single API endpoint.
     */
    public function show(ApiEndpoint $endpoint)
    {
        $endpoint->load(['parameters', 'responses']);

        $pathParamsCount = $endpoint->parameters->where('location', 'path')->count();
        $queryParamsCount = $endpoint->parameters->where('location', 'query')->count();
        $bodyParamsCount = $endpoint->parameters->where('location', 'body')->count();

        return Inertia::render('Documentation/Show', [
            'endpoint' => $endpoint,
            'pathParamsCount' => $pathParamsCount,
            'queryParamsCount' => $queryParamsCount,
            'bodyParamsCount' => $bodyParamsCount,
        ]);
    }
}
