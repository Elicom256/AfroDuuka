<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCountryRequest;
use App\Http\Requests\UpdateCountryRequest;
use App\Models\Country;
use App\Support\ListLimit;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $countries = ListLimit::apply(
            Country::orderBy('name'),
            request(),
            ListLimit::REFERENCE
        )->map(fn ($country) => $country->only(['id', 'name', 'iso_alpha2', 'flag_emoji', 'currency_code', 'calling_code']));

        return response()->json([
            'message' => 'Countries retrieved successfully',
            'data' => $countries,
            ...ListLimit::truncationNotice(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCountryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCountryRequest $request, Country $country)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        //
    }
}
