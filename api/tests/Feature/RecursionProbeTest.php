<?php
namespace Tests\Feature;
use App\Models\{Business,Role,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
class RecursionProbeTest extends TestCase
{
    use RefreshDatabase;
    public function test_probe(): void
    {
        $b = Business::factory()->create();
        $r = Role::factory()->create(['business_id'=>$b->id,'name'=>'Executive']);
        $u = User::factory()->create(['business_id'=>$b->id,'business_branch_id'=>null,'role_id'=>$r->id]);
        Sanctum::actingAs($u);
        fwrite(STDERR, "\n  calling \\App\\Models\\Product::count() as an Executive...\n");
        \App\Models\Product::count();
        fwrite(STDERR, "  survived\n");
        $this->assertTrue(true);
    }
}
