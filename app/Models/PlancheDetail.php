<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlancheDetail extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'planche_id',
        'planche_couleur_id',
        'epaisseur',
        'quantite_prevue',
    ];

    protected $casts = [
        'epaisseur'       => 'decimal:2',
        'quantite_prevue' => 'integer',
    ];

    public function planche()
    {
        return $this->belongsTo(Planche::class);
    }

    public function couleur()
    {
        return $this->belongsTo(PlancheCouleur::class, 'planche_couleur_id');
    }

    public function bonLivraisonLignes()
    {
        return $this->hasMany(PlancheBonLivraisonLigne::class);
    }

    public function benefitHistories()
    {
        return $this->hasMany(PlancheBenefitHistory::class);
    }

    public function auditLabel(): ?string
    {
        $contrat = Planche::query()->with('contrat:id,numero')->find($this->planche_id)?->contrat?->numero;

        return collect([
            PlancheCouleur::query()->whereKey($this->planche_couleur_id)->value('code'),
            $this->categorie,
            $this->epaisseur !== null ? 'ép. ' . (float) $this->epaisseur : null,
            $contrat ? 'contrat ' . $contrat : null,
        ])->filter()->implode(' · ') ?: null;
    }
}
