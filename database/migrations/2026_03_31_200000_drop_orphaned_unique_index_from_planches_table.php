<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('planches') || ! $this->hasIndex('planches', 'planches_contrat_id_code_couleur_unique')) {
            return;
        }

        // Keep a regular index for the FK before dropping the obsolete unique index.
        if (! $this->hasIndex('planches', 'planches_contrat_id_index')) {
            DB::statement('ALTER TABLE `planches` ADD INDEX `planches_contrat_id_index` (`contrat_id`)');
        }

        DB::statement('ALTER TABLE `planches` DROP INDEX `planches_contrat_id_code_couleur_unique`');
    }

    public function down(): void
    {
        if (! Schema::hasTable('planches') || ! Schema::hasColumn('planches', 'code_couleur')) {
            return;
        }

        if (! $this->hasIndex('planches', 'planches_contrat_id_code_couleur_unique')) {
            DB::statement('ALTER TABLE `planches` ADD UNIQUE KEY `planches_contrat_id_code_couleur_unique` (`contrat_id`, `code_couleur`)');
        }

        if ($this->hasIndex('planches', 'planches_contrat_id_index')) {
            DB::statement('ALTER TABLE `planches` DROP INDEX `planches_contrat_id_index`');
        }
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
