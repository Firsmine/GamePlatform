<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()->get(['username', 'last_login_at', 'created_at', 'updated_at']);

        $content = $users->map(function ($user) {
            return [
                'username' => $user->username,
                'last_login_at' => $user->last_login_at
                    ? $user->last_login_at->format('Y-m-d H:i:s')
                    : '',
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ];
        });
        return response()->json([
            'totalElements' => $users->count(),
            'content' => $content,
        ]);
    }
    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'username' => 'required|string|min:4|max:60|unique:users,username',
            'password' => 'required|string|min:6|max:20'
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 'invalid',
                'message' => $data->errors()->toArray()
            ], 422);
        }
        if (!$this->isAdministrator($request)) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You are not the administrator',
            ], 403);
        }
        $user = User::create($data->validated());

        return response()->json([
            'status' => 'success',
            'username' => $user->username
        ], 201);
    }
    public function show(Request $request, string $username)
    {
        //
    }
    public function update(Request $request)
    {
        //
    }
    public function destroy(Request $request)
    {
        //
    }

    private function isAdministrator(Request $request): bool
    {
        return $request->user() instanceof Administrator;
    }
}
