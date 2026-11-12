<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('nom_client');
            $table->string('email_client');
            $table->string('statut')->default('en_attente');
            $table->decimal('total_ht', 10, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('commande_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->unsignedBigInteger('produit_id');
            $table->string('designation');
            $table->unsignedInteger('quantite');
            $table->decimal('prix_unitaire_ht', 10, 2);
            $table->decimal('total_ligne_ht', 10, 2);
            $table->timestamps();

            $table->index('produit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_lignes');
        Schema::dropIfExists('commandes');
    }
};
