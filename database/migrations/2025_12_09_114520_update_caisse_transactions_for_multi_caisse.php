<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1) Add columns first (without immediate FK on caisse_id to allow backfill)
        Schema::table('caisse_transactions', function (Blueprint $table) {
            // New relations/columns
            if (!Schema::hasColumn('caisse_transactions', 'caisse_id')) {
                $table->unsignedBigInteger('caisse_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('caisse_transactions', 'movement_type')) {
                $table->string('movement_type')->nullable()->after('type');
            }
            if (!Schema::hasColumn('caisse_transactions', 'caisse_transfer_id')) {
                $table->foreignId('caisse_transfer_id')->nullable()->after('transaction_id')
                    ->constrained('caisse_transfers')->nullOnDelete();
            }
            if (!Schema::hasColumn('caisse_transactions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('caisse_transfer_id')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // 2) Ensure there is at least one active caisse to reference
        $defaultCaisseId = DB::table('caisses')->where('active', true)->orderBy('id')->value('id');
        if (!$defaultCaisseId) {
            $defaultCaisseId = DB::table('caisses')->insertGetId([
                'name' => 'Caisse principale',
                'type' => 'cash',
                'currency_code' => null,
                'initial_balance' => 0,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3) Backfill existing caisse_transactions rows
        // Important: keep the original `date` column intact for rows that already exist.
        // Some schemas define `date` with ON UPDATE CURRENT_TIMESTAMP, which would auto-change on any UPDATE.
        // Explicitly setting `date` to itself prevents the auto-update.
        DB::table('caisse_transactions')->whereNull('caisse_id')->update([
            'caisse_id' => $defaultCaisseId,
            'date' => DB::raw('`date`'),
        ]);

        // 4) Now add the foreign key constraint for caisse_id
        Schema::table('caisse_transactions', function (Blueprint $table) {
            $table->foreign('caisse_id')->references('id')->on('caisses')->cascadeOnDelete();
        });

        // 5) Clean possible orphaned payment_id before adding FK
        // Also keep `date` intact to avoid unintended auto-updates on the `date` column
        if (config('database.default') === 'sqlite' || config('database.connections.' . config('database.default') . '.driver') === 'sqlite') {
            DB::statement("UPDATE caisse_transactions SET payment_id = NULL, \"date\" = \"date\" WHERE payment_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM payments WHERE payments.id = caisse_transactions.payment_id)");
        } else {
            DB::statement("UPDATE caisse_transactions ct LEFT JOIN payments p ON ct.payment_id = p.id SET ct.payment_id = NULL, ct.`date` = ct.`date` WHERE ct.payment_id IS NOT NULL AND p.id IS NULL");
        }

        // 6) Add FK for existing payment_id (nullable)
        Schema::table('caisse_transactions', function (Blueprint $table) {
            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caisse_transactions', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropForeign(['caisse_id']);
            $table->dropForeign(['caisse_transfer_id']);
            $table->dropForeign(['user_id']);

            $table->dropColumn(['caisse_id', 'movement_type', 'caisse_transfer_id', 'user_id']);
        });
    }
};
