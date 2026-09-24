<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\TaxCategory;
use App\Models\TaxPayment;
use App\Models\TaxRate;
use Carbon\Carbon;
use Database\Seeders\Concerns\SeedsFixtureBusiness;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    use SeedsFixtureBusiness;

    public function run(): void
    {
        $business = $this->fixtureBusiness();

        $mainBranch = BusinessBranch::where('business_id', $business->id)
            ->where('name', 'Main Branch')
            ->value('id')
            ?? $this->fixtureMainBranch($business)->id;

        $categories = [
            ['name' => 'VAT', 'description' => 'Value Added Tax on goods and services'],
            ['name' => 'PAYE', 'description' => 'Pay As You Earn income tax on employee salaries'],
            ['name' => 'Local Service Tax', 'description' => 'Annual local service tax for the business'],
            ['name' => 'Corporate Income Tax', 'description' => 'Business income tax on company profits'],
        ];

        $categoryIds = [];
        foreach ($categories as $cat) {
            $category = TaxCategory::updateOrCreate(
                ['business_branch_id' => $mainBranch, 'name' => $cat['name']],
                [
                    'description' => $cat['description'],
                    'is_active'   => true,
                ]
            );
            $categoryIds[$cat['name']] = $category->id;
        }
        $this->command->info('✅ Seeded ' . count($categories) . ' tax categories');

        $rates = [
            'VAT' => [
                ['name' => 'Standard Rate', 'rate' => 0.18, 'jurisdiction_zone' => 'Uganda'],
                ['name' => 'Reduced Rate', 'rate' => 0.07, 'jurisdiction_zone' => 'Uganda'],
            ],
            'PAYE' => [
                ['name' => 'Lower Band', 'rate' => 0.10, 'jurisdiction_zone' => 'Uganda'],
                ['name' => 'Middle Band', 'rate' => 0.20, 'jurisdiction_zone' => 'Uganda'],
                ['name' => 'Upper Band', 'rate' => 0.30, 'jurisdiction_zone' => 'Uganda'],
            ],
            'Local Service Tax' => [
                ['name' => 'Trading License', 'rate' => 0.05, 'jurisdiction_zone' => 'Kampala'],
            ],
            'Corporate Income Tax' => [
                ['name' => 'Standard Rate', 'rate' => 0.30, 'jurisdiction_zone' => 'Uganda'],
            ],
        ];

        $rateCount = 0;
        foreach ($rates as $categoryName => $rateList) {
            foreach ($rateList as $rate) {
                TaxRate::updateOrCreate(
                    ['tax_category_id' => $categoryIds[$categoryName], 'name' => $rate['name']],
                    [
                        'rate'              => $rate['rate'],
                        'jurisdiction_zone' => $rate['jurisdiction_zone'],
                        'is_active'         => true,
                    ]
                );
                $rateCount++;
            }
        }
        $this->command->info('✅ Seeded ' . $rateCount . ' tax rates');

        $thisMonth = Carbon::now();

        $payments = [
            [
                'category'         => 'VAT',
                'amount'           => 1_250_000,
                'payment_date'     => $thisMonth->copy()->subMonth()->day(10),
                'tax_period_start' => $thisMonth->copy()->subMonths(3)->startOfMonth(),
                'tax_period_end'   => $thisMonth->copy()->subMonths(3)->endOfMonth(),
                'reference'        => 'URA-VAT-' . $thisMonth->copy()->subMonth()->format('Ym'),
                'notes'            => 'Quarterly VAT remittance',
            ],
            [
                'category'         => 'VAT',
                'amount'           => 980_000,
                'payment_date'     => $thisMonth->copy()->subMonths(2)->day(12),
                'tax_period_start' => $thisMonth->copy()->subMonths(4)->startOfMonth(),
                'tax_period_end'   => $thisMonth->copy()->subMonths(4)->endOfMonth(),
                'reference'        => 'URA-VAT-' . $thisMonth->copy()->subMonths(2)->format('Ym'),
                'notes'            => 'Monthly VAT remittance',
            ],
            [
                'category'         => 'PAYE',
                'amount'           => 640_000,
                'payment_date'     => $thisMonth->copy()->subMonth()->day(15),
                'tax_period_start' => $thisMonth->copy()->subMonth()->startOfMonth(),
                'tax_period_end'   => $thisMonth->copy()->subMonth()->endOfMonth(),
                'reference'        => 'URA-PAYE-' . $thisMonth->copy()->subMonth()->format('Ym'),
                'notes'            => 'Monthly PAYE on employee salaries',
            ],
            [
                'category'         => 'Local Service Tax',
                'amount'           => 300_000,
                'payment_date'     => $thisMonth->copy()->startOfMonth(),
                'tax_period_start' => $thisMonth->copy()->startOfYear()->startOfMonth(),
                'tax_period_end'   => $thisMonth->copy()->endOfYear()->endOfMonth(),
                'reference'        => 'KCCA-LST-' . $thisMonth->year,
                'notes'            => 'Annual local service tax payment',
            ],
            [
                'category'         => 'Corporate Income Tax',
                'amount'           => 2_400_000,
                'payment_date'     => $thisMonth->copy()->subDay(),
                'tax_period_start' => null,
                'tax_period_end'   => null,
                'reference'        => 'URA-CIT-' . ($thisMonth->year - 1),
                'notes'            => 'Prior year corporate income tax',
            ],
        ];

        $paymentCount = 0;
        foreach ($payments as $payment) {
            TaxPayment::updateOrCreate(
                [
                    'business_branch_id' => $mainBranch,
                    'reference'          => $payment['reference'],
                ],
                [
                    'tax_category_id'   => $categoryIds[$payment['category']],
                    'amount'            => $payment['amount'],
                    'payment_date'      => $payment['payment_date']->toDateString(),
                    'tax_period_start'  => $payment['tax_period_start']?->toDateString(),
                    'tax_period_end'    => $payment['tax_period_end']?->toDateString(),
                    'notes'             => $payment['notes'],
                ]
            );
            $paymentCount++;
        }
        $this->command->info('✅ Seeded ' . $paymentCount . ' tax payments');
    }
}