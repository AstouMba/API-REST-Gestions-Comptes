<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'logging'])->group(function () {
    
    Route::prefix('v1/' . config('api.name'))->group(function () {
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('auth/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    });

    Route::prefix('v1/' . config('api.name'))
        ->middleware('auth:api')
        ->group(function () {
            
            Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
          
            // Allow both admin and client to list comptes; CompteService will enforce per-user visibility
            Route::get('comptes', [CompteController::class, 'index'])
                ->middleware('role:admin,client')
                ->name('comptes.index');

            // Allow both admin and client to view compte details; CompteService will enforce per-user visibility
            Route::get('comptes/{compteId}', [CompteController::class, 'show'])
                ->middleware('role:admin,client')
                ->name('comptes.show');

            Route::middleware('role:admin')->group(function () {

                Route::post('comptes', [CompteController::class, 'store'])->name('comptes.store');

                Route::patch('comptes/{compteId}', [CompteController::class, 'update'])->name('comptes.update');

                Route::delete('comptes/{compteId}', [CompteController::class, 'destroy'])->name('comptes.destroy');

                Route::post('comptes/{compteId}/bloquer', [CompteController::class, 'bloquer']) ->name('comptes.bloquer');

            });
        });
});