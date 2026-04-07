<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArticleItem extends Model
{
    use HasFactory, SoftDeletes, HasSyncUuid;

    protected $fillable = [
        'article_id',
        'numero_colis',
        'longueur',
        'largeur',
        'epaisseur',
        'nombre_piece',
        'volume','indisponible'
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
