<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PuzzleController;
use App\Http\Controllers\Api\PlaySessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Puzzle App API Routes
|--------------------------------------------------------------------------
| Add this file's contents into your Laravel app's routes/api.php
| (these are automatically prefixed with /api and use the 'api' middleware
| group, per Laravel's default RouteServiceProvider / bootstrap/app.php).
|
| Auth: token-based via Sanctum (Authorization: Bearer <token>), no cookies
| or CSRF needed. See EnsureIsAdmin middleware — it must be registered as
| the 'admin' alias (see README for Laravel 10 vs 11 registration).
*/

// --- Public ---
Route::post('/login', [AuthController::class, 'login']);

Route::get('/puzzles', [PuzzleController::class, 'index']);
Route::get('/puzzles/{puzzle}', [PuzzleController::class, 'show']);

Route::post('/puzzles/{puzzle}/sessions', [PlaySessionController::class, 'store']);
Route::post('/sessions/join', [PlaySessionController::class, 'join']);
Route::get('/sessions/{session}', [PlaySessionController::class, 'show']);
Route::patch('/sessions/{session}/cell', [PlaySessionController::class, 'updateCell']);
Route::post('/sessions/{session}/check', [PlaySessionController::class, 'check']);

// --- Authenticated (any logged-in user) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// --- Admin-only: creating, editing, deleting puzzles & their questions ---
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/puzzles', [PuzzleController::class, 'store']);
    Route::get('/puzzles/{puzzle}/edit', [PuzzleController::class, 'edit']);
    Route::put('/puzzles/{puzzle}', [PuzzleController::class, 'update']);
    Route::delete('/puzzles/{puzzle}', [PuzzleController::class, 'destroy']);
});
