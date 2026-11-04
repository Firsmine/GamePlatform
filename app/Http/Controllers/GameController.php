<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Str;

class GameController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->query('page', 0);
        $size = $request->query('size', 10);
        $sortBy = $request->query('sortBy', 'title');
        $sortDir = $request->query('sortDir', 'asc');

        $data = Validator::make($request->query(), [
            'page' => 'integer|min:0',
            'size' => 'integer|min:1',
            'sortBy' => 'in:title,popular,uploaddate',
            'sortDir' => 'in:asc,desc',
        ]);
        if ($data->fails()) {
            return response()->json([
                'status' => 'invalid',
                'errors' => $data->errors()
            ], 422);
        }
        $games = Game::whereHas('versions')
            ->with(['author', 'latestVersion'])
            ->withCount('scores');

        if ($sortBy === 'title') {
            $games->orderBy('title', $sortDir);
        } elseif ($sortBy === 'popular') {
            $games->orderBy('scores_count', $sortDir);
        } else {
            $games->orderBy(
                Game::select('created_at')
                    ->from('game_versions')
                    ->whereColumn('game_id', 'games.id')
                    ->latest('created_at')
                    ->limit(1),
                $sortDir
            );
        }
        $total = $games->count();
        $result = $games->skip($page * $size)
            ->take($size)
            ->get();

        $content = $result->map(function ($game) {
            $version = $game->latestVersion;
            return [
                'slug' => $game->slug,
                'title' => $game->title,
                'description' => $game->description,
                'thumbnail' => $version
                    ? "/games/{$game->slug}/{$version->version}/thumbnail.png"
                    : null,
                'uploadTimestamp' => $version?->created_at,
                'author' => $game->author->username,
                'scoreCount' => $game->scores_count,
            ];
        });
        return response()->json([
            'page' => (int) $page,
            'size' => $content->count(),
            'totalElements' => $total,
            'content' => $content,
        ]);
    }
    public function store(Request $request, string $slug)
    {
        $data = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:60',
            'description' => 'required|string|min:0|max:200',
        ]);
        if ($data->fails()) {
            return response()->json([
                'status' => 'invalid',
                'errors' => $data->errors()
            ], 422);
        }

        $slug = Str::slug($request->title);

        if (Game::where('slug', $slug)->exists()) {
            return response()->json([
                'status' => 'invalid',
                'slug' => 'Game title already exists'
            ], 400);
        }
        Game::create([
            'title' => $request->title,
            'slug' => $slug,
            'description' => $request->description,
            'created_by' => $request->user()->id,
        ]);
        return response()->json([
            'status' => 'success',
            'slug' => $slug,
        ], 201);
    }
    public function show(Request $request, string $slug)
    {
        $game = Game::where('slug', $slug)
            ->with(['author', 'latestVersion'])
            ->withCount('scores')
            ->firstOrFail();
        $version = $game->latestVersion;

        return response()->json([
            'slug' => $game->slug,
            'title' => $game->title,
            'description' => $game->description,
            'thumbnail' => $version
                ? "/games/{$game->slug}/{$version->version}/thumbnail.png"
                : null,
            'uploadTimestamp' => $version?->created_at,
            'author' => $game->author->username,
            'scoreCount' => $game->scores_count,
            'gamePath' => $version
                ? "/games/{$game->slug}/{$version->version}/"
                : null,
        ]);
    }
    public function update(Request $request, string $slug)
    {
        $game = Game::where('slug', $slug)->firstOrFail();
        if ($game->created_by !== $request->user()->id) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You are not the game author'
            ], 403);
        }
        $data = Validator::make($request->all(), [
            'title' => 'required|string|min:3|max:60',
            'description' => 'required|string|min:0|max:200',
        ]);
        if ($data->fails()) {
            return response()->json([
                'status' => 'invalid',
                'errors' => $data->errors()
            ], 422);
        }
        $game->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);
        return response()->json([
            'status' => 'success'
        ]);
    }
    public function destroy(Request $request, string $slug)
    {
        $game = Game::where('slug', $slug)->firstOrFail();
        if ($game->created_by !== $request->user()->id) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You are not the game author'
            ], 403);
        }
        foreach ($game->versions as $version) {
            Storage::disk('public')->deleteDirectory(
                $version->storage_path
            );
            $version->scores()->delete();
            $version->forceDelete();
        }
        $game->forceDelete();
        return response()->noContent();
    }
}
