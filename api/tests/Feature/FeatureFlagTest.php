<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CoreSettings\ReportsSettings;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $role = Role::factory()->create(['business_id' => $this->business->id, 'name' => 'Executive']);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'role_id' => $role->id,
        ]);

        $this->actingAs($this->user);
    }

    public function test_a_disabled_feature_is_refused_with_a_human_message(): void
    {
        $response = $this->getJson('/api/reports/stock-summary');

        $response->assertStatus(403);
        $response->assertJson([
            'code' => 'feature_disabled',
            'feature' => 'reports',
        ]);
        $this->assertStringContainsString('Reports', $response->json('message'));
        $this->assertNotNull($response->json('request_id'));
    }

    public function test_an_enabled_feature_is_served(): void
    {
        $this->getJson('/api/dashboard/customers')->assertStatus(200);
    }

    public function test_enabling_the_feature_lets_the_request_through(): void
    {
        ReportsSettings::create(['business_id' => $this->business->id, 'status' => 'enabled']);

        $response = $this->getJson('/api/reports/stock-summary');

        $this->assertNotSame('feature_disabled', $response->json('code'));
    }
}
