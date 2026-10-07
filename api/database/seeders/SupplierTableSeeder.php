<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupplierTableSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Prime Wholesale Ltd',
                'email' => 'primewholesale@example.com',
                'phone' => '+256700111222',
                'address' => 'Kampala Road, Kampala',
            ],
            [
                'name' => 'East Africa Supplies',
                'email' => 'easupplies@example.com',
                'phone' => '+256701333444',
                'address' => 'Ntinda, Kampala',
            ],
            [
                'name' => 'Global Traders Uganda',
                'email' => 'globaltraders@example.com',
                'phone' => '+256702555666',
                'address' => 'Nakasero, Kampala',
            ],
            [
                'name' => 'City Stock Providers',
                'email' => 'citystock@example.com',
                'phone' => '+256703777888',
                'address' => 'Wandegeya, Kampala',
            ],
            [
                'name' => 'Fresh Farm Distributors',
                'email' => 'freshfarm@example.com',
                'phone' => '+256704999000',
                'address' => 'Nakawa, Kampala',
            ],
        ];

        $businessId = Business::where('email', 'testbusinessone@gmail.com')->value('id');

        $businessBranch = BusinessBranch::where('name', 'Main Branch')
            ->where('business_id', $businessId)->value('id');
        $role = Role::where('name', 'supplier')
            ->where('business_id', $businessId)->value('id');
        foreach ($suppliers as $supplierData) {
            $supplierCount = Supplier::with('user', function ($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })->count();
            $supplierCode = 'SUP-'.str_pad($supplierCount + 1, 5, '0', STR_PAD_LEFT);
            $nin = strtoupper('CM'.rand(10, 99).rand(10000000, 99999999).chr(rand(65, 90)).chr(rand(65, 90)));
            // 1. Create User first
            $user = User::firstOrNew(['email' => $supplierData['email']]);
            $user->firstname = explode(' ', $supplierData['name'])[0];
            $user->lastname = explode(' ', $supplierData['name'], 2)[1] ?? '';
            $user->email = $supplierData['email'];
            $user->phone = $supplierData['phone'];
            $user->address = $supplierData['address'];
            $user->business_id = $businessId;
            $user->business_branch_id = $businessBranch;
            $user->role_id = $role;
            $user->username = strtoupper(explode('@', $supplierData['email'])[0]);
            $user->status = 'active';
            if (! $user->exists) {
                $user->password = Hash::make('password');
                $user->nin = $nin;
            }
            $user->save();

            // 2. Create Supplier profile
            $supplier = Supplier::firstOrNew(['user_id' => $user->id]);
            $supplier->company_name = $supplierData['name'];
            $supplier->status = 'active';
            if (! $supplier->exists) {
                $supplier->supplier_code = $supplierCode;
            }
            $supplier->save();
        }

        $this->command->info('✅ Seeded '.count($suppliers).' Suppliers Successfully!');
    }
}
