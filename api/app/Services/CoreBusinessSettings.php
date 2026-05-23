<?php

namespace App\Services;

use App\Models\CoreSettings\AttendanceSettings;
use App\Models\CoreSettings\CreditSetting;
use App\Models\CoreSettings\CustomersSettings;
use App\Models\CoreSettings\DebitSetting;
use App\Models\CoreSettings\PaymentStatus;
use App\Models\CoreSettings\PromotionsSettings;
use App\Models\CoreSettings\ReportsSettings;
use App\Models\CoreSettings\SuppliersSettings;

class CoreBusinessSettings
{
    public function coreSettings(string $businessId)
    {
        $settings = [];
        $settings[] = SuppliersSettings::create(["business_id" => $businessId, "status" => "disabled"]);
        $settings[] = AttendanceSettings::create(["business_id" => $businessId, "status" => "disabled"]);
        $settings[] = CustomersSettings::create(["business_id" => $businessId, "status" => "disabled"]);
        $settings[] = PromotionsSettings::create(["business_id" => $businessId, "status" => "disabled"]);
        $settings[] = ReportsSettings::create(["business_id" => $businessId, "status" => "disabled"]);
        $settings[] = CreditSetting::create(["business_id" => $businessId, "status" => "disabled"]);
        $settings[] = DebitSetting::create(["business_id" => $businessId, "status" => "disabled"]);

        $paymentMethods = ["mobile_money", "card", "cash", "credit", "cryptocurrency"];
        
        foreach ($paymentMethods as $paymentMethod) {
          $settings[] = PaymentStatus::create([
            "business_id" => $businessId,
            "method" => $paymentMethod,
            "status" => "disabled",
            ]);
        }
        $total = count($settings);
        return "created $total core settings along";
    }
}