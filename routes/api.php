<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ScenarioController;
use App\Http\Controllers\Api\ScenarioGroupController;
use App\Http\Controllers\Api\DrillController;
use App\Http\Controllers\Api\RangeConstructionController;
use App\Http\Controllers\Api\StatsController;

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Scenarios (includes grid)
    Route::get('/scenarios/name-suggestions', [ScenarioController::class, 'nameSuggestions']);
    Route::apiResource('scenarios', ScenarioController::class);
    Route::get('/scenarios/{scenario}/border-hands', [ScenarioController::class, 'borderHands']);

    // Groups
    Route::apiResource('groups', ScenarioGroupController::class);
    Route::post('/groups/{group}/scenarios', [ScenarioGroupController::class, 'syncScenarios']);

    // Drills
    Route::prefix('drills')->group(function () {
        Route::get('/suggestions', [DrillController::class, 'suggestions']);
        Route::post('/start', [DrillController::class, 'start']);
        Route::post('/next-hand', [DrillController::class, 'nextHand']);
        Route::post('/answer', [DrillController::class, 'answer']);
        Route::post('/end', [DrillController::class, 'end']);
    });

    // Stats
    Route::prefix('stats')->group(function () {
        Route::get('/overview', [StatsController::class, 'overview']);
        Route::get('/scenarios', [StatsController::class, 'scenarioStats']);
        Route::get('/scenarios/{scenario}', [StatsController::class, 'scenarioDetail']);
        Route::get('/groups', [StatsController::class, 'groupStats']);
        Route::get('/problem-hands', [StatsController::class, 'problemHands']);
    });

    // Range Construction Drill
    Route::prefix('range-drill')->group(function () {
        Route::post('/start', [RangeConstructionController::class, 'start']);
        Route::post('/submit', [RangeConstructionController::class, 'submit']);
        Route::get('/stats', [RangeConstructionController::class, 'stats']);
    });
});

// i18n - public route
Route::get('/translations/{locale}', function (string $locale) {
    $path = lang_path("{$locale}.json");
    if (!file_exists($path)) {
        return response()->json(['error' => 'Locale not found'], 404);
    }
    return response()->json(json_decode(file_get_contents($path), true));
});
