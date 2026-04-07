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
        Schema::create('caisse_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_caisse_id')->constrained('caisses')->cascadeOnDelete();
            $table->foreignId('destination_caisse_id')->constrained('caisses')->cascadeOnDelete();
            $table->decimal('amount_source', 15, 2);
            $table->decimal('amount_destination', 15, 2);
            $table->decimal('exchange_rate', 18, 8)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('transfer_date');
            $table->foreignId('source_caisse_transaction_id')->nullable()->constrained('caisse_transactions')->nullOnDelete();
            $table->foreignId('destination_caisse_transaction_id')->nullable()->constrained('caisse_transactions')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_transfers');
    }
};
