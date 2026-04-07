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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->enum('essence', ['Ayous', 'Frake', 'Dibetou', 'Bois Rouge']);
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->string('contract_number')->unique();
            $table->boolean('indisponible')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
