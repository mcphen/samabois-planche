<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensurePlancheCouleurColumn();

        if (! Schema::hasColumn('planche_details', 'categorie')) {
            Schema::table('planche_details', function (Blueprint $table) {
                $table->enum('categorie', ['mate', 'semi_brillant', 'brillant'])
                    ->after('planche_couleur_id')
                    ->default('mate');
            });
        }

        if (! $this->hasIndex('planche_details', $this->categorieUniqueIndex())) {
            Schema::table('planche_details', function (Blueprint $table) {
                $table->unique(
                    ['planche_id', 'planche_couleur_id', 'categorie', 'epaisseur'],
                    $this->categorieUniqueIndex()
                );
            });
        }

        $this->dropIndexIfExists('planche_details', 'planche_details_planche_id_planche_couleur_id_epaisseur_unique');
        $this->dropIndexIfExists('planche_details', 'planche_details_planche_id_couleur_epaisseur_unique');
    }

    public function down(): void
    {
        if (! $this->hasIndex('planche_details', 'planche_details_planche_id_planche_couleur_id_epaisseur_unique')
            && ! $this->hasIndex('planche_details', 'planche_details_planche_id_couleur_epaisseur_unique')) {
            Schema::table('planche_details', function (Blueprint $table) {
                $table->unique(['planche_id', 'planche_couleur_id', 'epaisseur']);
            });
        }

        $this->dropIndexIfExists('planche_details', $this->categorieUniqueIndex());

        if (Schema::hasColumn('planche_details', 'categorie')) {
            Schema::table('planche_details', function (Blueprint $table) {
                $table->dropColumn('categorie');
            });
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

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->hasIndex($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    private function categorieUniqueIndex(): string
    {
        return 'planche_details_planche_couleur_cat_epaisseur_uq';
    }

    private function ensurePlancheCouleurColumn(): void
    {
        if (Schema::hasColumn('planche_details', 'planche_couleur_id')) {
            return;
        }

        if (DB::table('planche_details')->count() > 0) {
            throw new RuntimeException('The planche_details table is missing planche_couleur_id while still containing data. Backfill the color mapping before running this migration.');
        }

        Schema::table('planche_details', function (Blueprint $table) {
            $table->foreignId('planche_couleur_id')
                ->after('planche_id')
                ->constrained('planche_couleurs')
                ->restrictOnDelete();
        });
    }
};
