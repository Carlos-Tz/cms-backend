<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs in with valid credentials and returns a token', function () {
    $user = User::factory()->create(['password' => 'secret1234']);

    $response = $this->postJson('/api/admin/auth/login', [
        'email' => $user->email,
        'password' => 'secret1234',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'data' => ['access_token', 'token_type', 'expires_in'],
            'meta',
        ])
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.token_type', 'bearer');
});

it('rejects login with wrong password', function () {
    $user = User::factory()->create(['password' => 'secret1234']);

    $this->postJson('/api/admin/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnauthorized()
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('data', null);
});

it('validates required login fields', function () {
    $this->postJson('/api/admin/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('returns the authenticated user on me', function () {
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this->withToken($token)
        ->getJson('/api/admin/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

it('blocks me without a token', function () {
    $this->getJson('/api/admin/auth/me')->assertUnauthorized();
});

it('logs out an authenticated user', function () {
    $user = User::factory()->create();
    $token = auth('api')->login($user);

    $this->withToken($token)
        ->postJson('/api/admin/auth/logout')
        ->assertOk()
        ->assertJsonPath('data', null);
});
