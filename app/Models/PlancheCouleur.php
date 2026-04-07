<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PlancheCouleur extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'image_path',
    ];

    protected $appends = [
        'image_url',
    ];

    public function details()
    {
        return $this->hasMany(PlancheDetail::class, 'planche_couleur_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
