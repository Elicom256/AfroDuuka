<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Category;
use App\Models\Country;
use App\Services\CoreBusinessSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BusinessTableSeeder extends Seeder
{
    protected $coreBusinessSettings;

   public function __construct(CoreBusinessSettings $coreBusinessSettings)
   {
    $this->coreBusinessSettings = $coreBusinessSettings;
   }
    /**
     * Run the database seeds.
     */
    public function run()
    {
    
        //seed a business
        $business_category_id = BusinessCategory::where("name", "electronics")->value("id");
        $country_id = Country::where("iso_alpha2", "UG")->value("id");
       $result = Business::updateOrCreate(
            ["email" => "testbusinessone@gmail.com"],
            [
            "business_category_id" => $business_category_id,
            "country_id" => $country_id,
            "name" => "Test Whole Sallers",
            "phone" => "+256781234567",
            "address" => "Kabale-Kisoro Road"

        ]);
        $settings = $this->coreBusinessSettings->coreSettings($result->id);
        if($result){
            $this->command->info("✅ Seeded Business Successfully! with $settings");
        }else{
           $this->command->warn('⚠️ Business insert/update may have failed.');
        }
    }
}
