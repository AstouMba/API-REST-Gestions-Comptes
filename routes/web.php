<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwaggerController;;

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
Route::get('/', fn () => view('welcome'));

// Swagger UI
Route::get('/api/documentation', [SwaggerController::class, 'api'])
    ->name('l5-swagger.api');

// Fichier JSON Swagger
Route::get('/docs/json', [SwaggerController::class, 'docs'])
    ->name('l5-swagger.docs');
