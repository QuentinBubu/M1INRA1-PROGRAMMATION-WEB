<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
});

Route::get('/addProduct', function () {
    return view('addProduct');
});

Route::prefix('api')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});
