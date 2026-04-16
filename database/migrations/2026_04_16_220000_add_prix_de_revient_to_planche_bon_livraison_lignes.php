<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planche_bon_livraison_lignes', function (Blueprint $table) {
            $table->decimal('prix_de_revient', 10, 2)->nullable()->after('prix_total');
        });
    }

    public function down(): void
    {
        Schema::table('planche_bon_livraison_lignes', function (Blueprint $table) {
            $table->dropColumn('prix_de_revient');
        });
    }
};
