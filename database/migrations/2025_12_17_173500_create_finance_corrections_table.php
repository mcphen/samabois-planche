<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_corrections')) {
            Schema::create('finance_corrections', function (Blueprint $table) {
                $table->id();
                $table->string('correction_type'); // client_payment | incoming_transfer

                // Liens contextuels (tous facultatifs car dépendent du type)
                $table->unsignedBigInteger('caisse_transaction_id')->nullable();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->unsignedBigInteger('transaction_id')->nullable();
                $table->unsignedBigInteger('caisse_transfer_id')->nullable();

                // Champs génériques avant/après (paiements)
                $table->decimal('old_amount', 15, 2)->nullable();
                $table->decimal('new_amount', 15, 2)->nullable();
                $table->date('old_date')->nullable();
                $table->date('new_date')->nullable();
                $table->string('old_description')->nullable();
                $table->string('new_description')->nullable();

                // Champs spécifiques transferts
                $table->decimal('old_amount_source', 15, 2)->nullable();
                $table->decimal('new_amount_source', 15, 2)->nullable();
                $table->decimal('old_amount_destination', 15, 2)->nullable();
                $table->decimal('new_amount_destination', 15, 2)->nullable();
                $table->decimal('old_exchange_rate', 18, 6)->nullable();
                $table->decimal('new_exchange_rate', 18, 6)->nullable();

                $table->string('reason')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->json('meta')->nullable();

                $table->timestamps();

                // Indexes pour requêtes rapides
                $table->index(['correction_type']);
                $table->index(['caisse_transaction_id']);
                $table->index(['payment_id']);
                $table->index(['transaction_id']);
                $table->index(['caisse_transfer_id']);
                $table->index(['user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_corrections');
    }
};
