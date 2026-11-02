<?php

use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameVersionController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('signUp', [AuthController::class, 'signUp']);
        Route::post('signIn', [AuthController::class, 'signIn']);
        Route::post('signOut', [AuthController::class, 'signOut'])->middleware('auth:sanctum');
    });

    Route::middleware('auth::sanctum')->group(function () {
        // administrator
        Route::get('admins', [AdministratorController::class, 'index']);

        // user
        Route::apiResource('users', UserController::class)
            ->except(['create', 'edit']);
        Route::get('/users/{username}', [UserController::class, 'show']);

        // games
        Route::get('/games', [GameController::class, 'index']);
        Route::post('/games', [GameController::class, 'store']);
        Route::get('/games/{slug}', [GameController::class, 'show']);
        Route::put('/games/{slug}', [GameController::class, 'update']);
        Route::delete('/games/{slug}', [GameController::class, 'destroy']);

        // game versions
        Route::post('/games/{slug}/upload', [GameVersionController::class, 'store']);

        // scores
        Route::get('/games/{slug}/scores', [ScoreController::class, 'index']);
        Route::post('/games/{slug}/scores', [ScoreController::class, 'store']);
    });
});
