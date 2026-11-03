<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use Illuminate\Http\Request;

class AdministratorController extends Controller
{
    public function index(Request $request)
    {
        $admins = Administrator::query()->get(['username', 'last_login_at', 'created_at', 'updated_at']);

        $content = $admins->map(function ($admin) {
            return [
                'username' => $admin->username,
                'last_login_at' => $admin->last_login_at
                    ? $admin->last_login_at->format('Y-m-d H:i:s')
                    : '',
                'created_at' => $admin->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $admin->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'totalElements' => $admins->count(),
            'content' => $content,
        ], 200);
    }
}
