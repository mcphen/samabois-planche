<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlancheBonLivraison extends Model
{
    use HasFactory;

    protected $table = 'planche_bons_livraison';

    protected $fillable = [
        'client_id',
        'numero_bl',
        'date_livraison',
        'statut',
        'montant',
        'montant_solde',
        'status',
    ];

    protected $casts = [
        'date_livraison' => 'date',
        'montant'        => 'decimal:2',
        'montant_solde'  => 'decimal:2',
    ];

    public function recalculerMontant(): void
    {
        $this->update([
            'montant' => $this->lignes()->sum('prix_total'),
        ]);
    }

    public function lignes()
    {
        return $this->hasMany(PlancheBonLivraisonLigne::class, 'planche_bon_livraison_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'planche_bon_livraison_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'planche_bon_livraison_id')
            ->where('type', 'invoice')
            ->latest();
    }
}
