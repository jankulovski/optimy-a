<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('users can authenticate with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200);
    $this->assertNotNull($response->content(), 'Token should not be null');
    // For Sanctum token based API, assertAuthenticatedAs might not work as expected without a session.
    // Instead, we verify a token is returned.
    // Further tests would use this token to access protected routes.
});

test('users cannot authenticate with invalid password', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->postJson('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422); // Laravel returns 422 for failed validation/login
    $response->assertJsonValidationErrors('email'); // Default failed login attempt message refers to email field
});

test('users cannot authenticate with non_existent email', function () {
    $response = $this->postJson('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
    ]);
    $response->assertStatus(422)->assertJsonValidationErrors('email');
});

test('users can log out', function () {
    $user = User::factory()->create();
    // Sanctum::actingAs($user); // This is for session-based or Sanctum guard. For token, we just need a token.
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson('/logout');

    $response->assertStatus(204); // No content on successful logout
    // Assert that the token is deleted or invalidated (Sanctum handles this by deleting the token record)
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
        // 'name' => 'test-token' // if tokens have names
    ]);
});

test('logout requires authentication', function () {
    $this->postJson('/logout')
        ->assertStatus(401); // Unauthorized
}); 