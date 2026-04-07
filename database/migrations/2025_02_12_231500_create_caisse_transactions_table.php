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
        Schema::create('caisse_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['entree', 'sortie']); // Indique si c'est un paiement client ou une dépense
            $table->decimal('amount', 15, 2); // Montant de la transaction
            $table->string('objet')->nullable(); // Objet de la sortie
            $table->text('description')->nullable(); // Description de la sortie
            $table->unsignedBigInteger('payment_id')->nullable(); // Lien avec un paiement client
            $table->timestamp('date'); // Date de la transaction
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_transactions');
    }
};
