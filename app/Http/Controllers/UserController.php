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
        if (!$this->isAdministrator($request)) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You are not the administrator',
            ], 403);
        }
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
        ], 200);
    }
    public function store(Request $request)
    {
        if (!$this->isAdministrator($request)) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You are not the administrator',
            ], 403);
        }
        $data = Validator::make($request->all(), [
            'username' => 'required|string|min:4|max:60|unique:users,username',
            'password' => 'required|string|min:6|max:20'
        ]);

        if ($data->fails()) {
            return response()->json([
                'status' => 'invalid',
                'message' => $data->errors()->toArray()
            ], 400);
        }

        $user = User::create($data->validated());

        return response()->json([
            'status' => 'success',
            'username' => $user->username
        ], 201);
    }
    public function show(Request $request, string $username)
    {
        $user = User::where('username', $username)->firstOrFail();
        if (!$user) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'User Not found'
            ], 404);
        }
        $isCurrentUser = $request->user() && $request->user()->username === $user->username;

        $gamesQuery = $user->games();
        if (!$isCurrentUser) {
            $gamesQuery->whereHas('versions');
        }
        $authoredGames = $gamesQuery->get(['slug', 'title', 'description']);

        $highscores = $user->scores()
            ->with('game:id,slug,title,description')
            ->select('game_id', \DB::raw('MAX(score) as score'), \DB::raw('MAX(created_at) as timestamp'))
            ->groupBy('game_id')
            ->get()
            ->map(function ($score) {
                return [
                    'game' => [
                        'slug' => $score->game->slug,
                        'title' => $score->game->title,
                        'description' => $score->game->description,
                    ],
                    'score' => (int) $score->score,
                    'timestamp' => $score->timestamp ? $score->timestamp->toISOString() : null
                ];
            });
        return response()->json([
            'username' => $user->username,
            'registeredTimestamp' => $user->created_at->toISOString(),
            'authoredGames' => $authoredGames,
            'highscores' => $highscores
        ], 200);
    }
    public function update(Request $request, $id)
    {
        if (!$this->isAdministrator($request)) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You are not the administrator'
            ], 403);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'User Not found'
            ], 404);
        }

        $data = Validator::make($request->all(), [
            'username' => 'required|string|min:4|max:60|unique:users,username,' . $id,
            'password' => 'required|string|min:5|max:20',
        ]);
        if ($data->fails()) {
            return response()->json([
                'status' => 'invalid',
                'message' => $data->errors()->toArray()
            ], 400);
        }

        if (User::where('username', $request->username)->where('id', '!=', $id)->exists()) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Username already exists'
            ], 400);
        }
        $user->update($data->validated());
        return response()->json([
            'status' => 'success',
            'username' => $user->username,
        ], 201);
    }
    public function destroy(Request $request, $id)
    {
        if (!$this->isAdministrator($request)) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You are not the administrator',
            ], 403);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'status' => 'not-found',
                'message' => 'User Not found',
            ], 404);
        }
        $user->delete();
        return response()->noContent();
    }

    private function isAdministrator(Request $request): bool
    {
        return $request->user() instanceof Administrator;
    }
}
