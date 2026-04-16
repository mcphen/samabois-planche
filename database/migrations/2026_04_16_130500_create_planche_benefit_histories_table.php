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
        Schema::create('planche_benefit_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('planche_detail_id')->nullable()->constrained('planche_details')->nullOnDelete();
            $table->foreignId('planche_bon_livraison_id')->nullable()->constrained('planche_bons_livraison')->nullOnDelete();
            $table->foreignId('planche_bon_livraison_ligne_id')->nullable()->constrained('planche_bon_livraison_lignes')->nullOnDelete();
            $table->string('action');
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planche_benefit_histories');
    }
};
