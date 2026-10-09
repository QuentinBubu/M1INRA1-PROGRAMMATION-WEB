<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produit', function (Blueprint $table) {
            // Clé primaire personnalisée
            $table->id('id_p');

            // Informations de base
            $table->string('type_p', 100);
            $table->string('designation_p', 255);
            $table->decimal('prix_ht', 10, 2);

            // Gestion du stock
            $table->integer('stock_p')->default(0);

            // Gestion des dates
            $table->date('date_in');
            $table->timestamp('times_in')->useCurrent();

            // Nouveaux champs demandés pour l'évolution
            $table->integer('ppromo')->nullable()->comment('Pourcentage de promotion optionnel');
            $table->string('image')->nullable()->comment('Chemin de l\'image du produit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produit');
    }
};
