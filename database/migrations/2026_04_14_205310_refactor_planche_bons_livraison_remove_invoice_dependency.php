<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajouter montant_solde et status à planche_bons_livraison
        Schema::table('planche_bons_livraison', function (Blueprint $table) {
            $table->decimal('montant_solde', 12, 2)->default(0)->after('montant');
            $table->enum('status', ['pending', 'validated', 'canceled'])->default('pending')->after('montant_solde');
        });

        // 2. Ajouter planche_bon_livraison_id à transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('planche_bon_livraison_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('planche_bons_livraison')
                ->nullOnDelete();
        });

        // 3. Backfill : créer une Transaction pour chaque BL existant sans transaction
        $bls = DB::table('planche_bons_livraison')->get();
        foreach ($bls as $bl) {
            $exists = DB::table('transactions')
                ->where('planche_bon_livraison_id', $bl->id)
                ->exists();

            if (! $exists) {
                DB::table('transactions')->insert([
                    'client_id'                => $bl->client_id,
                    'type'                     => 'invoice',
                    'amount'                   => $bl->montant,
                    'transaction_date'         => $bl->date_livraison,
                    'planche_bon_livraison_id' => $bl->id,
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['planche_bon_livraison_id']);
            $table->dropColumn('planche_bon_livraison_id');
        });

        Schema::table('planche_bons_livraison', function (Blueprint $table) {
            $table->dropColumn(['montant_solde', 'status']);
        });
    }
};
