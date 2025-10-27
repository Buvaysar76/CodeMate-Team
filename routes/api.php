<?php

use App\Http\Controllers\BalanceController;
use App\OpenApi\Controllers\SwaggerController;
use Illuminate\Support\Facades\Route;

Route::get('/openapi.json', [SwaggerController::class, 'json'])->name('openapi.json');

Route::post('/deposit', [BalanceController::class, 'deposit']);
Route::post('/withdraw', [BalanceController::class, 'withdraw']);
Route::post('/transfer', [BalanceController::class, 'transfer']);
Route::get('/balance/{user}', [BalanceController::class, 'balance']);
