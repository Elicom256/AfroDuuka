<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Every export was failing.
 *
 * Three separate faults, each of which alone is enough to break the feature:
 *
 *  1. ExportButton built `${VITE_BASE_URL}/api/exports/{type}` while VITE_BASE_URL already
 *     ends in /api, so the request went to /api/api/exports/{type}, matched no route and
 *     answered 404. The UI showed "Export failed" for products, sales, purchases,
 *     customers and suppliers alike.
 *  2. The route declared `middleware('role')` with no auth:sanctum. Sanctum's guard falls
 *     back to the configured sanctum.guard (default 'web'), so Auth::user() was null,
 *     EffectiveBranchScope::branchesFor(null) returned null, the branch filter was silently
 *     skipped, and ExportService then called Auth::user()->business_id on null.
 *  3. ExportService read `name`, `phone`, `email` and `location` off Customer and Supplier.
 *     Neither table has those columns -- only company_name and a user_id -- so the export
 *     produced a file with blank columns rather than an error. That is worse than failing,
 *     because the file looked like it had worked.
 *
 * The tests pin all three: the endpoint answers 200, it is scoped to the caller's branch,
 * and the rows carry the columns that actually exist.
 *
 * What each test can and cannot catch, because two of the faults are not reachable from
 * here:
 *
 *  - Fault 1 is in the frontend URL, so no backend test can see it. The products tests
 *    would have passed before the fix.
 *  - Fault 2 is invisible under Sanctum::actingAs(), which sets the user on the sanctum
 *    guard as well as the web one. The customers and suppliers tests are the ones that
 *    catch it: without auth:sanctum, Auth::user() is null and ExportService calls
 *    ->business_id on null, which is a 500.
 *  - Fault 3 is caught by the customers, suppliers and sales tests, which assert on the
 *    content of the rows rather than only the status.
 */
class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected BusinessBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        $role = Role::factory()->create(['business_id' => $this->business->id, 'name' => 'Executive']);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_the_products_export_answers_200(): void
    {
        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'name' => 'Maize Flour 1kg',
        ]);

        $this->getJson('/api/exports/products')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /**
     * The branch scope is the whole point of fault 2. Without auth:sanctum the scope was
     * skipped entirely, so this would have returned another branch's products.
     */
    public function test_the_products_export_is_scoped_to_the_callers_branch(): void
    {
        Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'name' => 'Mine',
        ]);

        $otherBranch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        Product::factory()->create([
            'business_branch_id' => $otherBranch->id,
            'name' => 'Theirs',
        ]);

        $body = $this->getJson('/api/exports/products')->assertOk()->streamedContent();

        $this->assertStringContainsString('Mine', $body);
        $this->assertStringNotContainsString('Theirs', $body);
    }

    /**
     * A customer is a company or a person, and neither shape has a `name` column. The
     * export used to emit a blank column and look like it had worked.
     */
    public function test_the_customers_export_carries_the_columns_that_exist(): void
    {
        $contact = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'firstname' => 'Amina',
            'lastname' => 'Nabirye',
            'phone' => '0772000000',
            'email' => 'amina@example.test',
        ]);

        Customer::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $contact->id,
            'company_name' => null,
        ]);

        Customer::factory()->create([
            'business_id' => $this->business->id,
            'company_name' => 'Afro Wholesale Ltd',
        ]);

        $body = $this->getJson('/api/exports/customers')->assertOk()->streamedContent();

        $this->assertStringContainsString('Amina Nabirye', $body);
        $this->assertStringContainsString('0772000000', $body);
        $this->assertStringContainsString('amina@example.test', $body);
        $this->assertStringContainsString('Afro Wholesale Ltd', $body);
    }

    public function test_the_suppliers_export_carries_the_columns_that_exist(): void
    {
        $contact = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'firstname' => 'Joseph',
            'lastname' => 'Okello',
            'phone' => '0772111111',
            'email' => 'joseph@example.test',
        ]);

        Supplier::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $contact->id,
            'company_name' => null,
        ]);

        $body = $this->getJson('/api/exports/suppliers')->assertOk()->streamedContent();

        $this->assertStringContainsString('Joseph Okello', $body);
        $this->assertStringContainsString('0772111111', $body);
        $this->assertStringContainsString('joseph@example.test', $body);
    }

    /**
     * The sales export read `customer?->name`, which is null for the same reason. A sale to
     * a walk-in has no customer at all, and that has to stay distinguishable from a sale
     * whose customer simply has no name on file.
     */
    public function test_the_sales_export_names_the_customer(): void
    {
        $contact = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'firstname' => 'Grace',
            'lastname' => 'Atuhaire',
        ]);

        $customer = Customer::factory()->create([
            'business_id' => $this->business->id,
            'user_id' => $contact->id,
            'company_name' => null,
        ]);

        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 10,
        ]);

        $sale = \App\Models\Sale::create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'total_amount' => 15000,
            'status' => 'completed',
        ]);

        $sale->saleItems()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 15000,
            'subtotal' => 15000,
        ]);

        $body = $this->getJson('/api/exports/sales')->assertOk()->streamedContent();

        $this->assertStringContainsString('Grace Atuhaire', $body);
    }

    public function test_an_unknown_export_type_is_refused(): void
    {
        $this->getJson('/api/exports/nonsense')->assertNotFound();
    }
}
