<?php

namespace Tests\Feature\Reports;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenant\BusinessContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The admin reports monthly PDF download.
 *
 * The figure aggregation matters more here than the plumbing: the same service feeds the
 * emailed attachment, so a tenant-isolation failure or a period boundary that lands on the
 * wrong day would put a wrong total into a document that goes to a customer's inbox.
 */
class MonthlyPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected BusinessBranch $branch;

    protected User $user;

    private static int $cashFlowCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create(['name' => 'Nakatomi Trading']);
        $this->branch = BusinessBranch::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Kampala Road',
        ]);
        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => Role::factory()->create(['business_id' => $this->business->id])->id,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_it_returns_the_figures_for_the_requested_month(): void
    {
        $this->cashFlow('sale', 500_000, '2026-08-04');
        $this->cashFlow('sale', 250_000, '2026-08-20');
        $this->cashFlow('purchase', 100_000, '2026-08-11');
        $this->cashFlow('expense', 25_000, '2026-08-11');

        $this->getJson('/api/reports/monthly-performance?month=2026-08')
            ->assertOk()
            ->assertJsonPath('data.business_name', 'Nakatomi Trading')
            ->assertJsonPath('data.period', 'August 2026')
            ->assertJsonPath('data.month', '2026-08')
            ->assertJsonPath('data.figures.sales', 750000)
            ->assertJsonPath('data.figures.purchases', 100000)
            ->assertJsonPath('data.figures.expenses', 25000)
            ->assertJsonPath('data.figures.profit_loss', 625000)
            ->assertJsonPath('data.counts.sales', 2)
            ->assertJsonPath('data.counts.purchases', 1)
            ->assertJsonPath('data.branches.0.name', 'Kampala Road');
    }

    public function test_it_excludes_transactions_outside_the_month(): void
    {
        $this->cashFlow('sale', 100_000, '2026-07-31');
        $this->cashFlow('sale', 400_000, '2026-08-01');
        $this->cashFlow('sale', 500_000, '2026-08-31');
        $this->cashFlow('sale', 900_000, '2026-09-01');

        $this->getJson('/api/reports/monthly-performance?month=2026-08')
            ->assertOk()
            ->assertJsonPath('data.figures.sales', 900000)
            ->assertJsonPath('data.counts.sales', 2);
    }

    public function test_it_ignores_non_ledger_transaction_types(): void
    {
        // Movements of money that are not trading income, cost or overhead. Counting
        // these would double-count the sale or purchase they relate to.
        $this->cashFlow('sale', 300_000, '2026-08-04');
        $this->cashFlow('payment_in', 300_000, '2026-08-04');
        $this->cashFlow('payment_out', 40_000, '2026-08-05');
        $this->cashFlow('refund', 20_000, '2026-08-06');
        $this->cashFlow('adjustment', 10_000, '2026-08-07');

        $this->getJson('/api/reports/monthly-performance?month=2026-08')
            ->assertOk()
            ->assertJsonPath('data.figures.sales', 300000)
            ->assertJsonPath('data.figures.purchases', 0)
            ->assertJsonPath('data.figures.expenses', 0)
            ->assertJsonPath('data.figures.profit_loss', 300000);
    }

    public function test_it_ignores_cash_flows_that_are_not_completed(): void
    {
        $this->cashFlow('sale', 700_000, '2026-08-04');
        $this->cashFlow('sale', 800_000, '2026-08-05', ['status' => 'pending']);

        $this->getJson('/api/reports/monthly-performance?month=2026-08')
            ->assertOk()
            ->assertJsonPath('data.figures.sales', 700000);
    }

    public function test_it_never_aggregates_another_businesss_cash_flows(): void
    {
        $this->cashFlow('sale', 100_000, '2026-08-04');

        $rival = Business::factory()->create();
        $rivalBranch = BusinessBranch::factory()->create(['business_id' => $rival->id]);
        $rivalUser = User::factory()->create([
            'business_id' => $rival->id,
            'business_branch_id' => $rivalBranch->id,
            'role_id' => Role::factory()->create(['business_id' => $rival->id])->id,
        ]);

        // Written with the tenant context explicitly set, so the row lands in the rival
        // business rather than being scoped to the authenticated user's.
        app(BusinessContext::class)->run($rival->id, function () use ($rival, $rivalBranch) {
            $this->cashFlow('sale', 9_999_999, '2026-08-04', [
                'business_id' => $rival->id,
                'business_branch_id' => $rivalBranch->id,
            ]);
        });

        Sanctum::actingAs($rivalUser);

        $this->getJson('/api/reports/monthly-performance?month=2026-08')
            ->assertOk()
            ->assertJsonPath('data.business_name', $rival->name)
            // The original business's 100,000 must not appear anywhere in the rival's report.
            ->assertJsonPath('data.figures.sales', 9_999_999);
    }

    public function test_a_business_id_in_the_query_string_is_ignored(): void
    {
        $this->cashFlow('sale', 250_000, '2026-08-04');

        $rival = Business::factory()->create();
        $rivalBranch = BusinessBranch::factory()->create(['business_id' => $rival->id]);
        app(BusinessContext::class)->run($rival->id, function () use ($rival, $rivalBranch) {
            $this->cashFlow('sale', 5_000_000, '2026-08-04', [
                'business_id' => $rival->id,
                'business_branch_id' => $rivalBranch->id,
            ]);
        });

        $this->getJson('/api/reports/monthly-performance?month=2026-08&business_id='.$rival->id)
            ->assertOk()
            ->assertJsonPath('data.business_name', 'Nakatomi Trading')
            ->assertJsonPath('data.figures.sales', 250000);
    }

    public function test_it_defaults_to_the_last_completed_month(): void
    {
        Carbon::setTestNow('2026-09-14 10:00:00');

        $this->cashFlow('sale', 150_000, '2026-08-05');
        $this->cashFlow('sale', 450_000, '2026-09-05');

        // August, not the still-accumulating September.
        $this->getJson('/api/reports/monthly-performance')
            ->assertOk()
            ->assertJsonPath('data.month', '2026-08')
            ->assertJsonPath('data.figures.sales', 150000);
    }

    public function test_it_rejects_a_malformed_month(): void
    {
        // Carbon would silently roll 2026-13 over into January 2027 and quietly serve
        // the wrong month, so the round trip has to be checked instead of trusted.
        foreach (['2026-13', 'august', '2026-8', '08-2026'] as $month) {
            $this->getJson('/api/reports/monthly-performance?month='.$month)
                ->assertStatus(422)
                ->assertJsonValidationErrors('month');
        }
    }

    public function test_it_returns_the_currency_of_the_businesss_country(): void
    {
        $this->business->update([
            'country_id' => Country::factory()->create(['currency_code' => 'TZS'])->id,
        ]);

        $this->cashFlow('sale', 100_000, '2026-08-04');

        $this->getJson('/api/reports/monthly-performance?month=2026-08')
            ->assertOk()
            ->assertJsonPath('data.currency', 'TZS');
    }

    public function test_it_downloads_a_pdf_as_an_attachment(): void
    {
        $this->cashFlow('sale', 500_000, '2026-08-04');
        $this->cashFlow('expense', 100_000, '2026-08-09');

        $response = $this->get('/api/reports/monthly-performance/pdf?month=2026-08');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename=monthly-report-august-2026.pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_the_downloaded_pdf_contains_the_figures_and_the_period(): void
    {
        $this->cashFlow('sale', 750_000, '2026-08-04');
        $this->cashFlow('purchase', 100_000, '2026-08-11');
        $this->cashFlow('expense', 25_000, '2026-08-11');

        $text = $this->textOf(
            $this->get('/api/reports/monthly-performance/pdf?month=2026-08')->getContent()
        );

        $this->assertPdfContains('Nakatomi Trading', $text);
        $this->assertPdfContains('August 2026', $text);
        $this->assertPdfContains('750,000.00', $text);
        $this->assertPdfContains('625,000.00', $text);
        $this->assertPdfContains('Kampala Road', $text);
    }

    public function test_a_loss_is_rendered_as_a_loss(): void
    {
        $this->cashFlow('expense', 900_000, '2026-08-04');

        $text = $this->textOf(
            $this->get('/api/reports/monthly-performance/pdf?month=2026-08')->getContent()
        );

        // 900,000 of expenses and no sales, so the figure has to print as a loss and the
        // document must not quietly present a negative number as a positive one.
        $this->assertPdfContains('900,000.00', $text);
        $this->assertMatchesRegularExpression(
            '/loss/i',
            $text,
            'A month with only expenses should be labelled as a loss.'
        );
    }

    public function test_a_month_with_no_transactions_still_downloads(): void
    {
        // The user asked for the document. An empty month is a real report with zeroes
        // in it, not a failure, and is most likely exactly what they want to forward on.
        $this->get('/api/reports/monthly-performance/pdf?month=2026-08')->assertOk();
    }

    /**
     * Case-insensitive, because the template styles the business name in caps and a
     * change to that styling is not a report regression.
     */
    private function assertPdfContains(string $needle, string $text): void
    {
        $this->assertStringContainsStringIgnoringCase($needle, $text);
    }

    private function cashFlow(string $type, float $amount, string $date, array $overrides = []): CashFlow
    {
        return CashFlow::factory()->create(array_merge([
            'transaction_code' => 'CF-TEST-'.str_pad(++self::$cashFlowCounter, 6, '0', STR_PAD_LEFT),
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'type' => $type,
            'amount' => $amount,
            'status' => 'completed',
            'category' => $type === 'sale' ? 'product_sales' : $type.'s',
            'transaction_date' => $date,
        ], $overrides));
    }

    /**
     * Pull the visible text back out of a rendered PDF.
     *
     * Asserting on the raw bytes only ever proves the file is non-empty. Content streams
     * are Flate-compressed, so the figures do not appear in the response body at all and
     * a template printing the wrong number would pass. So the streams are inflated and
     * the text-show operands are pulled out.
     *
     * Done in PHP rather than by shelling out to `pdftotext`, which is not installed in
     * the application container — a test that silently depends on a binary nobody has is
     * a test that stops being run.
     *
     * NUL bytes are dropped because dompdf writes TrueType subset fonts as 2-byte codes,
     * which is why a naive extraction yields "N A K A T O M I" rather than "NAKATOMI".
     * The result also includes glyph data from the embedded font programmes, so it is
     * only good for asserting that a distinctive string is present, never for counting
     * occurrences.
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
