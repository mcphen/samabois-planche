<?php

use App\Http\Controllers\Api\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Routes de synchronisation (à protéger par un token dans le .env en production)
Route::prefix('sync')->group(function () {
    Route::post('/{tableName}', [SyncController::class, 'push']);
    Route::get('/{tableName}', [SyncController::class, 'pull']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
