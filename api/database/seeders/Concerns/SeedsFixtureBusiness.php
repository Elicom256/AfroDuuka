<?php

namespace Database\Seeders\Concerns;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessCategory;
use App\Models\Country;

trait SeedsFixtureBusiness
{
    /**
     * Resolve the fixture business, creating a minimal one if it does not
     * exist yet. Allows individual seeders to run in any order / standalone.
     */
    protected function fixtureBusiness(): Business
    {
        $business = Business::where("email", "testbusinessone@gmail.com")->first();

        if ($business) {
            return $business;
        }

        $businessCategoryId = BusinessCategory::where("name", "electronics")
            ->value("id")
            ?? BusinessCategory::updateOrCreate(
                ["name" => "electronics"],
                ["description" => "businesses selling electronics and gadgets", "status" => 1]
            )->id;

        $countryId = Country::where("iso_alpha2", "UG")
            ->value("id")
            ?? Country::updateOrCreate(
                ["iso_alpha2" => "UG"],
                [
                    'name' => 'Uganda',
                    'flag_emoji' => '🇺🇬',
                    'currency_code' => 'UGX',
                    'currency_symbol' => 'Shs',
                    'default_vat_rate' => 18.00,
                    'region' => 'East Africa',
                    'calling_code' => '+256',
                ]
            )->id;

        return Business::create([
            "email" => "testbusinessone@gmail.com",
            "name" => "Test Whole Sallers",
            "phone" => "+256781234567",
            "address" => "Kabale-Kisoro Road",
            "business_category_id" => $businessCategoryId,
            "country_id" => $countryId,
        ]);
    }

    /**
     * Resolve the fixture main branch for the given business, creating it if
     * absent.
     */
    protected function fixtureMainBranch(Business $business): BusinessBranch
    {
        return BusinessBranch::updateOrCreate(
            ["business_id" => $business->id, "name" => "Main Branch"],
            ["address" => "Kampala Road, Kampala", "phone" => "0780000000"]
        );
    }
}