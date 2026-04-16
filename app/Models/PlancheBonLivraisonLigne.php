<?php

namespace App\Models;

use App\Models\Contrat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlancheBonLivraisonLigne extends Model
{
    use HasFactory;

    protected $table = 'planche_bon_livraison_lignes';

    protected $fillable = [
        'planche_bon_livraison_id',
        'planche_detail_id',
        'contrat_id',
        'quantite_livree',
        'prix_unitaire',
        'prix_total',
        'prix_de_revient',
    ];

    protected $casts = [
        'quantite_livree'  => 'integer',
        'prix_unitaire'    => 'decimal:2',
        'prix_total'       => 'decimal:2',
        'prix_de_revient'  => 'decimal:2',
    ];

    public function getBeneficeUnitaireAttribute(): ?float
    {
        if ($this->prix_unitaire === null || $this->prix_de_revient === null) {
            return null;
        }
        return (float) $this->prix_unitaire - (float) $this->prix_de_revient;
    }

    public function getBeneficeTotalAttribute(): ?float
    {
        $beneficeUnitaire = $this->benefice_unitaire;
        if ($beneficeUnitaire === null) {
            return null;
        }
        return $beneficeUnitaire * $this->quantite_livree;
    }

    public function bonLivraison()
    {
        return $this->belongsTo(PlancheBonLivraison::class, 'planche_bon_livraison_id');
    }

    public function plancheDetail()
    {
        return $this->belongsTo(PlancheDetail::class);
    }

    public function contrat()
    {
        return $this->belongsTo(Contrat::class);
    }
}
