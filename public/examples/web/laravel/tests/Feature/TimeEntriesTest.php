<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_entry_is_idempotent_by_request_id(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('time.write');

        $payload = ['task' => 'Deep work', 'minutes' => 90];
        $headers = ['X-Request-Id' => 'req-abc-123'];

        $first = $this->actingAs($user)->postJson('/api/time-entries', $payload, $headers);
        $second = $this->actingAs($user)->postJson('/api/time-entries', $payload, $headers);

        $first->assertCreated();
        // Retry → same stored entry, no duplicate.
        $this->assertSame($first->json('id'), $second->json('id'));
        $this->assertDatabaseCount('time_entries', 1);
    }

    public function test_create_entry_validates_the_contract(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('time.write');

        $this->actingAs($user)
            ->postJson('/api/time-entries', ['task' => '', 'minutes' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['task', 'minutes']);
    }
}
