<?php

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

/**
 * @group Authentication
 */
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * @group Admin
 */
Route::middleware(['admin'])->post('/admin/clients', [App\Http\Controllers\AdminController::class, 'store']);

Route::prefix('v1')->group(function () {
    /**
      * @group Comptes
      */
    Route::get('comptes', [App\Http\Controllers\CompteController::class, 'index']);
    Route::get('comptes/{numero}', [App\Http\Controllers\CompteController::class, 'show']);
    Route::post('comptes', [App\Http\Controllers\CompteController::class, 'store']);
    Route::put('comptes/{numero}', [App\Http\Controllers\CompteController::class, 'update']);
    Route::delete('comptes/{numero}', [App\Http\Controllers\CompteController::class, 'destroy']);
});
