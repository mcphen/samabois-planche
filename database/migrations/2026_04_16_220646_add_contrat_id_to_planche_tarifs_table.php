<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planche_tarifs', function (Blueprint $table) {
            $table->foreignId('contrat_id')->nullable()->after('epaisseur')->constrained('contrats')->nullOnDelete();
            $table->dropUnique(['categorie', 'epaisseur']);
            $table->unique(['categorie', 'epaisseur', 'contrat_id']);
        });
    }

    public function down(): void
    {
        Schema::table('planche_tarifs', function (Blueprint $table) {
            $table->dropUnique(['categorie', 'epaisseur', 'contrat_id']);
            $table->dropForeign(['contrat_id']);
            $table->dropColumn('contrat_id');
            $table->unique(['categorie', 'epaisseur']);
        });
    }
};
