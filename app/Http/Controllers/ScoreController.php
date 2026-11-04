<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Score;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function index(Request $request, string $slug)
    {
        $game = Game::where('slug', $slug)->firstOrFail();
        $scores = Score::with('user')->whereHas('gameVersion', function ($query) use ($game) {
            $query->where('game_id', $game->id);
        })
            ->orderByDesc('score')
            ->orderByDesc('created_at')
            ->get()->unique('user_id')->values();

        $content = $scores->map(function ($score) {
            return [
                'username' => $score->user->username,
                'score' => $score->score,
                'timestamp' => $score->created_at->toISOString(),
            ];
        });
        return response()->json([
            'scores' => $content,
        ]);
    }
    public function store(Request $request, string $slug)
    {
        $validated = $request->validate([
            'score' => 'required|numeric|min:0',
        ]);

        $game = Game::where('slug', $slug)->firstOrFail();

        $latestVersion = $game->versions()->orderByDesc('version')->first();

        Score::create([
            'user_id' => $request->user()->id,
            'game_version_id' => $latestVersion->id,
            'score' => $validated['score'],
        ]);
        return response()->json([
            'status' => 'success',
        ], 201);
    }
}
