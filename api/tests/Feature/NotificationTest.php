<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected Business $business;

    protected BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
        ]);
        $role = Role::factory()->create(['business_id' => $this->business->id, 'name' => 'Executive']);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    protected function createNotification(string $type, bool $isRead = false): Notification
    {
        return Notification::create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
            'type' => $type,
            'title' => ucfirst(str_replace('_', ' ', $type)),
            'message' => 'Test message',
            'data' => [],
            'is_read' => $isRead,
            'read_at' => $isRead ? now() : null,
        ]);
    }

    public function test_index_lists_only_the_authenticated_users_notifications(): void
    {
        $owner = Notification::create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
            'type' => 'new_sale',
            'title' => 'Mine',
            'message' => 'mine',
            'data' => [],
        ]);

        $otherUser = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create(['business_id' => $this->business->id])->id,
        ]);
        Notification::create([
            'user_id' => $otherUser->id,
            'business_id' => $this->business->id,
            'type' => 'new_sale',
            'title' => 'Theirs',
            'message' => 'theirs',
            'data' => [],
        ]);

        $this->getJson('/api/users/notifications')
            ->assertStatus(200)
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.id', $owner->id);
    }

    public function test_index_filters_by_type(): void
    {
        $this->createNotification('low_stock');
        $this->createNotification('new_sale');
        $this->createNotification('new_sale');

        $response = $this->getJson('/api/users/notifications?type=new_sale')
            ->assertStatus(200);

        $this->assertCount(2, $response->json('notifications'));
        $this->assertCount(1, $this->getJson('/api/users/notifications?type=low_stock')->json('notifications'));
    }

    public function test_index_filters_by_is_read(): void
    {
        $this->createNotification('low_stock', false);
        $this->createNotification('new_sale', true);

        $this->getJson('/api/users/notifications?is_read=1')
            ->assertStatus(200)
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.type', 'new_sale');
    }

    public function test_index_returns_unread_by_type_breakdown(): void
    {
        $this->createNotification('low_stock');
        $this->createNotification('low_stock');
        $this->createNotification('new_sale');

        $response = $this->getJson('/api/users/notifications')->assertStatus(200);

        $unreadByType = $response->json('meta.unread_by_type');
        $this->assertSame(2, $unreadByType['low_stock']);
        $this->assertSame(1, $unreadByType['new_sale']);
        $this->assertSame(3, $response->json('meta.unread'));
    }

    public function test_unread_count_returns_grouped_by_type(): void
    {
        $this->createNotification('low_stock');
        $this->createNotification('low_stock');
        $this->createNotification('new_sale');
        $this->createNotification('new_purchase', true);

        $response = $this->getJson('/api/users/notifications/unread-count')
            ->assertStatus(200);

        $this->assertSame(3, $response->json('unread_count'));
        $this->assertSame(2, $response->json('unread_by_type.low_stock'));
        $this->assertSame(1, $response->json('unread_by_type.new_sale'));
        $this->assertArrayNotHasKey('new_purchase', $response->json('unread_by_type'));
    }

    public function test_mark_as_read_marks_only_own_notification(): void
    {
        $notification = $this->createNotification('low_stock', false);

        $this->postJson("/api/users/notifications/{$notification->id}/read")
            ->assertStatus(200);

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_as_read_rejects_foreign_notification(): void
    {
        $otherUser = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create(['business_id' => $this->business->id])->id,
        ]);
        $notification = Notification::create([
            'user_id' => $otherUser->id,
            'business_id' => $this->business->id,
            'type' => 'low_stock',
            'title' => 'Theirs',
            'message' => 'theirs',
            'data' => [],
        ]);

        $this->postJson("/api/users/notifications/{$notification->id}/read")
            ->assertStatus(403);
    }

    public function test_clear_all_deletes_only_own_notifications(): void
    {
        $this->createNotification('low_stock');
        $this->createNotification('new_sale');

        $otherUser = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create(['business_id' => $this->business->id])->id,
        ]);
        Notification::create([
            'user_id' => $otherUser->id,
            'business_id' => $this->business->id,
            'type' => 'new_purchase',
            'title' => 'Theirs',
            'message' => 'theirs',
            'data' => [],
        ]);

        $response = $this->postJson('/api/users/notifications/clear-all')
            ->assertStatus(200);

        $this->assertSame(2, $response->json('cleared_count'));
        $this->assertSame(0, Notification::where('user_id', $this->user->id)->count());
        $this->assertSame(1, Notification::where('user_id', $otherUser->id)->count());
    }
}
