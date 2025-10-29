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

Route::middleware(['api', 'logging'])->group(function () {
    /**
         * @group Comptes
         */
    Route::prefix('v1/' . config('api.name'))->group(function () {
            Route::get('comptes', [CompteController::class, 'index'])->name('comptes.index');
            Route::get('comptes/{compteId}', [CompteController::class, 'show'])->name('comptes.show');
            Route::post('comptes', [CompteController::class, 'store'])->name('comptes.store');
            Route::patch('comptes/{compteId}', [CompteController::class, 'update'])->name('comptes.update');
            Route::delete('comptes/{compteId}', [CompteController::class, 'destroy'])->name('comptes.destroy');
            Route::post('comptes/{compteId}/bloquer', [CompteController::class, 'bloquer'])
                ->name('comptes.bloquer');
            
        });
});

