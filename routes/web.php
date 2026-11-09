<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\UserController;
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

Route::prefix('admin')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user_id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user_id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user_id}', [UserController::class, 'destroy'])->name('users.destroy');
});