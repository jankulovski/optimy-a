<?php

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// Helper function to create an authenticated user and return the user and token
function actingAsUser(): User
{
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    return $user;
}

// Public routes tests
test('it lists campaigns', function () {
    Campaign::factory(3)->create();
    $response = $this->getJson('/api/campaigns');
    // $response->dump(); // Dump the response
    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'title', 'user']], 'links', 'meta']); // Restored full structure check
});

test('it shows a single campaign', function () {
    $campaign = Campaign::factory()->create();
    $response = $this->getJson("/api/campaigns/{$campaign->id}");
    $response->assertStatus(200)
        ->assertJsonPath('data.id', $campaign->id)
        ->assertJsonPath('data.title', $campaign->title);
});

test('it returns 404 for non_existent campaign', function () {
    $this->getJson('/api/campaigns/999')->assertStatus(404);
});

// Authenticated routes tests
test('authenticated user can create a campaign', function () {
    actingAsUser();
    $campaignData = Campaign::factory()->make([
        'start_date' => now()->format('Y-m-d H:i:s'), // Ensure a valid start_date
        'end_date' => now()->addMonth()->format('Y-m-d H:i:s'), // Ensure end_date is after start_date
    ])->toArray();
    // Ensure goal_amount is a numeric string for validation if factory produces decimal
    $campaignData['goal_amount'] = (string) $campaignData['goal_amount'];

    $response = $this->postJson('/api/campaigns', $campaignData);
    $response->assertStatus(201)
        ->assertJsonPath('data.title', $campaignData['title']);
    $this->assertDatabaseHas('campaigns', ['title' => $campaignData['title']]);
});

test('unauthenticated user cannot create a campaign', function () {
    $campaignData = Campaign::factory()->make()->toArray();
    $this->postJson('/api/campaigns', $campaignData)->assertStatus(401);
});

test('campaign creation validation for required fields', function () {
    actingAsUser();
    $this->postJson('/api/campaigns', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'description', 'goal_amount']);
});

test('authenticated user who owns campaign can update it', function () {
    $user = actingAsUser();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);
    $updateData = ['title' => 'Updated Title', 'description' => 'Updated description.'];

    $response = $this->putJson("/api/campaigns/{$campaign->id}", $updateData);
    $response->assertStatus(200)
        ->assertJsonPath('data.title', 'Updated Title');
    $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'title' => 'Updated Title']);
});

test('authenticated user cannot update campaign they do not own', function () {
    actingAsUser(); // This is user1
    $campaignOwner = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $campaignOwner->id]);
    $updateData = ['title' => 'Updated Title'];

    $this->putJson("/api/campaigns/{$campaign->id}", $updateData)->assertStatus(403);
});

test('unauthenticated user cannot update a campaign', function () {
    $campaign = Campaign::factory()->create();
    $this->putJson("/api/campaigns/{$campaign->id}", ['title' => 'New Title'])->assertStatus(401);
});

test('authenticated user who owns campaign can delete it', function () {
    $user = actingAsUser();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $this->deleteJson("/api/campaigns/{$campaign->id}")
        ->assertStatus(204);
    $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
});

test('authenticated user cannot delete campaign they do not own', function () {
    actingAsUser();
    $campaignOwner = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $campaignOwner->id]);

    $this->deleteJson("/api/campaigns/{$campaign->id}")->assertStatus(403);
});

test('unauthenticated user cannot delete a campaign', function () {
    $campaign = Campaign::factory()->create();
    $this->deleteJson("/api/campaigns/{$campaign->id}")->assertStatus(401);
}); 