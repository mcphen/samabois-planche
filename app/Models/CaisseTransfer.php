<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaisseTransfer extends Model
{
    use HasFactory, HasSyncUuid;

    protected $fillable = [
        'source_caisse_id',
        'destination_caisse_id',
        'amount_source',
        'amount_destination',
        'exchange_rate',
        'description',
        'status',
        'corrected_transfer_id',
        'transfer_date',
        'source_caisse_transaction_id',
        'destination_caisse_transaction_id',
    ];

    public function correctedTransfer()
    {
        return $this->belongsTo(CaisseTransfer::class, 'corrected_transfer_id');
    }

    public function sourceCaisse()
    {
        return $this->belongsTo(Caisse::class, 'source_caisse_id');
    }

    public function destinationCaisse()
    {
        return $this->belongsTo(Caisse::class, 'destination_caisse_id');
    }

    public function sourceTransaction()
    {
        return $this->belongsTo(CaisseTransaction::class, 'source_caisse_transaction_id');
    }

    public function destinationTransaction()
    {
        return $this->belongsTo(CaisseTransaction::class, 'destination_caisse_transaction_id');
    }
}
