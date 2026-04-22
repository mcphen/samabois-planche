<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planche_tarifs', function (Blueprint $table) {
            $table->dropUnique(['categorie', 'epaisseur', 'contrat_id']);
            $table->dropColumn('categorie');
            $table->unique(['epaisseur', 'contrat_id']);
        });

        Schema::table('planche_details', function (Blueprint $table) {
            $table->dropColumn('categorie');
        });
    }

    public function down(): void
    {
        Schema::table('planche_tarifs', function (Blueprint $table) {
            $table->dropUnique(['epaisseur', 'contrat_id']);
            $table->enum('categorie', ['mate', 'semi_brillant', 'brillant'])->after('id')->default('mate');
            $table->unique(['categorie', 'epaisseur', 'contrat_id']);
        });

        Schema::table('planche_details', function (Blueprint $table) {
            $table->enum('categorie', ['mate', 'semi_brillant', 'brillant'])->nullable()->after('planche_couleur_id');
        });
    }
};
