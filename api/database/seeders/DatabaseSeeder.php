<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(CountrySeeder::class);
        $this->call(BusinessCategorySeeder::class);
        $this->call(BusinessTableSeeder::class);
        $this->call(RoleTableSeeder::class);
        $this->call(BusinessBranchSeeder::class);
        $this->call(UserTableSeeder::class);
        $this->call(CategoryTableSeeder::class);
        $this->call(ProductsTableSeeder::class);
        $this->call(SupplierTableSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(BusinessBranchProductSeeder::class);
        $this->call(BusinessTaxesSeeder::class);
        $this->call(AttendanceSeeder::class);
        $this->call(BusinessTaxPaymentsSeeder::class);
        $this->call(EmployeeRemunerationSeeder::class);
        $this->call(PricingSeeder::class);
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
