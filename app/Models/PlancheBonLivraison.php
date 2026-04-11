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
    ];

    protected $casts = [
        'date_livraison' => 'date',
    ];

    public function lignes()
    {
        return $this->hasMany(PlancheBonLivraisonLigne::class, 'planche_bon_livraison_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'planche_bon_livraison_id');
    }

}
