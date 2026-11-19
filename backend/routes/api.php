<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\PlaySessionController;
use App\Http\Controllers\Api\PuzzleController;
use Illuminate\Support\Facades\Route;

// --- Public: a puzzle is only reachable through its private share code (the QR) ---
Route::post('/login', [AuthController::class, 'login']);

Route::get('/puzzles/{puzzle:share_code}', [PuzzleController::class, 'show']);
Route::get('/puzzles/{puzzle:share_code}/leaderboard', [LeaderboardController::class, 'show']);
Route::post('/puzzles/{puzzle:share_code}/sessions', [PlaySessionController::class, 'store']);

Route::post('/sessions/join', [PlaySessionController::class, 'join']);
Route::get('/sessions/{session}', [PlaySessionController::class, 'show']);
Route::patch('/sessions/{session}/cell', [PlaySessionController::class, 'updateCell']);
Route::post('/sessions/{session}/check', [PlaySessionController::class, 'check']);

// --- Authenticated (any logged-in user) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

// --- Host only: see all puzzles, create, edit, open/close, new round, live dashboard, delete ---
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/puzzles', [PuzzleController::class, 'index']);
    Route::post('/puzzles', [PuzzleController::class, 'store']);
    Route::get('/puzzles/{puzzle}/edit', [PuzzleController::class, 'edit']);
    Route::put('/puzzles/{puzzle}', [PuzzleController::class, 'update']);
    Route::patch('/puzzles/{puzzle}/publish', [PuzzleController::class, 'setPublished']);
    Route::patch('/puzzles/{puzzle:share_code}/new-round', [LeaderboardController::class, 'newRound']);
    Route::delete('/puzzles/{puzzle}', [PuzzleController::class, 'destroy']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
});
