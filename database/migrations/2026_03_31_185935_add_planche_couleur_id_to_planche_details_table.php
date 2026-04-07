<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('planche_details')) {
            return;
        }

        if (! Schema::hasColumn('planche_details', 'planche_couleur_id')) {
            if (DB::table('planche_details')->count() > 0) {
                throw new RuntimeException('The planche_details table still uses the old schema and contains data. Backfill planche_couleur_id before running this migration.');
            }

            Schema::table('planche_details', function (Blueprint $table) {
                $table->foreignId('planche_couleur_id')
                    ->after('planche_id')
                    ->constrained('planche_couleurs')
                    ->restrictOnDelete();
            });
        }

        $tempIndexAdded = false;

        if ($this->hasIndex('planche_details', 'planche_details_planche_id_epaisseur_unique')) {
            // Keep a simple backing index for the FK before dropping the legacy unique index.
            if (! $this->hasIndex('planche_details', 'tmp_planche_id_idx')
                && ! $this->hasIndex('planche_details', 'planche_details_planche_id_planche_couleur_id_epaisseur_unique')
                && ! $this->hasIndex('planche_details', 'planche_details_planche_id_couleur_epaisseur_unique')) {
                DB::statement('ALTER TABLE `planche_details` ADD INDEX `tmp_planche_id_idx` (`planche_id`)');
                $tempIndexAdded = true;
            }

            DB::statement('ALTER TABLE `planche_details` DROP INDEX `planche_details_planche_id_epaisseur_unique`');
        }

        if (! $this->hasForeignKey('planche_details', 'planche_details_planche_couleur_id_foreign')) {
            Schema::table('planche_details', function (Blueprint $table) {
                $table->foreign('planche_couleur_id')->references('id')->on('planche_couleurs')->restrictOnDelete();
            });
        }

        if (! $this->hasIndex('planche_details', 'planche_details_planche_id_planche_couleur_id_epaisseur_unique')
            && ! $this->hasIndex('planche_details', 'planche_details_planche_id_couleur_epaisseur_unique')) {
            Schema::table('planche_details', function (Blueprint $table) {
                $table->unique(['planche_id', 'planche_couleur_id', 'epaisseur']);
            });
        }

        if ($tempIndexAdded
            && $this->hasIndex('planche_details', 'tmp_planche_id_idx')
            && ($this->hasIndex('planche_details', 'planche_details_planche_id_planche_couleur_id_epaisseur_unique')
                || $this->hasIndex('planche_details', 'planche_details_planche_id_couleur_epaisseur_unique'))) {
            DB::statement('ALTER TABLE `planche_details` DROP INDEX `tmp_planche_id_idx`');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('planche_details')) {
            return;
        }

        $newIndexName = $this->hasIndex('planche_details', 'planche_details_planche_id_planche_couleur_id_epaisseur_unique')
            ? 'planche_details_planche_id_planche_couleur_id_epaisseur_unique'
            : ($this->hasIndex('planche_details', 'planche_details_planche_id_couleur_epaisseur_unique')
                ? 'planche_details_planche_id_couleur_epaisseur_unique'
                : null);

        $tempIndexAdded = false;

        if ($newIndexName && ! $this->hasIndex('planche_details', 'tmp_planche_id_idx')) {
            DB::statement('ALTER TABLE `planche_details` ADD INDEX `tmp_planche_id_idx` (`planche_id`)');
            $tempIndexAdded = true;
        }

        if ($newIndexName) {
            DB::statement("ALTER TABLE `planche_details` DROP INDEX `{$newIndexName}`");
        }

        if ($this->hasForeignKey('planche_details', 'planche_details_planche_couleur_id_foreign')
            || Schema::hasColumn('planche_details', 'planche_couleur_id')) {
            Schema::table('planche_details', function (Blueprint $table) {
                if ($this->hasForeignKey('planche_details', 'planche_details_planche_couleur_id_foreign')) {
                    $table->dropForeign('planche_details_planche_couleur_id_foreign');
                }

                if (Schema::hasColumn('planche_details', 'planche_couleur_id')) {
                    $table->dropColumn('planche_couleur_id');
                }
            });
        }

        if (! $this->hasIndex('planche_details', 'planche_details_planche_id_epaisseur_unique')) {
            DB::statement('ALTER TABLE `planche_details` ADD UNIQUE `planche_details_planche_id_epaisseur_unique` (`planche_id`, `epaisseur`)');
        }

        if ($tempIndexAdded && $this->hasIndex('planche_details', 'tmp_planche_id_idx')) {
            DB::statement('ALTER TABLE `planche_details` DROP INDEX `tmp_planche_id_idx`');
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

    private function hasForeignKey(string $table, string $foreignKey): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $foreignKey)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
