<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Epaisseur extends Model
{
    use HasFactory, HasSyncUuid;

    protected $fillable = [
        'intitule',
        'slug',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::bootHasSyncUuid();

        static::saving(function (Epaisseur $epaisseur) {
            $epaisseur->intitule = trim((string) $epaisseur->intitule);
            $epaisseur->slug = trim((string) ($epaisseur->slug ?: Str::slug($epaisseur->intitule)));
        });
    }
}
