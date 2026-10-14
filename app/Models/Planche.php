<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planche extends Model
{
    use HasFactory, Auditable;

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

    public function auditLabel(): ?string
    {
        $numero = Contrat::query()->whereKey($this->contrat_id)->value('numero');

        return $numero ? 'Contrat ' . $numero : null;
    }
}
