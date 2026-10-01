<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleSearchConsoleController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/google/connect', [
    GoogleSearchConsoleController::class,
    'connect'
])->name('google.connect');

Route::get('/google/callback', [
    GoogleSearchConsoleController::class,
    'callback'
])->name('google.callback');