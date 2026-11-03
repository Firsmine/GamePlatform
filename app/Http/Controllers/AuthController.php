<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function signUp(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|min:4|max:60|unique:administrators,username',
            'password' => 'required|string|min:6|max:20'
        ]);
        $admin = Administrator::create($data);
        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token
        ], 200);
    }
    public function signIn(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:4|max:60|exists:administrators,username',
            'password' => 'required|string|min:6|max:20'
        ]);
        $admin = Administrator::where('username', $request->username)->first();
        $admin->tokens()->delete();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                "status" => "invalid",
                "message" => "Wrong username or password"
            ], 401);
        }
        $token = $admin->createToken('auth_token')->plainTextToken;
        return response()->json([
            'status' => 'success',
            'token' => $token
        ], 200);
    }
    public function signOut(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => 'success'
        ]);
    }
}
