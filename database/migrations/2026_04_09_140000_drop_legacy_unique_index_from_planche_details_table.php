<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('planche_details') || ! $this->hasIndex('planche_details', 'planche_details_planche_id_epaisseur_unique')) {
            return;
        }

        DB::statement('ALTER TABLE `planche_details` DROP INDEX `planche_details_planche_id_epaisseur_unique`');
    }

    public function down(): void
    {
        if (! Schema::hasTable('planche_details')
            || $this->hasIndex('planche_details', 'planche_details_planche_id_epaisseur_unique')) {
            return;
        }

        DB::statement('ALTER TABLE `planche_details` ADD UNIQUE `planche_details_planche_id_epaisseur_unique` (`planche_id`, `epaisseur`)');
    }

    private function hasIndex(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
