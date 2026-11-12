<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VenteController extends Controller
{
    public function show(Commande $commande): JsonResponse
    {
        return response()->json($commande->load('lignes'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'produit_id' => ['required', 'integer', 'exists:produit,id_p'],
            'quantite' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $commande = DB::transaction(function () use ($validated): Commande {
            $produit = Produit::query()
                ->whereKey($validated['produit_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($produit->stock_p < $validated['quantite']) {
                throw ValidationException::withMessages([
                    'quantite' => 'Le stock disponible est insuffisant.',
                ]);
            }

            $prix = (float) $produit->prix_final;
            $quantite = (int) $validated['quantite'];
            $total = round($prix * $quantite, 2);

            $produit->decrement('stock_p', $quantite);

            $commande = Commande::create([
                'nom_client' => $validated['nom'],
                'email_client' => $validated['email'],
                'statut' => 'en_attente',
                'total_ht' => $total,
            ]);

            $commande->lignes()->create([
                'produit_id' => $produit->id_p,
                'designation' => $produit->designation_p,
                'quantite' => $quantite,
                'prix_unitaire_ht' => $prix,
                'total_ligne_ht' => $total,
            ]);

            return $commande;
        });

        return response()->json([
            'message' => 'Tunnel de vente créé.',
            'etape_suivante' => 'confirmation',
            'commande' => $commande->load('lignes'),
        ], 201);
    }

    public function confirm(Commande $commande): JsonResponse
    {
        if ($commande->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Cette commande ne peut plus être confirmée.',
            ], 422);
        }

        $commande->update(['statut' => 'confirmee']);

        return response()->json([
            'message' => 'Commande fictive confirmée.',
            'commande' => $commande->load('lignes'),
        ]);
    }

    public function cancel(Commande $commande): JsonResponse
    {
        if ($commande->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Cette commande ne peut plus être annulée.',
            ], 422);
        }

        DB::transaction(function () use ($commande): void {
            foreach ($commande->lignes as $ligne) {
                Produit::query()
                    ->whereKey($ligne->produit_id)
                    ->lockForUpdate()
                    ->increment('stock_p', $ligne->quantite);
            }

            $commande->update(['statut' => 'annulee']);
        });

        return response()->json([
            'message' => 'Commande fictive annulée.',
            'commande' => $commande->fresh()->load('lignes'),
        ]);
    }
}
