<?php

namespace Database\Factories;

use App\Models\Produit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produit>
 */
class ProduitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
        return [
            'type_p' => $this->faker->randomElement(['Smartphone', 'Ordinateur portable', 'Casque audio', 'Montre connectée', 'Tablette', 'Caméra']),
            'designation_p' => $this->faker->words(3, true),
            'prix_ht' => $this->faker->randomFloat(2, 50, 1500), // Prix entre 50 et 1500 €
            'stock_p' => $this->faker->numberBetween(0, 100),
            'ppromo' => $this->faker->optional(0.3)->numberBetween(5, 50), // 30% de chance d'avoir une promo entre 5 et 50%
            'date_in' => $this->faker->dateTime()
        ];
    }
}
