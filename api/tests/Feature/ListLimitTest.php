<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Support\ListLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The list endpoints that are not paginated still have to be bounded.
 *
 * These endpoints return arrays, not a paginated envelope, because the table components
 * on the client index the result directly. That is a deliberate trade: the server's
 * memory is bounded here, and real pagination — with the client rebuilt to match — is
 * the follow-up.
 *
 * What is pinned below is that the ceiling cannot be exceeded by asking for a bigger one,
 * and that a truncated response says so rather than pretending to be complete.
 */
class ListLimitTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private BusinessBranch $branch;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create([
                'business_id' => $this->business->id,
                'name' => 'Executive',
            ])->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->user);
    }

    private function sales(int $count): void
    {
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'business_id' => $this->business->id,
                'user_id' => $this->user->id,
                'business_branch_id' => $this->branch->id,
                'subtotal' => 100,
                'total_amount' => 100,
                'status' => 'completed',
                'created_at' => now()->subMinutes($i),
                'updated_at' => now()->subMinutes($i),
            ];
        }

        Sale::insert($rows);
    }

    public function test_a_list_under_the_ceiling_is_returned_whole_and_unflagged(): void
    {
        $this->sales(3);

        $response = $this->getJson('/api/sales/branch-sales')->assertStatus(200);

        $this->assertCount(3, $response->json('sales'));
        $this->assertArrayNotHasKey('truncated', $response->json());
    }

    public function test_a_list_over_the_ceiling_is_capped(): void
    {
        $this->sales(ListLimit::RECORDS + 5);

        $response = $this->getJson('/api/sales/branch-sales')->assertStatus(200);

        $this->assertCount(ListLimit::RECORDS, $response->json('sales'));
    }

    public function test_a_truncated_list_says_so(): void
    {
        $this->sales(ListLimit::RECORDS + 5);

        $response = $this->getJson('/api/sales/branch-sales')->assertStatus(200);

        // A manager seeing 500 sales would otherwise reasonably conclude the business
        // had 500 sales.
        $this->assertTrue($response->json('truncated'));
        $this->assertSame(ListLimit::RECORDS, $response->json('limit'));
    }

    public function test_a_caller_cannot_raise_the_ceiling_by_asking(): void
    {
        $this->sales(ListLimit::RECORDS + 5);

        // Without a maximum on the requested limit, ?limit=999999999 restores exactly
        // the problem the ceiling exists to remove.
        $response = $this->getJson('/api/sales/branch-sales?limit=999999999')
            ->assertStatus(200);

        $this->assertCount(ListLimit::RECORDS, $response->json('sales'));
    }

    public function test_a_caller_may_ask_for_fewer(): void
    {
        $this->sales(20);

        $response = $this->getJson('/api/sales/branch-sales?limit=5')->assertStatus(200);

        $this->assertCount(5, $response->json('sales'));

        // Flagged, because 20 rows exist and 5 were returned. This is the distinction
        // that matters: a caller who asked for a page needs to know there are more, and
        // one who did not needs to know the ceiling took effect.
        $this->assertTrue($response->json('truncated'));
        $this->assertSame(5, $response->json('limit'));
    }

    public function test_a_nonsense_limit_falls_back_to_the_ceiling(): void
    {
        $this->sales(3);

        $response = $this->getJson('/api/sales/branch-sales?limit=abc')->assertStatus(200);

        $this->assertCount(3, $response->json('sales'));
    }

    public function test_a_zero_or_negative_limit_cannot_empty_the_list(): void
    {
        $this->sales(3);

        $response = $this->getJson('/api/sales/branch-sales?limit=0')->assertStatus(200);

        // max(1, ...) in ListLimit::for. A limit of zero would read to the client as
        // "there are no sales", which is a different and wrong answer.
        $this->assertCount(1, $response->json('sales'));
    }

    public function test_the_ceiling_is_applied_in_sql_not_after_loading(): void
    {
        // The limit has to bound what crosses the wire and what occupies memory.
        // Collection->take() would still have loaded every row before discarding it,
        // which is the whole cost this is meant to avoid.
        $queries = [];

        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->sales(3);
        $this->getJson('/api/sales/branch-sales')->assertStatus(200);

        // limit+1: the extra row is the truncation probe.
        $expected = 'limit '.(ListLimit::RECORDS + 1);
        $limited = array_filter($queries, static fn ($sql) => str_contains($sql, $expected));

        $this->assertNotEmpty(
            $limited,
            'the ceiling must appear in the SQL, not be applied to a hydrated collection'
        );
    }
}
