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
        $tables = [
            'users', 'articles', 'article_items', 'clients', 'suppliers',
            'invoices', 'invoice_items', 'payments', 'transactions',
            'caisse_transactions', 'caisses', 'caisse_transfers', 'caisse_closures'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'uuid')) {
                        $table->uuid('uuid')->nullable()->unique()->after('id');
                    }
                    if (!Schema::hasColumn($tableName, 'remote_id')) {
                        $table->unsignedBigInteger('remote_id')->nullable()->index();
                    }
                    if (!Schema::hasColumn($tableName, 'last_synced_at')) {
                        $table->timestamp('last_synced_at')->nullable();
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users', 'articles', 'article_items', 'clients', 'suppliers',
            'invoices', 'invoice_items', 'payments', 'transactions',
            'caisse_transactions', 'caisses', 'caisse_transfers', 'caisse_closures'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropColumn(['uuid', 'remote_id', 'last_synced_at']);
                });
            }
        }
    }
};
