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
    ];

    protected $casts = [
        'quantite_livree' => 'integer',
        'prix_unitaire' => 'decimal:2',
        'prix_total' => 'decimal:2',
    ];

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
