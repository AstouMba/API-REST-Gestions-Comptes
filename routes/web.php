<?php

use Illuminate\Support\Facades\Route;
use SwaggerLume\Http\Controllers\SwaggerLumeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', [SwaggerLumeController::class, 'docs'])->name('swagger-lume.docs');
Route::get('/api/documentation', [SwaggerLumeController::class, 'api'])->name('swagger-lume.api');
