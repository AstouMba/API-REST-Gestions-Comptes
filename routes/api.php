<?php

use App\Http\Controllers\CompteController;
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

Route::middleware('auth:sanctum')->group(function () {
    /**
        * @group Comptes
        */
    Route::prefix('v1/' . config('api.name'))->group(function () {
        Route::get('comptes', [CompteController::class, 'index'])->name('comptes.index');
        Route::get('comptes/archives', [CompteController::class, 'getArchivedComptes'])->name('comptes.archives');
    });
});
