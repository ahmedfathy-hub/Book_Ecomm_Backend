<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    ////////////////////////////////////////////////Register Function /////////////////////////////////////
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'sometimes|exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id ?? 3            // Default role
        ]);


        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('role')
        ], 201);
    
    }

////////////////////////////////////////////////Login Function /////////////////////////////////////
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($credentials)) 
            {
                return response()->json(
                    [
                    'message' => 'Invalid login credentials'
                    ], 401);
            }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('role', 'profile')
        ]);
    }

////////////////////////////////////////////////Logout Function /////////////////////////////////////
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Successfully logged out']);
    }

////////////////////////////////////////////////Profile Function /////////////////////////////////////

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'sometimes|string|max:20|unique:users,phone,'.$user->id,
            'address' => 'sometimes|string|max:500',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'country' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:20',
            'payment_details' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Update user fields
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        $user->save();

        $profileData = $request->only(['address', 'city', 'state', 'country', 'postal_code', 'payment_details']);

        if (!empty($profileData)) 
            {
                if ($user->profile) 
                {
                    $user->profile()->update($profileData);
                } else {
                    $user->profile()->create($profileData);
                }
            }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->load('role', 'profile')
        ]);

    
    }

    public function profile(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('role', 'profile')
        ]);
    }

}
