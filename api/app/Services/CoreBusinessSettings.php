<?php

namespace App\Services;

use App\Models\CoreSettings\AttendanceSettings;
use App\Models\CoreSettings\CreditSetting;
use App\Models\CoreSettings\CustomersSettings;
use App\Models\CoreSettings\DebitSetting;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\CoreSettings\PromotionsSettings;
use App\Models\CoreSettings\ReportsSettings;
use App\Models\CoreSettings\SuppliersSettings;

class CoreBusinessSettings
{
    public function coreSettings(string $businessId)
    {
        $settings = [];
        // Suppliers and Customers are core People features the executive and branch
        // manager are meant to manage, so a new business gets them enabled and the
        // People sidebar entries visible. Everything below is opt-in and stays disabled.
        $settings[] = SuppliersSettings::firstOrCreate(['business_id' => $businessId], ['status' => 'enabled']);
        $settings[] = AttendanceSettings::firstOrCreate(['business_id' => $businessId, 'status' => 'disabled']);
        $settings[] = CustomersSettings::firstOrCreate(['business_id' => $businessId], ['status' => 'enabled']);
        $settings[] = PromotionsSettings::firstOrCreate(['business_id' => $businessId, 'status' => 'disabled']);
        $settings[] = ReportsSettings::firstOrCreate(['business_id' => $businessId, 'status' => 'disabled']);
        $settings[] = CreditSetting::firstOrCreate(['business_id' => $businessId, 'status' => 'disabled']);
        $settings[] = DebitSetting::firstOrCreate(['business_id' => $businessId, 'status' => 'disabled']);

        $paymentMethods = ['mobile_money', 'card', 'cash', 'credit', 'cryptocurrency'];

        foreach ($paymentMethods as $paymentMethod) {
            $settings[] = PaymentMethod::firstOrCreate(
                ['business_id' => $businessId, 'method' => $paymentMethod],
                ['status' => 'disabled']
            );
        }
        $total = count($settings);

        return "created $total core settings along";
    }
}
