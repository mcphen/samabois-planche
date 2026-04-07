<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('planche_bon_livraison_lignes', function (Blueprint $table) {
            $table->decimal('prix_unitaire', 12, 2)->default(0)->after('quantite_livree');
            $table->decimal('prix_total', 12, 2)->default(0)->after('prix_unitaire');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planche_bon_livraison_lignes', function (Blueprint $table) {
            $table->dropColumn(['prix_unitaire', 'prix_total']);
        });
    }
};
