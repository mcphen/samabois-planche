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
        if (Schema::hasTable('caisse_transfers')) {
            Schema::table('caisse_transfers', function (Blueprint $table) {
                if (!Schema::hasColumn('caisse_transfers', 'status')) {
                    $table->string('status')->default('validé')->after('description'); // validé, annulé, corrigé
                }
                if (!Schema::hasColumn('caisse_transfers', 'corrected_transfer_id')) {
                    $table->foreignId('corrected_transfer_id')->nullable()->constrained('caisse_transfers')->onDelete('set null')->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caisse_transfers', function (Blueprint $table) {
            $table->dropForeign(['corrected_transfer_id']);
            $table->dropColumn(['status', 'corrected_transfer_id']);
        });
    }
};
