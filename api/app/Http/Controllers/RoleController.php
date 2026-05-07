<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(){
        $roles = Role::orderBy('name', 'desc')->get();
        return response()->json($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        $validated = $request->validated();
        $role = Role::create($validated);
        return response()->json([
            "message" => "Role created successfully!",
            "role" => $role
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
       $role = Role::where("id", $role->id)->get();
       return response()->json(["message" => "Fetched Role with id $role->id", "role" => $role]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $validated = $request->validated();
        $role = $role->update($validated);
        return response()->json([
            "message" => "Role created successfully!",
            "role" => $role
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(["message" => "Role deleted!"]);
    }
}
