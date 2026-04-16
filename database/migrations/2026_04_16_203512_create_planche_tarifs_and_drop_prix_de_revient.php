<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Créer la table des tarifs (prix de revient global par categorie + epaisseur)
        Schema::create('planche_tarifs', function (Blueprint $table) {
            $table->id();
            $table->enum('categorie', ['mate', 'semi_brillant', 'brillant']);
            $table->decimal('epaisseur', 8, 2);
            $table->decimal('prix', 10, 2);
            $table->timestamps();

            $table->unique(['categorie', 'epaisseur']);
        });

        // 2. Supprimer la colonne prix_de_revient de planche_details
        if (Schema::hasColumn('planche_details', 'prix_de_revient')) {
            Schema::table('planche_details', function (Blueprint $table) {
                $table->dropColumn('prix_de_revient');
            });
        }
    }

    public function down(): void
    {
        // Recréer la colonne prix_de_revient
        if (! Schema::hasColumn('planche_details', 'prix_de_revient')) {
            Schema::table('planche_details', function (Blueprint $table) {
                $table->decimal('prix_de_revient', 10, 2)->nullable()->after('quantite_prevue');
            });
        }

        Schema::dropIfExists('planche_tarifs');
    }
};
