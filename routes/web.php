<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProduitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('login');
});

Route::get('/addProduct', function () {
    return view('addProduct');
});

Route::get('/register', function () {
    return view('register');
});

Route::get('/produits', [ProduitController::class, 'index']);
Route::get('/produits/{id_p}/edit', [ProduitController::class, 'edit']);
Route::put('/produits/{id_p}', [ProduitController::class, 'update']);
Route::delete('/produits/{id_p}', [ProduitController::class, 'destroy']);

Route::prefix('api')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/produits-form', [ProduitController::class, 'store']);
});
