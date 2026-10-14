<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoriqueClientSolde extends Model
{
    use HasFactory, Auditable;

    protected $fillable = ['client_id','amount','date'];

    public function auditLabel(): ?string
    {
        return Client::withTrashed()->whereKey($this->client_id)->value('name');
    }
}
