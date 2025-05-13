<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new users can register', function () {
    $response = $this->postJson('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(201); // API should return 201 Created
    // For API registration, assertDatabaseHas is more relevant than assertAuthenticated if no token is returned directly
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
});

test('registration requires a name', function () {
    $this->postJson('/register', [
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors('name');
});

test('registration requires an email', function () {
    $this->postJson('/register', [
        'name' => 'Test User',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

test('registration requires a valid email', function () {
    $this->postJson('/register', [
        'name' => 'Test User',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

test('registration requires email to be unique', function () {
    User::factory()->create(['email' => 'test@example.com']);
    $this->postJson('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

test('registration requires a password', function () {
    $this->postJson('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

test('registration requires password to be at least 8 characters', function () {
    $this->postJson('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

test('registration requires password confirmation', function () {
    $this->postJson('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

test('registration requires password confirmation to match', function () {
    $this->postJson('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
}); 