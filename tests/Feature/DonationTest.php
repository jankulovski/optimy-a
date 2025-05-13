<?php

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// Re-declare or import if you have a global test helper for this
if (!function_exists('actingAsUser')) {
    function actingAsUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        return $user;
    }
}

test('authenticated user can make a donation to an active campaign', function () {
    $user = actingAsUser();
    $campaign = Campaign::factory()->create(['status' => 'active', 'current_amount' => 0]);
    $donationData = [
        'campaign_id' => $campaign->id,
        'amount' => '50.00',
    ];

    $response = $this->postJson('/api/donations', $donationData);
    $response->assertStatus(201)
        ->assertJsonPath('data.amount', '50.00')
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.campaign_id', $campaign->id);

    $this->assertDatabaseHas('donations', [
        'user_id' => $user->id,
        'campaign_id' => $campaign->id,
        'amount' => 50.00,
    ]);
    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'current_amount' => 50.00,
    ]);
});

test('unauthenticated user cannot make a donation', function () {
    $campaign = Campaign::factory()->create(['status' => 'active']);
    $this->postJson('/api/donations', [
        'campaign_id' => $campaign->id,
        'amount' => '50.00',
    ])->assertStatus(401);
});

test('donation requires a valid campaign_id', function () {
    actingAsUser();
    $this->postJson('/api/donations', ['amount' => '50.00'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('campaign_id');

    $this->postJson('/api/donations', ['campaign_id' => 999, 'amount' => '50.00'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('campaign_id'); // exists rule
});

test('donation requires a numeric amount greater than 0', function () {
    $user = actingAsUser();
    $campaign = Campaign::factory()->create();

    $this->postJson('/api/donations', ['campaign_id' => $campaign->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('amount');

    $this->postJson('/api/donations', ['campaign_id' => $campaign->id, 'amount' => '0'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('amount'); // min:1 rule

    $this->postJson('/api/donations', ['campaign_id' => $campaign->id, 'amount' => 'abc'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('amount'); // numeric rule
});

test('cannot donate to a non_active campaign', function () {
    actingAsUser();
    $campaign = Campaign::factory()->create(['status' => 'inactive']);
    $this->postJson('/api/donations', ['campaign_id' => $campaign->id, 'amount' => '50.00'])
        ->assertStatus(400) // As per DonationController logic
        ->assertJsonPath('message', 'Campaign is not active.');
});

test('authenticated user can list their own donations', function () {
    $user = actingAsUser();
    Donation::factory(2)->create(['user_id' => $user->id]);
    Donation::factory(3)->create(); // Other user's donations

    $response = $this->getJson('/api/donations');
    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

test('unauthenticated user cannot list donations', function () {
    $this->getJson('/api/donations')->assertStatus(401);
});

test('authenticated user can view their own specific donation', function () {
    $user = actingAsUser();
    $donation = Donation::factory()->create(['user_id' => $user->id]);

    $response = $this->getJson("/api/donations/{$donation->id}");
    $response->assertStatus(200)
        ->assertJsonPath('data.id', $donation->id);
});

test('authenticated user cannot view donation they do not own', function () {
    actingAsUser(); // User 1
    $otherUser = User::factory()->create();
    $donation = Donation::factory()->create(['user_id' => $otherUser->id]);

    $this->getJson("/api/donations/{$donation->id}")->assertStatus(403);
});

test('unauthenticated user cannot view a specific donation', function () {
    $donation = Donation::factory()->create();
    $this->getJson("/api/donations/{$donation->id}")->assertStatus(401);
});

test('it returns 404 for non_existent donation', function () {
    actingAsUser();
    $this->getJson('/api/donations/999')->assertStatus(404);
}); 