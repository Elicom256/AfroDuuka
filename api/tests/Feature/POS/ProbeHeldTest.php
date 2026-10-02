<?php
namespace Tests\Feature\POS;
use App\Models\{Business,BusinessBranch,Customer,Product,Role,Sale,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
class ProbeHeldTest extends TestCase {
  use RefreshDatabase;
  public function test_probe(): void {
    $b=Business::factory()->create(); $br=BusinessBranch::factory()->create(['business_id'=>$b->id]);
    $p=Product::factory()->create(['business_branch_id'=>$br->id,'name'=>'P','sku'=>'S','barcode'=>'1','quantity'=>10,'selling_price'=>1000,'status'=>'active']);
    $c=Customer::factory()->create(['business_id'=>$b->id,'business_branch_id'=>$br->id]);
    $u1=User::factory()->create(['business_id'=>$b->id,'business_branch_id'=>$br->id,'role_id'=>Role::factory()->create(['business_id'=>$b->id,'name'=>'Executive'])->id]);
    $held=Sale::create(['business_id'=>$b->id,'business_branch_id'=>$br->id,'user_id'=>$u1->id,'customer_id'=>$c->id,'subtotal'=>10000,'tax_amount'=>0,'total_amount'=>10000,'status'=>'held','note'=>'x']);
    $u2=User::factory()->create(['business_id'=>$b->id,'business_branch_id'=>$br->id,'role_id'=>Role::factory()->create(['business_id'=>$b->id,'name'=>'Executive'])->id]);
    Sanctum::actingAs($u2);
    $this->withoutExceptionHandling();
    $this->postJson('/api/pos/checkout',['sale_id'=>$held->id,'items'=>[['product_id'=>$p->id,'quantity'=>1,'unit_price'=>10000]],'payments'=>[['method'=>'cash','amount'=>10000]]])->assertStatus(404);
  }
}
