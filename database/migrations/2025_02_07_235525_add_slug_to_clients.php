<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('clients', 'slug')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('slug')->nullable()->unique()->after('name');
            });
        }

        // Générer les slugs pour les clients existants
        $usedSlugs = [];
        $clients = DB::table('clients')->select('id', 'name')->orderBy('id')->get();
        foreach ($clients as $client) {
            $baseSlug = Str::slug($client->name) ?: 'client-' . $client->id;
            $slug = $baseSlug;
            $suffix = 2;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $usedSlugs[] = $slug;

            DB::table('clients')
                ->where('id', $client->id)
                ->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('clients', 'slug')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
