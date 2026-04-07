<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_type',
        'caisse_transaction_id',
        'payment_id',
        'transaction_id',
        'caisse_transfer_id',
        'old_amount', 'new_amount',
        'old_date', 'new_date',
        'old_description', 'new_description',
        'old_amount_source', 'new_amount_source',
        'old_amount_destination', 'new_amount_destination',
        'old_exchange_rate', 'new_exchange_rate',
        'reason',
        'user_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'old_date' => 'date',
        'new_date' => 'date',
    ];

    public function caisseTransaction()
    {
        return $this->belongsTo(CaisseTransaction::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function caisseTransfer()
    {
        return $this->belongsTo(CaisseTransfer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
