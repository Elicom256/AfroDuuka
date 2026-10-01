<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessCategoryRequest;
use App\Http\Requests\UpdateBusinessCategoryRequest;
use App\Models\BusinessCategory;

/**
 * Read-only catalogue of business categories.
 *
 * The signup form has to show this dropdown before an account exists, but the
 * dashboard route that exposes it sits behind auth:sanctum and the role middleware.
 * A business cannot be created without a category (businesses.business_category_id is
 * NOT NULL), so the public /api/business-categories route is what lets self-serve
 * onboarding collect the field at all.
 *
 * BusinessCategory is a global reference table — it has no business_id and carries no
 * tenant scope — so exposing it publicly discloses nothing about any tenant.
 */
class BusinessCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return BusinessCategory::all();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBusinessCategoryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(BusinessCategory $businessCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BusinessCategory $businessCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBusinessCategoryRequest $request, BusinessCategory $businessCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessCategory $businessCategory)
    {
        //
    }
}
