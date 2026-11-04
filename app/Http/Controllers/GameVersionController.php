<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use ZipArchive;

class GameVersionController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $game = Game::where('slug', $slug)->first();
        if (!$game) {
            return response('Game not found', 404)
                ->header('Content-Type', 'text/plain');
        }

        $data = Validator::make($request->all(), [
            'token' => 'required|string',
            'zipfile' => 'required|file|mimes:zip|max:102400',
        ]);
        if ($data->fails()) {
            return response($data->errors()->first(), 422)
                ->header('Content-Type', 'text/plain');
        }
        $accessToken = PersonalAccessToken::findToken(
            $request->input('token')
        );
        if (!$accessToken || !$accessToken->tokenable instanceof User) {
            return response('Invalid token', 401)
                ->header('Content-Type', 'text/plain');
        }
        $user = $accessToken->tokenable;
        if ($game->created_by !== $user->id) {
            return response('You are not the game author', 403)
                ->header('Content-Type', 'text/plain');
        }
        $version = ($game->versions()->max('version') ?? 0) + 1;
        $directory = "games/{$game->id}/{$version}";
        $zip = new ZipArchive();

        if ($zip->open($request->file('zipfile')->getRealPath()) !== true) {
            return response('Invalid ZIP file', 422)
                ->header('Content-Type', 'text/plain');
        }
        Storage::disk('public')->makeDirectory($directory);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileName = $zip->getNameIndex($i);
            if (
                str_contains($fileName, '..') ||
                Str::startsWith($fileName, '/') ||
                str_contains($fileName, "\0")
            ) {
                $zip->close();
                Storage::disk('public')->deleteDirectory($directory);
                return response('Invalid ZIP file path', 422)
                    ->header('Content-Type', 'text/plain');
            }
            if (str_ends_with($fileName, '/')) {
                Storage::disk('public')->makeDirectory(
                    "{$directory}/{$fileName}"
                );
                continue;
            }
            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                continue;
            }
            Storage::disk('public')->put(
                "{$directory}/{$fileName}",
                $contents
            );
        }
        $zip->close();
        $game->versions()->create([
            'version' => $version,
            'storage_path' => $directory,
        ]);
        return response('Game uploaded successfully', 201)
            ->header('Content-Type', 'text/plain');
    }

    // serve game
    public function show(string $slug, int $version)
    {
        $game = Game::where('slug', $slug)->first();
        if (!$game) {
            abort(404);
        }
        $gameVersion = $game->versions()->where('version', $version)->first();
        if (!$gameVersion) {
            abort(404);
        }
        $path = $gameVersion->storage_path;
        $indexFile = Storage::disk('public')->path(
            "{$path}/index.html"
        );
        if (!file_exists($indexFile)) {
            abort(404);
        }
        return response()->file($indexFile);
    }
}
