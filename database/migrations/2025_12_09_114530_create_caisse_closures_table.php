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
        if (!Schema::hasTable('caisse_closures')) {
            Schema::create('caisse_closures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('caisse_id')->constrained('caisses')->cascadeOnDelete();
                $table->dateTime('start_date');
                $table->dateTime('end_date');
                $table->decimal('initial_balance', 15, 2)->default(0);
                $table->decimal('total_entries', 15, 2)->default(0);
                $table->decimal('total_exits', 15, 2)->default(0);
                $table->decimal('total_transfer_in', 15, 2)->default(0);
                $table->decimal('total_transfer_out', 15, 2)->default(0);
                $table->decimal('theoretical_balance', 15, 2)->default(0);
                $table->decimal('real_balance', 15, 2)->default(0);
                $table->decimal('difference', 15, 2)->default(0);
                $table->enum('status', ['draft', 'validated', 'cancelled'])->default('draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_closures');
    }
};
