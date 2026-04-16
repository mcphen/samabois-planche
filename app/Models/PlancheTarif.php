<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PlancheTarif extends Model
{
    use HasFactory;

    protected $fillable = [
        'categorie',
        'epaisseur',
        'prix',
    ];

    protected $casts = [
        'epaisseur' => 'decimal:2',
        'prix'      => 'decimal:2',
    ];

    /** Cache par requête pour éviter les N+1. */
    private static ?Collection $cache = null;

    /**
     * Retourne tous les tarifs indexés par "categorie|epaisseur".
     * Résultat mis en cache pour la durée de la requête.
     */
    public static function getAllKeyed(): Collection
    {
        if (static::$cache === null) {
            static::$cache = static::all()->keyBy(
                fn (self $t) => $t->categorie . '|' . number_format((float) $t->epaisseur, 2, '.', '')
            );
        }

        return static::$cache;
    }

    /** Vider le cache (utile après une mise à jour de tarif dans la même requête). */
    public static function clearCache(): void
    {
        static::$cache = null;
    }

    /**
     * Retrouve le prix pour une combinaison categorie + epaisseur.
     * Retourne null si aucun tarif n'est défini.
     */
    public static function getPrixFor(string $categorie, float|string $epaisseur): ?float
    {
        $key = $categorie . '|' . number_format((float) $epaisseur, 2, '.', '');

        $tarif = static::getAllKeyed()->get($key);

        return $tarif !== null ? (float) $tarif->prix : null;
    }
}
