<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, HasSyncUuid;

    protected $fillable = [
        'client_id',
        'type',
        'isSolde',
        'amount',
        'transaction_date',
        'invoice_id',
        'planche_bon_livraison_id',
        'old_transaction',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class,'client_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function plancheBonLivraison()
    {
        return $this->belongsTo(PlancheBonLivraison::class, 'planche_bon_livraison_id');
    }

    /**
     * Mouvements de caisse liés à cette transaction comptable.
     */
    public function caisseTransactions()
    {
        return $this->hasMany(CaisseTransaction::class);
    }
}
