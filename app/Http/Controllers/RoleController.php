<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $role = Role::create($request->only('name'));

        return response()->json($role, 201);
        
    }

    
    public function show(string $id)
    {
        $role = Role::findOrFail($id);
        return response()->json($role);
    }

    
    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name,'.$role->id.'|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $role->update($request->only('name'));

        return response()->json($role);
    }

    
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        
        // Prevent deletion if role has users
        if ($role->users()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete role with assigned users'
            ], 422);
        }

        $role->delete();
        return response()->json(['message' => 'Role deleted successfully']);
    }
}
