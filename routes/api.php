<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CampaignController;
use App\Http\Controllers\API\DonationController;

// Public routes
Route::get('/campaigns', [CampaignController::class, 'index']);
Route::get('/campaigns/{campaign}', [CampaignController::class, 'show']);

// Authenticated routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update']);
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy']);

    Route::post('/donations', [DonationController::class, 'store']);
    Route::get('/donations', [DonationController::class, 'index']); // User's donations
    Route::get('/donations/{donation}', [DonationController::class, 'show']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
