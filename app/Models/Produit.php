<?php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Produit.
 * Gère les articles du site e-commerce et expose automatiquement un CRUD via API Platform.
 */
#[ApiResource]
class Produit extends Model
{
    // Forcer le nom de la table pour respecter le script SQL
    protected $table = 'produit';

    // Définir la clé primaire personnalisée
    protected $primaryKey = 'id_p';

    // Désactiver les timestamps par défaut de Laravel car le sujet utilise 'times_in' et 'date_in'
    public $timestamps = false;

    // Champs autorisés pour la création/modification via l'API (Protection contre l'assignation de masse)
    protected $fillable = [
        'type_p',
        'designation_p',
        'prix_ht',
        'date_in',
        'stock_p',
        'ppromo',
        'image'
    ];

    // Ces variables virtuelles seront renvoyées par l'API
    protected $appends = ['prix_final', 'image_url'];

    /**
     * Règle métier : Calcul du prix dynamique.
     * S'il y a une promotion (ppromo), on calcule le nouveau prix.
     * Sinon, on affiche le prix hors taxe normal.
     */
    public function getPrixFinalAttribute()
    {
        if (!empty($this->ppromo)) {
            $remise = ($this->prix_ht * $this->ppromo) / 100;
            return round($this->prix_ht - $remise, 2);
        }

        return $this->prix_ht;
    }

    /**
     * Validation : Le miroir de sécurité serveur (API Constraints)
     */
    public static function rules(): array
    {
        return [
            'type_p' => 'required|string|max:100', // Contrainte Required et limite de taille
            'designation_p' => 'required|string|max:255',
            'prix_ht' => 'required|numeric|min:0', // Contrainte Min/Max : le prix ne peut pas être négatif
            'stock_p' => 'integer|min:0',
            'date_in' => 'required|date',
            'ppromo' => 'nullable|integer|min:1|max:99', // Optionnel, entre 1% et 99%
            'image' => 'nullable|string'
        ];
    }

    /**
     * Règle métier : Affichage de l'image.
     * Si aucune image n'est liée, on retourne une image générique.
     */
    public function getImageUrlAttribute()
    {
        if (empty($this->image)) {
            return asset('images/default-product.png'); // L'image par défaut à placer dans public/images/
        }

        return asset('storage/' . $this->image);
    }
}
