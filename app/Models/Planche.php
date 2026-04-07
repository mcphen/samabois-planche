<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planche extends Model
{
    use HasFactory;

    protected $fillable = [
        'contrat_id',
    ];

    public function contrat()
    {
        return $this->belongsTo(Contrat::class);
    }

    public function details()
    {
        return $this->hasMany(PlancheDetail::class);
    }
}
