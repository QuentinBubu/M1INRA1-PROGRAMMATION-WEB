<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProduitController extends Controller
{
    public function index(): View
    {
        return view('produits.index', [
            'produits' => Produit::query()->orderByDesc('id_p')->get(),
        ]);
    }

    public function edit(int $id_p): View
    {
        return view('produits.edit', [
            'produit' => Produit::query()->findOrFail($id_p),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(Produit::rules());
        $produit = Produit::create($validated);

        return response()->json($produit, 201);
    }

    public function update(Request $request, int $id_p): RedirectResponse
    {
        $produit = Produit::query()->findOrFail($id_p);
        $produit->update($request->validate(Produit::rules()));

        return redirect('/produits')->with('success', 'Produit modifié avec succès.');
    }

    public function destroy(int $id_p): RedirectResponse
    {
        Produit::query()->findOrFail($id_p)->delete();

        return redirect('/produits')->with('success', 'Produit supprimé avec succès.');
    }
}
