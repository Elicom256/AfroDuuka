<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessTaxes;
use App\Models\BusinessTaxPayment;
use App\Models\CoreSettings\PaymentMethod;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BusinessTaxPaymentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $business = Business::where('email', 'testbusinessone@gmail.com')->first();
        $userId = User::with("role")->where("business_id", $business->id)->whereHas("role", function($q){
            $q->where("name", "admin");
        })->value("id");

        if (!$business) {
            $this->command->warn('❌ Business not found. Skipping BusinessTaxPaymentsSeeder.');
            return;
        }

        $branch = BusinessBranch::where('business_id', $business->id)
                    ->where('name', 'Main Branch')
                    ->first();

        if (!$branch) {
            $this->command->warn('❌ Main Branch not found. Skipping seeder.');
            return;
        }

        $taxes = BusinessTaxes::where('business_id', $business->id)
                    ->where('status', 'active')
                    ->get();

        if ($taxes->isEmpty()) {
            $this->command->warn('❌ No active taxes found.');
            return;
        }

        $admin = User::first();

        $this->command->info('Seeding Business Tax Payments...');

        $periods = [
            '2025-Q1', '2025-Q2', '2025-Q3', '2025-Q4',
            '2026-Q1', '2026-Q2'
        ];

        $count = 0;

        foreach ($taxes as $tax) {
            $paymentMethodId = PaymentMethod::where("business_id", $business->id)->value("id");
            foreach ($periods as $period) {
                
                $amount = rand(15000, 85000);

                $paidAmount = rand(0, $amount);
                
                $status = match(true) {
                    $paidAmount == 0        => 'unpaid',
                    $paidAmount == $amount  => 'paid',
                    default                 => 'partial',
                };

                // ✅ Fixed Due Date Logic
                $year = substr($period, 0, 4);
                $quarter = substr($period, 6, 1); // Get Q1, Q2, etc.

                $month = match($quarter) {
                    '1' => '03', // March end for Q1
                    '2' => '06', // June end for Q2
                    '3' => '09', // September end for Q3
                    '4' => '12', // December end for Q4
                    default => '12',
                };

                $dueDate = Carbon::create($year, $month, 30); // Last day of the quarter

               $payment = BusinessTaxPayment::create([
                    'business_branch_id' => $branch->id,
                    'business_tax_id'    => $tax->id,
                    
                    'amount'             => $amount,
                    'paid_amount'        => $paidAmount,
                    'balance'            => $amount - $paidAmount,
                    
                    'tax_period'         => $period,
                    'due_date'           => $dueDate,
                    'payment_date'       => in_array($status, ['paid', 'partial']) 
                                            ? $dueDate->copy()->subDays(rand(0, 20)) 
                                            : null,
                    'paid_at'            => in_array($status, ['paid', 'partial']) 
                                            ? Carbon::now()->subDays(rand(1, 40)) 
                                            : null,
                    
                    'status'             => $status,
                    'reference_number'   => 'TXP-' . strtoupper(uniqid()),
                    'payment_method_id'     => $paymentMethodId,
                    'notes'              => 'Tax payment for ' . $period,
                    'payment_metadata'   => [
                        'transaction_id' => 'TXN' . rand(100000, 999999),
                        'gateway'        => 'system'
                    ],
                    
                    'created_by'         => $admin?->id,
                    'updated_by'         => $admin?->id,
                ]);

                $count++;
               ActivityLog::create([
                     "user_id" => $userId,
                     "business_id" => $business->id,
                     "business_branch_id" => $branch->id,
                     "action" => " Tax Payments",
                     "description" =>"Business Tax Payments seeded successfully"
                 ]);
            }
            // log

        }

        $this->command->info("✅ Business Tax Payments seeded successfully! ({$count} records created)");
    }
}