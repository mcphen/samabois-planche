<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlancheBenefitHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'planche_detail_id',
        'planche_bon_livraison_id',
        'planche_bon_livraison_ligne_id',
        'action',
        'old_data',
        'new_data',
        'notes',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plancheDetail(): BelongsTo
    {
        return $this->belongsTo(PlancheDetail::class);
    }

    public function plancheBonLivraison(): BelongsTo
    {
        return $this->belongsTo(PlancheBonLivraison::class);
    }

    public function plancheBonLivraisonLigne(): BelongsTo
    {
        return $this->belongsTo(PlancheBonLivraisonLigne::class);
    }
}
