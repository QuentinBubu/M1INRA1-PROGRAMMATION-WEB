<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    protected $fillable = [
        'nom_client',
        'email_client',
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
