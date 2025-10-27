<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/docs', fn() => view('swagger/ui', [
    'jsonUrl' => route('openapi.json'),
]));
