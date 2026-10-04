<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportExportSeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where('email', 'testbusinessone@gmail.com')->first();
        $user = User::where('email', 'testbusinessone@gmail.com')->first();

        if (! $business || ! $user) {
            return;
        }

        $exports = [
            [
                'report_type' => 'sales',
                'format' => 'csv',
                'status' => 'completed',
                'parameters' => ['date_from' => now()->subDays(30)->toDateString(), 'date_to' => now()->toDateString()],
            ],
            [
                'report_type' => 'inventory',
                'format' => 'pdf',
                'status' => 'completed',
                'parameters' => ['branch_id' => $business->branches->first()?->id],
            ],
            [
                'report_type' => 'financial',
                'format' => 'pdf',
                'status' => 'processing',
                'parameters' => ['month' => now()->subMonth()->format('Y-m')],
            ],
        ];

        foreach ($exports as $export) {
            ReportExport::create(array_merge([
                'business_id' => $business->id,
                'user_id' => $user->id,
            ], $export));
        }
    }
}
