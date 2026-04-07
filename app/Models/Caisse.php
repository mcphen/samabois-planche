<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caisse extends Model
{
    use HasFactory, HasSyncUuid;

    protected $fillable = [
        'name',
        'type',
        'currency_code',
        'initial_balance',
        'active',
    ];

    public function transactions()
    {
        return $this->hasMany(CaisseTransaction::class);
    }

    public function transfersAsSource()
    {
        return $this->hasMany(CaisseTransfer::class, 'source_caisse_id');
    }

    public function transfersAsDestination()
    {
        return $this->hasMany(CaisseTransfer::class, 'destination_caisse_id');
    }

    public function closures()
    {
        return $this->hasMany(CaisseClosure::class);
    }
}
