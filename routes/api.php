<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\VenteController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/produits-form', [ProduitController::class, 'store']);

Route::post('/vente', [VenteController::class, 'store']);
Route::get('/vente/{commande}', [VenteController::class, 'show']);
Route::put('/vente/{commande}/confirmer', [VenteController::class, 'confirm']);
Route::delete('/vente/{commande}', [VenteController::class, 'cancel']);
