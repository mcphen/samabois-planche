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
        Schema::create('planche_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planche_id')->constrained('planches')->cascadeOnDelete();
            $table->foreignId('planche_couleur_id')->constrained('planche_couleurs')->restrictOnDelete();
            $table->decimal('epaisseur', 8, 2);
            $table->unsignedInteger('quantite_prevue');
            $table->timestamps();

            $table->unique(['planche_id', 'planche_couleur_id', 'epaisseur']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planche_details');
    }
};
