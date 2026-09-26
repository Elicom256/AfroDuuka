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

/**
 * A monthly report describes exactly one branch, so the branch is a scoping decision and
 * not a caption.
 *
 * This is the failure mode worth guarding: a document headed with one branch's name while
 * carrying a total that includes another branch's trading. Nothing in the document says
 * so, the number is plausible, and the branch manager who has to act on it is misled.
 */
class MonthlyPerformanceBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    private static int $cashFlowCounter = 0;

    private Business $business;

    private BusinessBranch $kampala;

    private BusinessBranch $jinja;

    /**
     * A business admin: no business_branch_id, so the branch scope admits every branch
     * of their business. This is the account that could leak a sibling branch's figures.
     */
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create(['name' => 'Nakatomi Trading']);
        $this->kampala = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Kampala Road',
        ]);
        $this->jinja = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Jinja Main Street',
        ]);
        $this->admin = $this->userFor($this->business, null);
    }

    public function test_a_report_contains_only_the_requested_branchs_figures(): void
    {
        $this->cashFlow($this->kampala, 'sale', 500_000);
        $this->cashFlow($this->jinja, 'sale', 9_000_000);

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/reports/monthly-performance?month=2026-08&branch_id='.$this->kampala->id)
            ->assertOk()
            ->assertJsonPath('data.branch_name', 'Kampala Road')
            ->assertJsonPath('data.branch_id', $this->kampala->id)
            ->assertJsonPath('data.figures.sales', 500000);
    }

    public function test_a_branch_manager_cannot_read_another_branchs_report(): void
    {
        $this->cashFlow($this->kampala, 'sale', 500_000);
        $this->cashFlow($this->jinja, 'sale', 9_000_000);

        Sanctum::actingAs($this->userFor($this->business, $this->kampala));

        $this->getJson('/api/reports/monthly-performance?month=2026-08&branch_id='.$this->jinja->id)
            ->assertForbidden();
    }

    public function test_a_branch_manager_is_scoped_to_their_own_branch_without_asking(): void
    {
        $this->cashFlow($this->kampala, 'sale', 500_000);
        $this->cashFlow($this->jinja, 'sale', 9_000_000);

        Sanctum::actingAs($this->userFor($this->business, $this->kampala));

        $this->getJson('/api/reports/monthly-performance?month=2026-08')
            ->assertOk()
            ->assertJsonPath('data.branch_name', 'Kampala Road')
            ->assertJsonPath('data.figures.sales', 500000);
    }

    public function test_a_branch_from_another_business_is_refused(): void
    {
        $rival = Business::factory()->create();
        $rivalBranch = BusinessBranch::factory()->create(['business_id' => $rival->id]);

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/reports/monthly-performance?month=2026-08&branch_id='.$rivalBranch->id)
            ->assertForbidden();
    }

    public function test_a_business_admin_must_choose_a_branch(): void
    {
        $this->cashFlow($this->kampala, 'sale', 500_000);

        Sanctum::actingAs($this->admin);

        // Defaulting to the first branch would answer with a real, plausible document
        // about a branch the user never asked for. An error is the honest answer.
        $this->getJson('/api/reports/monthly-performance?month=2026-08')
            ->assertStatus(422)
            ->assertJsonValidationErrors('branch_id');
    }

    public function test_the_only_branch_is_used_when_no_selection_is_needed(): void
    {
        $this->jinja->delete();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/reports/monthly-performance?month=2026-08')
            ->assertOk()
            ->assertJsonPath('data.branch_name', 'Kampala Road');
    }

    public function test_a_business_with_no_branches_is_reported_as_such(): void
    {
        $this->kampala->delete();
        $this->jinja->delete();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/reports/monthly-performance?month=2026-08')
            ->assertStatus(422)
            ->assertJsonValidationErrors('branch_id');
    }

    public function test_the_download_names_the_branch_and_holds_only_its_figures(): void
    {
        $this->cashFlow($this->kampala, 'sale', 500_000);
        $this->cashFlow($this->jinja, 'sale', 9_000_000);

        Sanctum::actingAs($this->admin);

        $response = $this->get('/api/reports/monthly-performance/pdf?month=2026-08&branch_id='.$this->kampala->id);

        $response->assertOk();
        $response->assertHeader(
            'content-disposition',
            'attachment; filename=monthly-report-kampala-road-august-2026.pdf'
        );

        $text = $this->textOf($response->getContent());

        $this->assertStringContainsStringIgnoringCase('Kampala Road', $text);
        $this->assertStringContainsStringIgnoringCase('500,000.00', $text);
        $this->assertStringNotContainsStringIgnoringCase('9,000,000.00', $text);
        $this->assertStringNotContainsStringIgnoringCase('Jinja', $text);
    }

    public function test_a_one_branch_report_does_not_print_a_single_row_branch_table(): void
    {
        $this->cashFlow($this->kampala, 'sale', 500_000);

        Sanctum::actingAs($this->admin);

        $text = $this->textOf(
            $this->get('/api/reports/monthly-performance/pdf?month=2026-08&branch_id='.$this->kampala->id)->getContent()
        );

        // The branch is already the document's subject; a table titled "By branch"
        // holding that one branch reads as though figures were missing.
        $this->assertStringNotContainsStringIgnoringCase('By branch', $text);
    }

    private function userFor(Business $business, ?BusinessBranch $branch): User
    {
        return User::factory()->create([
            'business_id' => $business->id,
            'business_branch_id' => $branch?->id,
            'role_id' => Role::factory()->create(['business_id' => $business->id])->id,
        ]);
    }

    private function cashFlow(BusinessBranch $branch, string $type, float $amount): CashFlow
    {
        return CashFlow::factory()->create([
            'transaction_code' => 'CF-BS-'.str_pad(++self::$cashFlowCounter, 6, '0', STR_PAD_LEFT),
            'business_id' => $this->business->id,
            'business_branch_id' => $branch->id,
            'type' => $type,
            'amount' => $amount,
            'status' => 'completed',
            'category' => $type === 'sale' ? 'product_sales' : $type.'s',
            'transaction_date' => '2026-08-04',
        ]);
    }

    /**
     * @see MonthlyPerformanceReportTest::textOf() for why this is done in PHP.
     */
    private function textOf(string $pdf): string
    {
        $text = '';

        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $streams);

        foreach ($streams[1] as $stream) {
            $raw = @gzuncompress($stream) ?: $stream;

            if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/', $raw, $literals)) {
                foreach ($literals[0] as $literal) {
                    $text .= substr($literal, 1, -1);
                }
            }
        }

        return str_replace("\0", '', $text);
    }
}
