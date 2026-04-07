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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            //$table->unsignedBigInteger('client_id');
            $table->enum('type', ['invoice', 'payment']);
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');


            $table->foreignId('client_id')->nullable()
                ->references('id')->on('clients')
                ->onDelete('cascade');

            $table->foreignId('invoice_id')->nullable()
                ->references('id')->on('invoices')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
