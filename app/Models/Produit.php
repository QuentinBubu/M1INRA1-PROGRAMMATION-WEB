<?php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use Illuminate\Database\Eloquent\Model;

#[ApiResource]
class Produit extends Model
{
    protected $table = 'produit';
    protected $primaryKey = 'id_p';
    public $timestamps = false;
    protected $fillable = [
        'type_p',
        'designation_p',
        'prix_ht',
        'date_in',
        'stock_p',
        'ppromo',
        'image'
    ];
    protected $appends = ['prix_final', 'image_url'];


    public function getPrixFinalAttribute()
    {
        if (!empty($this->ppromo)) {
            $remise = ($this->prix_ht * $this->ppromo) / 100;
            return round($this->prix_ht - $remise, 2);
        }

        return $this->prix_ht;
    }

    public static function rules(): array
    {
        return [
            'type_p' => 'required|string|max:100',
            'designation_p' => 'required|string|max:255',
            'prix_ht' => 'required|numeric|min:0',
            'stock_p' => 'integer|min:0',
            'date_in' => 'required|date',
            'ppromo' => 'nullable|integer|min:1|max:99',
            'image' => 'nullable|string'
        ];
    }


    public function getImageUrlAttribute()
    {
        if (empty($this->image)) {
            return asset('images/default-product.png');
        }

        return asset('storage/' . $this->image);
    }
}
