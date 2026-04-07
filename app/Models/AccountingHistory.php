<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'client_id',
        'amount_due',
        'amount_payment',
        'amount_solde',
        'notes',
    ];

    /**
     * Get the user that performed the accounting restoration.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client whose accounting was restored.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
