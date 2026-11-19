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
        $this->authorizeCommande($commande);

        return response()->json($commande->load('lignes'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'numero_carte' => ['required', 'digits:16'],
            'expiration' => ['required', 'regex:/^(0[1-9]|1[0-2])\/[0-9]{2}$/'],
            'cvv' => ['required', 'digits_between:3,4'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.produit_id' => ['required_with:items', 'integer', 'exists:produit,id_p'],
            'items.*.quantite' => ['required_with:items', 'integer', 'min:1', 'max:100'],
            'produit_id' => ['nullable', 'integer', 'exists:produit,id_p'],
            'quantite' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $items = $validated['items'] ?? [[
            'produit_id' => $validated['produit_id'] ?? null,
            'quantite' => $validated['quantite'] ?? null,
        ]];

        if (empty($items[0]['produit_id']) || empty($items[0]['quantite'])) {
            throw ValidationException::withMessages([
                'items' => 'Le panier doit contenir au moins un produit.',
            ]);
        }

        $commande = DB::transaction(function () use ($validated): Commande {
            $items = collect($validated['items'] ?? [[
                'produit_id' => $validated['produit_id'],
                'quantite' => $validated['quantite'],
            ]])->groupBy('produit_id')->map(
                fn ($lines) => $lines->sum('quantite')
            );
            $lines = [];
            $total = 0;

            foreach ($items as $produitId => $quantite) {
                $produit = Produit::query()
                    ->whereKey($produitId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($produit->stock_p < $quantite) {
                    throw ValidationException::withMessages([
                        'items' => "Le stock de {$produit->designation_p} est insuffisant.",
                    ]);
                }

                $prix = (float) $produit->prix_final;
                $ligneTotal = round($prix * $quantite, 2);
                $total += $ligneTotal;
                $produit->decrement('stock_p', $quantite);
                $lines[] = compact('produit', 'quantite', 'prix', 'ligneTotal');
            }

            $commande = Commande::create([
                'user_id' => auth()->id(),
                'nom_client' => $validated['nom'],
                'email_client' => $validated['email'],
                'statut' => 'en_attente',
                'total_ht' => round($total, 2),
            ]);

            foreach ($lines as $line) {
                $commande->lignes()->create([
                    'produit_id' => $line['produit']->id_p,
                    'designation' => $line['produit']->designation_p,
                    'quantite' => $line['quantite'],
                    'prix_unitaire_ht' => $line['prix'],
                    'total_ligne_ht' => $line['ligneTotal'],
                ]);
            }

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
        $this->authorizeCommande($commande);

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
        $this->authorizeCommande($commande);

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

    private function authorizeCommande(Commande $commande): void
    {
        abort_unless(
            auth()->user()?->role === 'admin' || $commande->user_id === auth()->id(),
            403
        );
    }
}
