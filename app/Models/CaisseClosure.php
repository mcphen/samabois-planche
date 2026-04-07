<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaisseClosure extends Model
{
    use HasFactory, HasSyncUuid;

    protected $fillable = [
        'caisse_id',
        'start_date',
        'end_date',
        'initial_balance',
        'total_entries',
        'total_exits',
        'total_transfer_in',
        'total_transfer_out',
        'theoretical_balance',
        'real_balance',
        'difference',
        'status',
        'created_by',
        'validated_by',
        'notes',
    ];

    public function caisse()
    {
        return $this->belongsTo(Caisse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
