<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers a user and returns a JWT access token', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Waste Administrator',
        'email' => '  NEW.ADMIN@EXAMPLE.COM ',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'User registered successfully.')
        ->assertJsonPath('data.user.name', 'Waste Administrator')
        ->assertJsonPath('data.user.email', 'new.admin@example.com')
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('errors', null)
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                'access_token',
                'token_type',
                'expires_in',
            ],
            'meta',
        ]);

    expect($response->json('data.access_token'))->toBeString()->not->toBeEmpty()
        ->and($response->json('data.expires_in'))->toBeInt()->toBeGreaterThan(0);

    $user = User::query()->where('email', 'new.admin@example.com')->first();

    expect($user)->not->toBeNull()
        ->and(Hash::check('password123', $user->password))->toBeTrue()
        ->and($response->json('data.user'))->not->toHaveKey('password');
});

it('rejects invalid registration data', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'different',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'The given data was invalid.')
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta', [])
        ->assertJsonStructure([
            'errors' => ['name', 'email', 'password'],
        ]);
});

it('rejects a duplicate registration email after normalizing it', function () {
    User::factory()->create([
        'email' => 'duplicate@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/auth/register', [
        'name' => 'Duplicate User',
        'email' => ' DUPLICATE@EXAMPLE.COM ',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'The given data was invalid.')
        ->assertJsonStructure([
            'errors' => ['email'],
        ]);
});

it('logs in with normalized credentials and returns a JWT access token', function () {
    $user = User::factory()->create([
        'name' => 'Login User',
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => ' LOGIN@EXAMPLE.COM ',
        'password' => 'password123',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Login successful.')
        ->assertJsonPath('data.user.id', (string) $user->getKey())
        ->assertJsonPath('data.user.email', 'login@example.com')
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('errors', null)
        ->assertJsonStructure([
            'data' => ['access_token', 'expires_in'],
        ]);

    expect($response->json('data.access_token'))->toBeString()->not->toBeEmpty();
});

it('rejects an invalid login password with an unauthorized response', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'login@example.com',
        'password' => 'incorrect-password',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Invalid email or password.')
        ->assertJsonPath('errors', null);
});

it('validates login credentials', function () {
    $this->postJson('/api/auth/login', [
        'email' => 'invalid-email',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'The given data was invalid.')
        ->assertJsonStructure([
            'errors' => ['email', 'password'],
        ]);
});

it('returns the authenticated user', function () {
    $headers = testAuthHeaders();
    $user = User::query()->firstOrFail();

    $this->withHeaders($headers)
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Authenticated user retrieved successfully.')
        ->assertJsonPath('data.id', (string) $user->getAuthIdentifier())
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('errors', null)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'created_at', 'updated_at'],
        ]);
});

it('requires authentication to retrieve the current user', function () {
    $this->getJson('/api/auth/me')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthenticated.')
        ->assertJsonPath('errors', null);
});

it('rejects an invalid JWT access token', function () {
    $this->withHeaders([
        'Authorization' => 'Bearer invalid-token',
        'Accept' => 'application/json',
    ])
        ->getJson('/api/auth/me')
        ->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('logs out and invalidates the JWT access token', function () {
    $headers = testAuthHeaders();

    $this->withHeaders($headers)
        ->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Logout successful.')
        ->assertJsonPath('data', [])
        ->assertJsonPath('errors', null);

    $this->withHeaders($headers)
        ->getJson('/api/auth/me')
        ->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('requires authentication to log out', function () {
    $this->postJson('/api/auth/logout')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthenticated.');
});
