<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlancheDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'planche_id',
        'planche_couleur_id',
        'categorie',
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
}
