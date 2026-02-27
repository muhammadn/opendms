<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StatusController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/status', [StatusController::class, 'index']);
    Route::post('/status/send', [StatusController::class, 'message']);
    Route::post('/status/broadcast', [StatusController::class, 'broadcast']);
    Route::get('/status/history', [StatusController::class, 'history']);
});

Route::get('/dashboard/json', [DashboardController::class, 'json'])
	->withoutMiddleware([EnsureTokenIsValid::class]);

Route::get('/dashboard/timeline', [DashboardController::class, 'timeline'])
        ->withoutMiddleware([EnsureTokenIsValid::class]);

require __DIR__.'/settings.php';
