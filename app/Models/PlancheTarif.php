<?php

namespace App\Models;

use App\Models\Contrat;
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
        'contrat_id',
    ];

    protected $casts = [
        'epaisseur'   => 'decimal:2',
        'prix'        => 'decimal:2',
        'contrat_id'  => 'integer',
    ];

    public function contrat()
    {
        return $this->belongsTo(Contrat::class);
    }

    /** Cache par requête pour éviter les N+1. */
    private static ?Collection $cache = null;

    /**
     * Retourne tous les tarifs indexés par "{contrat_id}|categorie|epaisseur".
     * Un contrat_id null = tarif global (s'applique à tous les contrats).
     */
    public static function getAllKeyed(): Collection
    {
        if (static::$cache === null) {
            static::$cache = static::all()->keyBy(
                fn (self $t) => ($t->contrat_id ?? '') . '|' . $t->categorie . '|' . number_format((float) $t->epaisseur, 2, '.', '')
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
     * Si contrat_id est fourni, un tarif spécifique à ce contrat est prioritaire sur le tarif global.
     * Retourne null si aucun tarif n'est défini.
     */
    public static function getPrixFor(string $categorie, float|string $epaisseur, ?int $contratId = null): ?float
    {
        $keyed  = static::getAllKeyed();
        $suffix = '|' . $categorie . '|' . number_format((float) $epaisseur, 2, '.', '');

        if ($contratId !== null) {
            $tarif = $keyed->get($contratId . $suffix);
            if ($tarif !== null) {
                return (float) $tarif->prix;
            }
        }

        // Fallback sur le tarif global (contrat_id = null)
        $tarif = $keyed->get($suffix);

        return $tarif !== null ? (float) $tarif->prix : null;
    }
}
