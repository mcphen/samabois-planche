<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planche_bons_livraison', function (Blueprint $table) {
            $table->decimal('montant', 12, 2)->default(0)->after('statut');
        });

        // Backfill : calculer le montant pour les BL existants
        DB::statement('
            UPDATE planche_bons_livraison pbl
            SET montant = (
                SELECT COALESCE(SUM(l.prix_total), 0)
                FROM planche_bon_livraison_lignes l
                WHERE l.planche_bon_livraison_id = pbl.id
            )
        ');
    }

    public function down(): void
    {
        Schema::table('planche_bons_livraison', function (Blueprint $table) {
            $table->dropColumn('montant');
        });
    }
};
