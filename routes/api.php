<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AgentTrackingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Check if email exists in users table
Route::post('/check-email', function (Request $request) {
    $request->validate([
        'email' => 'required|email'
    ]);

    $exists = User::where('email', $request->email)->exists();

    return response()->json([
        'exists' => $exists
    ]);
});

// Admin API routes
Route::prefix('admin')->group(function () {
    Route::get('/agents/query', [AgentController::class, 'query']);
    Route::get('/agents/{id}', [AgentController::class, 'show']);
});

// Public Agent Tracking API routes
Route::prefix('agents')->group(function () {
    Route::post('/track/referral', [AgentTrackingController::class, 'trackReferral']);
    Route::post('/track/sale', [AgentTrackingController::class, 'trackSale']);
    Route::get('/track/code/{code}', [AgentTrackingController::class, 'getReferralCodeInfo']);
});
