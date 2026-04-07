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
        Schema::create('planche_bons_livraison', function (Blueprint $table) {
            $table->id();
            $table->string('numero_bl')->unique();
            $table->date('date_livraison');
            $table->enum('statut', ['brouillon', 'valide'])->default('brouillon');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planche_bons_livraison');
    }
};
