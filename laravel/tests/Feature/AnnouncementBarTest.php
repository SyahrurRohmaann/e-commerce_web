<?php

namespace Tests\Feature;

use App\Models\AnnouncementBar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_returns_only_active_bars_ordered(): void
    {
        AnnouncementBar::create([
            'message' => 'Second message',
            'background_color' => '#222222',
            'text_color' => '#ffffff',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        AnnouncementBar::create([
            'message' => 'First message',
            'background_color' => '#111111',
            'text_color' => '#ffffff',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        AnnouncementBar::create([
            'message' => 'Hidden message',
            'background_color' => '#111111',
            'text_color' => '#ffffff',
            'is_active' => false,
            'sort_order' => 3,
        ]);

        $response = $this->getJson('/api/announcements');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertSame('First message', $response->json('data.0.message'));
        $this->assertSame('Second message', $response->json('data.1.message'));
    }

    public function test_admin_can_create_update_and_delete_announcements(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $create = $this->actingAs($admin)->postJson('/api/admin/announcements', [
            'message' => 'Free shipping this week',
            'background_color' => '#000000',
            'text_color' => '#ffffff',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $create->assertCreated();
        $barId = $create->json('data.id');

        $update = $this->actingAs($admin)->putJson("/api/admin/announcements/{$barId}", [
            'message' => 'Updated message',
            'background_color' => '#333333',
            'text_color' => '#ffffff',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $update->assertOk();
        $this->assertSame('Updated message', $update->json('data.message'));

        $this->actingAs($admin)->deleteJson("/api/admin/announcements/{$barId}")->assertOk();
        $this->assertDatabaseMissing('announcement_bars', ['id' => $barId]);
    }

    public function test_message_is_required(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/admin/announcements', [
            'background_color' => '#000000',
            'text_color' => '#ffffff',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('message');
    }
}