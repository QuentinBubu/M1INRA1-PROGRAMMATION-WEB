<?php

use App\Http\Controllers\ProduitController;
use App\Http\Controllers\UserController;
use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/boutique');
    }

    return view('login');
})->name('login');

Route::get('/addProduct', function () {
    return view('addProduct');
})->middleware(['auth', 'admin']);

Route::get('/boutique', function () {
    $produits = Produit::query()
        ->where('stock_p', '>', 0)
        ->orderBy('designation_p')
        ->get();

    return view('boutique', [
        'produits' => $produits,
        'produitsData' => $produits->map(function (Produit $produit): array {
            return [
                'id' => $produit->id_p,
                'name' => $produit->designation_p,
                'type' => $produit->type_p,
                'price' => (float) $produit->prix_final,
                'stock' => (int) $produit->stock_p,
            ];
        })->values()->all(),
    ]);
})->middleware('auth');

Route::get('/vente', function () {
    return redirect('/boutique');
})->middleware('auth');

Route::get('/mes-commandes', function () {
    return view('commandes', [
        'commandes' => Commande::query()
            ->where('user_id', auth()->id())
            ->with('lignes')
            ->latest()
            ->get(),
    ]);
})->middleware('auth');

Route::get('/register', function () {
    return view('register');
});

Route::middleware('auth')->group(function () {
    Route::middleware('admin')->group(function () {
        Route::get('/produits', [ProduitController::class, 'index']);
        Route::get('/produits/{id_p}/edit', [ProduitController::class, 'edit']);
        Route::put('/produits/{id_p}', [ProduitController::class, 'update']);
        Route::delete('/produits/{id_p}', [ProduitController::class, 'destroy']);
    });

    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user_id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user_id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user_id}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});