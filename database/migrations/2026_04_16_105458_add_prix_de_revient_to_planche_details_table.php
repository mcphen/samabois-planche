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
        Schema::table('planche_details', function (Blueprint $table) {
            $table->decimal('prix_de_revient', 10, 2)->nullable()->after('quantite_prevue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planche_details', function (Blueprint $table) {
            $table->dropColumn('prix_de_revient');
        });
    }
};
