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
        Schema::table('planche_bons_livraison', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->after('id')
                ->constrained('clients')
                ->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('planche_bon_livraison_id')
                ->nullable()
                ->after('client_id')
                ->unique()
                ->constrained('planche_bons_livraison')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('planche_bon_livraison_id');
        });

        Schema::table('planche_bons_livraison', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
