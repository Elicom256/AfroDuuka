<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessCategory;
use App\Models\Country;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenant\BusinessContext;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * BusinessContext::businessId() has to work out whether the caller is a site
 * admin, and it used to do that with a *scoped* Role query.
 *
 * `roles` carries a business_id, so BaseModel's `business` global scope runs on
 * that query — and the scope's whole job is to ask businessId() for the tenant.
 * The two call each other until the process exhausts memory:
 *
 *   BusinessContext::businessId()
 *     -> Role::query()->whereKey(...)->value('name')
 *       -> BaseModel 'business' global scope
 *         -> BusinessContext::businessId()   -> ...
 *
 * This is not a test-harness artefact. Any authenticated request that touched a
 * tenant table died, which is also why the suite reported "Premature end of PHP
 * process" from the first test that wrote a record as an Executive.
 */
class BusinessContextRoleLookupTest extends TestCase
{
    private int $seq = 0;

    /**
     * Built by hand rather than through Business::factory().
     *
     * The factory creates a Country and a BusinessCategory from fixed lists, and
     * both tables have UNIQUE names, so it only works against an empty database.
     * Anything that ran before this test and did not roll back leaves rows that
     * make it fail, which is how this file passed alone and failed in the suite.
     */
    private function makeUser(string $roleName, ?int $branchId = null): User
    {
        $tag = $roleName.'-'.(++$this->seq).'-'.substr(md5($roleName.$this->seq.random_int(1, 1e9)), 0, 6);

        $category = BusinessCategory::create([
            'name' => "Cat $tag",
            'description' => "Cat $tag",
        ]);

        $country = Country::create([
            'name' => "Country $tag",
            'currency_code' => 'UGX',
            'currency_symbol' => 'USh',
        ]);

        $business = Business::create([
            'name' => "Biz $tag",
            'email' => "biz-$tag@example.com",
            'phone' => '+2567'.substr(md5($tag), 0, 7),
            'business_category_id' => $category->id,
            'country_id' => $country->id,
        ]);

        $branch = BusinessBranch::create([
            'business_id' => $business->id,
            'name' => 'Main',
        ]);

        $role = Role::create([
            'business_id' => $business->id,
            'name' => $roleName,
        ]);

        $user = User::create([
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => "user-$tag@example.com",
            'phone' => '+2568'.substr(md5($tag), 0, 7),
            'password' => bcrypt('secret1234'),
            'business_id' => $business->id,
            'business_branch_id' => $branchId === null ? null : $branch->id,
            'role_id' => $role->id,
        ]);

        return $user;
    }

    public function test_a_tenant_query_does_not_recurse_through_the_business_scope(): void
    {
        $executive = $this->makeUser('Executive');
        Sanctum::actingAs($executive);

        // Would exhaust memory before the fix rather than fail an assertion.
        $this->assertSame(0, Product::withoutGlobalScopes()->count());
        $this->assertIsInt(Product::count());
    }

    public function test_a_real_api_request_as_an_executive_completes(): void
    {
        $executive = $this->makeUser('Executive');
        Sanctum::actingAs($executive);

        // The full middleware stack, not just a model query: this is the path a
        // cashier actually takes when the till opens.
        $response = $this->getJson('/api/products');

        $this->assertNotSame(500, $response->status(), 'the products endpoint failed for an authenticated Executive');
        $this->assertContains($response->status(), [200, 403], 'unexpected status: '.$response->status());
    }

    public function test_business_id_resolves_for_an_ordinary_tenant_user(): void
    {
        $executive = $this->makeUser('Executive');
        Sanctum::actingAs($executive);

        $this->assertSame((int) $executive->business_id, app(BusinessContext::class)->businessId());
        $this->assertFalse(app(BusinessContext::class)->isSiteAdmin());
    }

    public function test_business_id_is_null_and_unrestricted_for_a_site_admin(): void
    {
        $siteAdmin = $this->makeUser('siteadmin');
        Sanctum::actingAs($siteAdmin);

        $this->assertNull(app(BusinessContext::class)->businessId());
        $this->assertTrue(app(BusinessContext::class)->isSiteAdmin());
    }

    public function test_the_role_lookup_is_not_itself_tenant_scoped(): void
    {
        $executive = $this->makeUser('Executive');
        Sanctum::actingAs($executive);

        $context = app(BusinessContext::class);
        $context->businessId();
        $context->businessId();
        $context->isSiteAdmin();

        // Keyed on the user, not merely cached, so switching the acting user
        // mid-process cannot serve the previous role.
        $other = $this->makeUser('siteadmin');
        Auth::setUser($other);

        $this->assertTrue($context->isSiteAdmin(), 'stale role name served after the acting user changed');
        $this->assertNull($context->businessId());
    }

    public function test_clear_drops_the_memoised_role(): void
    {
        $executive = $this->makeUser('Executive');
        Sanctum::actingAs($executive);

        $context = app(BusinessContext::class);
        $this->assertFalse($context->isSiteAdmin());

        $context->clear();

        // A long-lived queue worker must not carry one caller's role into the
        // next job it picks up.
        $siteAdmin = $this->makeUser('siteadmin');
        Auth::setUser($siteAdmin);

        $this->assertTrue($context->isSiteAdmin());
    }
}
