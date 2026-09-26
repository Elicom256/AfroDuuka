<?php
namespace Tests\Feature\Reports;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ZzProbeTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_performance_endpoint(): void
    {
        $business = Business::factory()->create();
        $a = BusinessBranch::factory()->create(['business_id' => $business->id, 'name' => 'Kampala']);
        $b = BusinessBranch::factory()->create(['business_id' => $business->id, 'name' => 'Jinja']);
        $user = User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => null, // business admin: sees all branches
            'role_id' => Role::factory()->create(['business_id' => $business->id])->id,
        ]);
        Sanctum::actingAs($user);

        $n = 0;
        foreach ([[$a->id, 500000], [$b->id, 250000]] as [$branchId, $amount]) {
            CashFlow::factory()->create([
                'transaction_code' => 'CF-BP-'.(++$n),
                'business_id' => $business->id,
                'business_branch_id' => $branchId,
                'type' => 'sale', 'amount' => $amount, 'status' => 'completed',
                'category' => 'product_sales', 'transaction_date' => now()->subDay()->toDateString(),
            ]);
        }

        $this->withoutExceptionHandling();
        $r = $this->getJson('/api/reports/branch-performance?period=last_30_days&id='.$a->id);
        fwrite(STDERR, "\nSTATUS=".$r->status()."\nBODY=".substr($r->getContent(), 0, 600)."\n");
        $this->assertTrue(true);
    }
}
