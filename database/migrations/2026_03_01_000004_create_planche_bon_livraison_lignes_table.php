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
        Schema::create('planche_bon_livraison_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planche_bon_livraison_id')
                ->constrained('planche_bons_livraison')
                ->cascadeOnDelete();
            $table->foreignId('planche_detail_id')
                ->constrained('planche_details')
                ->restrictOnDelete();
            $table->unsignedInteger('quantite_livree');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planche_bon_livraison_lignes');
    }
};
