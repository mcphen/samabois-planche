<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrat extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'supplier_id',
        'numero',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function planches()
    {
        return $this->hasMany(Planche::class);
    }
}
