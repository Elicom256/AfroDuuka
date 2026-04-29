<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BusinessTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //seed a business
        $business_category_id = BusinessCategory::where("name", "electronics")->value("id");
       $result = Business::updateOrInsert(
            ["email" => "testbusinessone@gmail.com"],
            [
            "business_category_id" => $business_category_id,
            "name" => "Test Whole Sallers",
            "phone" => "+256781234567",
        ]);
        if($result){
            $this->command->info("✅ Seeded Business Successfully!");
        }else{
           $this->command->warn('⚠️ Business insert/update may have failed.');
        }
    }
}
