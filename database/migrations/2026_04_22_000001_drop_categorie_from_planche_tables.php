<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('planche_tarifs', 'categorie')) {
            Schema::table('planche_tarifs', function (Blueprint $table) {
                if ($this->hasIndex('planche_tarifs', 'planche_tarifs_categorie_epaisseur_contrat_id_unique')) {
                    $table->dropUnique('planche_tarifs_categorie_epaisseur_contrat_id_unique');
                }

                $table->dropColumn('categorie');
            });
        }

        if (! $this->hasIndex('planche_tarifs', 'planche_tarifs_epaisseur_contrat_id_unique')) {
            Schema::table('planche_tarifs', function (Blueprint $table) {
                $table->unique(['epaisseur', 'contrat_id']);
            });
        }

        if (Schema::hasColumn('planche_details', 'categorie')) {
            Schema::table('planche_details', function (Blueprint $table) {
                $table->dropColumn('categorie');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('planche_tarifs', 'planche_tarifs_epaisseur_contrat_id_unique')) {
            Schema::table('planche_tarifs', function (Blueprint $table) {
                $table->dropUnique('planche_tarifs_epaisseur_contrat_id_unique');
            });
        }

        if (! Schema::hasColumn('planche_tarifs', 'categorie')) {
            Schema::table('planche_tarifs', function (Blueprint $table) {
                $table->enum('categorie', ['mate', 'semi_brillant', 'brillant'])->after('id')->default('mate');
            });
        }

        if (! $this->hasIndex('planche_tarifs', 'planche_tarifs_categorie_epaisseur_contrat_id_unique')) {
            Schema::table('planche_tarifs', function (Blueprint $table) {
                $table->unique(['categorie', 'epaisseur', 'contrat_id']);
            });
        }

        if (! Schema::hasColumn('planche_details', 'categorie')) {
            Schema::table('planche_details', function (Blueprint $table) {
                $table->enum('categorie', ['mate', 'semi_brillant', 'brillant'])->nullable()->after('planche_couleur_id');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return ! empty(DB::select(
            'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?',
            [$index],
        ));
    }
};
