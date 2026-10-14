<?php

namespace Tests\Feature;

use App\Models\BusinessCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public business-category catalogue the onboarding form depends on.
 *
 * A business cannot be created without a category (businesses.business_category_id is
 * NOT NULL), so if this endpoint is unreachable or answers in the wrong shape the
 * sign-up flow cannot complete. The response is deliberately a bare JSON array, not
 * `{ data: [...] }`; the SPA's normalizer tolerates both, but this test pins the
 * contract so a future change is a conscious one rather than a silent break.
 */
class BusinessCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_categories_without_authentication(): void
    {
        $category = BusinessCategory::factory()->create(['name' => 'Retail']);

        $response = $this->getJson('/api/business-categories');

        $response->assertOk()
            ->assertJsonFragment(['id' => $category->id, 'name' => 'Retail']);

        // The collection is returned bare: a top-level JSON array, no `data` wrapper.
        $this->assertIsList($response->json());
    }

    public function test_it_returns_an_empty_array_when_no_categories_exist(): void
    {
        $response = $this->getJson('/api/business-categories');

        $response->assertOk();
        $this->assertSame([], $response->json());
    }
}
