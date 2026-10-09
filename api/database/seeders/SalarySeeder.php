<?php

namespace Database\Seeders;

use App\Models\Salary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalarySeeder extends Seeder
{
    /**
     * One monthly salary per role, which is the whole point of the model: a role
     * carries an amount and every worker holding it is paid that. Amounts step up
     * with the role id, which is seeded executive -> branch manager -> others, so the
     * numbers read as a plausible payroll rather than noise.
     */
    public function run(): void
    {
        // Read through the query builder rather than the model: Role carries
        // BaseModel's tenant global scopes, and a seeder runs with no authenticated
        // user, so a scoped read resolves to `whereRaw('0 = 1')` and finds nothing.
        DB::table('roles')
            ->whereNotNull('business_id')
            ->orderBy('id')
            ->each(function (object $role) {
                $exists = DB::table('salaries')
                    ->where('role_id', $role->id)
                    ->whereNull('business_branch_id')
                    ->whereNull('deleted_at')
                    ->exists();

                if ($exists) {
                    return;
                }

                $salary = new Salary([
                    // Explicit, because a seeder has no authenticated user and no
                    // BusinessContext for BaseModel to stamp it from, and the column
                    // is NOT NULL.
                    'business_id' => $role->business_id,
                    'role_id' => $role->id,
                    'amount' => 1_500_000 + ($role->id * 250_000),
                    'period' => 'monthly',
                    'status' => 'active',
                ]);

                $salary->coversAllBranches = true;
                $salary->save();
            });
    }
}
