<?php

namespace Database\Seeders;

use App\Models\Role;
use Database\Seeders\Concerns\SeedsFixtureBusiness;
use Illuminate\Database\Seeder;

class RoleTableSeeder extends Seeder
{
    use SeedsFixtureBusiness;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // Get business safely
        $business = $this->fixtureBusiness();

        $roles = ['Executive', 'BranchManager', 'Operations', 'Procurement', 'editor', 'supplier', 'customer'];
        $systemroles = ['CoreSupport', 'siteadmin'];
        // ============ seed system roles =============
        foreach ($systemroles as $role) {
            Role::updateOrCreate(['name' => $role], []);
        }
        // ================ seed business roles ===============
        foreach ($roles as $roleName) {
            Role::updateOrCreate(
                [
                    'name' => $roleName,
                    'business_id' => $business->id,
                ],
                []
            );
            $this->command->info('✅ Seeded '.$roleName.' Successfully!');
        }
    }
}
