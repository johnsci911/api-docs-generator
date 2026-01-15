<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns paginated list of users', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/users');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
});

it('filters users by search query', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/users?search=john');

    $response->assertStatus(200)
        ->assertJsonPath('meta.search', 'john');
});

it('filters users by role', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/users?role=admin');

    $response->assertStatus(200)
        ->assertJsonPath('meta.role', 'admin');
});

it('supports pagination parameters', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/users?page=1&per_page=5');

    $response->assertStatus(200)
        ->assertJsonPath('meta.per_page', '5')
        ->assertJsonPath('meta.current_page', 1);
});

it('creates a new user with valid data', function () {
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
    ];

    $response = $this->actingAs($this->user)->postJson('/api/v1/users', $userData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'role', 'created_at'],
            'message',
        ])
        ->assertJsonPath('data.name', 'Test User')
        ->assertJsonPath('data.email', 'test@example.com');
});

it('validates required fields when creating user', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/users', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('returns user details for valid id', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/users/1');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'role'],
        ])
        ->assertJsonPath('data.id', 1);
});

it('returns 404 for invalid user id', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/users/999');

    $response->assertStatus(404);
});

it('updates user with valid data', function () {
    $response = $this->actingAs($this->user)->putJson('/api/v1/users/1', [
        'name' => 'Updated Name',
        'bio' => 'Updated bio',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('message', 'User updated successfully');
});

it('deletes user successfully', function () {
    $response = $this->actingAs($this->user)->deleteJson('/api/v1/users/1');

    $response->assertStatus(200)
        ->assertJsonPath('message', 'User deleted successfully');
});

it('uploads avatar successfully', function () {
    $file = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->actingAs($this->user)->postJson('/api/v1/users/1/avatar', [
        'avatar' => $file,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['avatar_url'],
            'message',
        ])
        ->assertJsonPath('message', 'Avatar uploaded successfully');
});

it('validates avatar is required', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/users/1/avatar', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['avatar']);
});

it('validates avatar is an image', function () {
    $file = UploadedFile::fake()->create('document.pdf');

    $response = $this->actingAs($this->user)->postJson('/api/v1/users/1/avatar', [
        'avatar' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['avatar']);
});
