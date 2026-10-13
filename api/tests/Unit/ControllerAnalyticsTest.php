<?php

namespace Tests\Unit;

use App\Http\Controllers\Controller;
use Tests\TestCase;

class ControllerAnalyticsTest extends TestCase
{
    public function test_period_defaults_and_rejects_invalid_values(): void
    {
        $this->assertSame('last_7_days', Controller::resolveAnalyticsPeriod('invalid', ['last_7_days', 'this_month']));
        $this->assertSame('this_month', Controller::resolveAnalyticsPeriod('this_month', ['last_7_days', 'this_month']));
        $this->assertSame('last_7_days', Controller::resolveAnalyticsPeriod(null, ['last_7_days', 'this_month']));
    }
}
