<?php

namespace Database\Seeders;

use App\Models\CoreSettings\ReportsSettings;
use Illuminate\Database\Seeder;

class ReportsSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = \App\Models\Business::where('email', 'testbusinessone@gmail.com')->value('id');

        if (!$businessId) {
            return;
        }

        foreach (['enabled', 'disabled'] as $status) {
            ReportsSettings::updateOrCreate(
                ['business_id' => $businessId, 'status' => $status],
                ['business_id' => $businessId, 'status' => $status]
            );
        }
    }
}
