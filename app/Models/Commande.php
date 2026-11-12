<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    protected $fillable = [
        'nom_client',
        'email_client',
        'statut',
        'total_ht',
    ];

    protected $casts = [
        'total_ht' => 'decimal:2',
    ];

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneCommande::class);
    }
}
