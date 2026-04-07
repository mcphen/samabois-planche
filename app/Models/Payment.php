<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory, HasSyncUuid;

    protected $fillable = [
        'client_id',
        'amount',
        'date',
    ];

    /**
     * Relation avec le client.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation avec les factures affectées par ce paiement.
     */
    public function invoices()
    {
        return $this->belongsToMany(Invoice::class)->withPivot('amount_paid')->withTimestamps();
    }

    /**
     * Mouvements de caisse liés à ce paiement.
     */
    public function caisseTransactions()
    {
        return $this->hasMany(CaisseTransaction::class);
    }
}
