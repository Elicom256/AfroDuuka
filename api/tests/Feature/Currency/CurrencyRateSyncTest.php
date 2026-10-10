<?php

namespace Tests\Feature\Currency;

use App\Models\Business;
use App\Models\Country;
use App\Models\CurrencyRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrencyRateSyncTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        Country::factory()->create(['currency_code' => 'UGX', 'iso_alpha2' => 'UG']);

        $this->business = Business::factory()->create([
            'country_id' => Country::where('currency_code', 'UGX')->value('id'),
        ]);
    }

    private function fakeProvider(): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'result' => 'success',
                'base_code' => 'UGX',
                'rates' => [
                    'UGX' => 1,
                    'USD' => 0.00027,
                    'KES' => 0.035,
                    'TZS' => 0.68,
                ],
            ]),
        ]);
    }

    public function test_the_command_syncs_live_rates_for_each_business(): void
    {
        $this->fakeProvider();

        $this->artisan('duukaflow:currency:sync-rates')->assertSuccessful();

        $this->assertDatabaseHas('currency_rates', [
            'business_id' => $this->business->id,
            'base_currency' => 'UGX',
            'target_currency' => 'USD',
            'source' => 'open_er_api',
            'valid_to' => null,
        ]);

        $this->assertDatabaseHas('currency_rates', [
            'business_id' => $this->business->id,
            'base_currency' => 'UGX',
            'target_currency' => 'KES',
        ]);

        $this->assertDatabaseMissing('currency_rates', [
            'business_id' => $this->business->id,
            'target_currency' => 'UGX',
        ]);
    }

    public function test_the_command_refreshes_rows_without_duplicating_them(): void
    {
        $this->fakeProvider();

        $this->artisan('duukaflow:currency:sync-rates')->assertSuccessful();
        $this->artisan('duukaflow:currency:sync-rates')->assertSuccessful();

        $this->assertSame(1, CurrencyRate::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('base_currency', 'UGX')
            ->where('target_currency', 'USD')
            ->count());
    }

    public function test_the_command_reports_failure_when_the_provider_is_unreachable(): void
    {
        Http::fake(['open.er-api.com/*' => Http::response([], 500)]);

        $this->artisan('duukaflow:currency:sync-rates')
            ->assertFailed()
            ->expectsOutputToContain('Failed to sync');

        $this->assertDatabaseCount('currency_rates', 0);
    }

    public function test_the_sync_respects_a_forced_base_currency(): void
    {
        Country::factory()->create(['currency_code' => 'USD', 'iso_alpha2' => 'US']);
        $usdBusiness = Business::factory()->create([
            'country_id' => Country::where('currency_code', 'USD')->value('id'),
        ]);

        Http::fake([
            'open.er-api.com/*' => Http::response([
                'result' => 'success',
                'base_code' => 'UGX',
                'rates' => [
                    'UGX' => 1,
                    'USD' => 0.00027,
                ],
            ]),
        ]);

        $this->artisan('duukaflow:currency:sync-rates')->assertSuccessful();

        $this->assertDatabaseHas('currency_rates', [
            'business_id' => $usdBusiness->id,
            'base_currency' => 'USD',
            'target_currency' => 'UGX',
        ]);
    }
}