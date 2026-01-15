<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUserRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * User Management API
 *
 * Endpoints for managing user accounts, profiles, and avatars.
 *
 * @group Users
 */
class UserController extends Controller
{
    /**
     * List all users
     *
     * Retrieve a paginated list of all users in the system.
     * Supports filtering by role, status, and search query.
     *
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer Number of items per page (max: 100). Example: 15
     * @queryParam role string Filter by user role (admin, user, editor). Example: admin
     * @queryParam search string Search users by name or email. Example: john
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "role": "admin",
     *       "bio": "Senior developer and project lead.",
     *       "avatar": "https://api.example.com/storage/avatars/1.jpg",
     *       "verified": true,
     *       "posts_count": 42,
     *       "created_at": "2024-01-15T08:00:00Z"
     *     },
     *     {
     *       "id": 2,
     *       "name": "Jane Smith",
     *       "email": "jane@example.com",
     *       "role": "editor",
     *       "bio": "Content specialist with a focus on technical writing.",
     *       "avatar": null,
     *       "verified": true,
     *       "posts_count": 12,
     *       "created_at": "2024-01-16T10:30:00Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "last_page": 1,
     *     "per_page": 15,
     *     "total": 2,
     *     "search": null,
     *     "role": null
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->input('per_page', 15), 100);
        $search = $request->input('search');
        $role = $request->input('role');

        $users = [
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'role' => 'admin',
                'bio' => 'Senior developer with 10 years experience',
                'avatar' => 'https://api.example.com/storage/avatars/1.jpg',
                'verified' => true,
                'posts_count' => 42,
                'created_at' => '2024-01-15T08:00:00Z',
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'role' => 'user',
                'bio' => 'Frontend specialist',
                'avatar' => null,
                'verified' => true,
                'posts_count' => 18,
                'created_at' => '2024-01-14T10:30:00Z',
            ],
            [
                'id' => 3,
                'name' => 'Bob Wilson',
                'email' => 'bob@example.com',
                'role' => 'editor',
                'bio' => null,
                'avatar' => null,
                'verified' => false,
                'posts_count' => 5,
                'created_at' => '2024-01-13T14:20:00Z',
            ],
        ];

        return response()->json([
            'data' => $users,
            'meta' => [
                'current_page' => (int) $request->input('page', 1),
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => count($users),
                'search' => $search,
                'role' => $role,
            ],
        ]);
    }

    /**
     * Create a new user
     *
     * Register a new user account with the provided information.
     * An optional avatar image can be uploaded during creation.
     *
     * @bodyParam name string required The user's full name. Example: John Doe
     * @bodyParam email string required The user's email address. Must be unique. Example: john@example.com
     * @bodyParam password string required The user's password. Min 8 characters. Example: secretpassword123
     * @bodyParam password_confirmation string required Password confirmation. Example: secretpassword123
     * @bodyParam role string The user's role (admin, user, editor). Default: user. Example: user
     * @bodyParam avatar file An optional profile picture. Max 2MB. Allowed: jpg, png, gif.
     *
     * @response 201 {
     *   "data": {
     *     "id": 4,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "role": "user",
     *     "bio": null,
     *     "avatar": null,
     *     "verified": false,
     *     "posts_count": 0,
     *     "created_at": "2024-01-15T09:00:00Z"
     *   },
     *   "message": "User created successfully"
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["The email has already been taken."],
     *     "password": ["The password must be at least 8 characters."]
     *   }
     * }
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $avatarUrl = 'https://api.example.com/storage/avatars/4.jpg';
        }

        return response()->json([
            'data' => [
                'id' => 4,
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'role' => $request->input('role', 'user'),
                'bio' => null,
                'avatar' => $avatarUrl,
                'verified' => false,
                'posts_count' => 0,
                'created_at' => now()->toIso8601String(),
            ],
            'message' => 'User created successfully',
        ], 201);
    }

    /**
     * Get user details
     *
     * Retrieve detailed information about a specific user by their ID.
     *
     * @urlParam id integer required The user's unique identifier. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "role": "admin",
     *     "bio": "Senior developer with 10 years experience in full-stack development. Expert in Laravel and Vue.js.",
     *     "avatar": "https://api.example.com/storage/avatars/1.jpg",
     *     "verified": true,
     *     "posts_count": 42,
     *     "comments_count": 156,
     *     "badges": ["top-contributor", "laravel-expert"],
     *     "created_at": "2024-01-01T08:00:00Z",
     *     "updated_at": "2024-01-15T08:00:00Z"
     *   }
     * }
     * @response 404 {
     *   "message": "User not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        if ($id > 3) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'role' => 'admin',
                'bio' => 'Senior developer with 10 years experience',
                'avatar' => 'https://api.example.com/storage/avatars/'.$id.'.jpg',
                'verified' => true,
                'posts_count' => 42,
                'comments_count' => 156,
                'created_at' => '2024-01-01T08:00:00Z',
                'updated_at' => '2024-01-15T08:00:00Z',
            ],
        ]);
    }

    /**
     * Update user information
     *
     * Update an existing user's profile information.
     * All fields are optional - only provided fields will be updated.
     *
     * @urlParam id integer required The user's unique identifier. Example: 1
     *
     * @bodyParam name string The user's full name. Example: John Updated
     * @bodyParam email string The user's email address. Must be unique. Example: john.updated@example.com
     * @bodyParam bio string The user's biography. Example: Updated bio text
     * @bodyParam role string The user's role (admin, user, editor). Example: editor
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "John Updated",
     *     "email": "john.updated@example.com",
     *     "role": "editor",
     *     "bio": "Updated bio text with new professional details.",
     *     "avatar": "https://api.example.com/storage/avatars/1.jpg",
     *     "verified": true,
     *     "created_at": "2024-01-01T08:00:00Z",
     *     "updated_at": "2024-01-15T09:30:00Z"
     *   },
     *   "message": "User updated successfully"
     * }
     * @response 404 {
     *   "message": "User not found"
     * }
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        if ($id > 3) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $id,
                'name' => $request->input('name', 'John Doe'),
                'email' => $request->input('email', 'john@example.com'),
                'role' => $request->input('role', 'admin'),
                'bio' => $request->input('bio', 'Updated bio'),
                'avatar' => 'https://api.example.com/storage/avatars/'.$id.'.jpg',
                'verified' => true,
                'created_at' => '2024-01-01T08:00:00Z',
                'updated_at' => now()->toIso8601String(),
            ],
            'message' => 'User updated successfully',
        ]);
    }

    /**
     * Delete a user
     *
     * Permanently delete a user account and all associated data.
     * This action cannot be undone.
     *
     * @urlParam id integer required The user's unique identifier. Example: 1
     *
     * @response 200 {
     *   "message": "User deleted successfully"
     * }
     * @response 404 {
     *   "message": "User not found"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        if ($id > 3) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Upload user avatar
     *
     * Upload or replace the user's profile picture.
     * The previous avatar will be automatically deleted.
     *
     * @urlParam id integer required The user's unique identifier. Example: 1
     *
     * @bodyParam avatar file required The avatar image file. Max 2MB. Allowed: jpg, jpeg, png, gif, webp.
     *
     * @response 200 {
     *   "data": {
     *     "avatar_url": "https://api.example.com/storage/avatars/1_1705312800.jpg"
     *   },
     *   "message": "Avatar uploaded successfully"
     * }
     * @response 404 {
     *   "message": "User not found"
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "avatar": ["The avatar must be an image.", "The avatar may not be greater than 2048 kilobytes."]
     *   }
     * }
     */
    public function uploadAvatar(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ]);

        if ($id > 3) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        $filename = $id.'_'.time().'.jpg';

        return response()->json([
            'data' => [
                'avatar_url' => 'https://api.example.com/storage/avatars/'.$filename,
            ],
            'message' => 'Avatar uploaded successfully',
        ]);
    }

    /**
     * Delete user avatar
     *
     * Remove the user's current profile picture and reset to default.
     *
     * @urlParam id integer required The user's unique identifier. Example: 1
     *
     * @response 200 {
     *   "message": "Avatar deleted successfully"
     * }
     * @response 404 {
     *   "message": "User not found"
     * }
     */
    public function deleteAvatar(int $id): JsonResponse
    {
        if ($id > 3) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Avatar deleted successfully',
        ]);
    }
}
