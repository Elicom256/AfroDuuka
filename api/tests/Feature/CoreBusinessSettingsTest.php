<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CoreSettings\AttendanceSettings;
use App\Models\CoreSettings\CustomersSettings;
use App\Models\CoreSettings\SuppliersSettings;
use App\Services\CoreBusinessSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suppliers and Customers are the People features the exec and branch manager
 * are meant to manage, so a new business must get them enabled — otherwise the
 * sidebar hides the entries (useFeatureSettings gates on settingKey) and the
 * executive reports "there are no suppliers and customers". Every other core
 * setting stays opt-in.
 */
class CoreBusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_suppliers_and_customers_are_enabled_for_a_new_business(): void
    {
        $business = Business::factory()->create();

        app(CoreBusinessSettings::class)->coreSettings($business->id);

        $this->assertSame(
            'enabled',
            SuppliersSettings::where('business_id', $business->id)->value('status'),
        );
        $this->assertSame(
            'enabled',
            CustomersSettings::where('business_id', $business->id)->value('status'),
        );
    }

    public function test_other_core_settings_stay_disabled(): void
    {
        $business = Business::factory()->create();

        app(CoreBusinessSettings::class)->coreSettings($business->id);

        $this->assertSame(
            'disabled',
            AttendanceSettings::where('business_id', $business->id)->value('status'),
        );
    }

    public function test_running_core_settings_twice_does_not_duplicate_rows(): void
    {
        $business = Business::factory()->create();

        $service = app(CoreBusinessSettings::class);
        $service->coreSettings($business->id);
        $service->coreSettings($business->id);

        $this->assertSame(1, SuppliersSettings::where('business_id', $business->id)->count());
        $this->assertSame(1, CustomersSettings::where('business_id', $business->id)->count());
    }
}
