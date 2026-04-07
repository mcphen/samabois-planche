<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use HasFactory, HasSyncUuid;

    protected $fillable = ['name','slug_name', 'address', 'phone', 'email'];

    public function contrats()
    {
        return $this->hasMany(Contrat::class);
    }


    protected static function boot()
    {
        parent::boot();
        static::bootHasSyncUuid();

        static::creating(function ($client) {
            $client->slug_name = Str::slug($client->name);

            // Vérifier si un client avec le même slug existe déjà
            $existing = self::where('slug_name', $client->slug_name)->exists();
            if ($existing) {
                throw new \Exception("Ce nom de fournisseur existe déjà !");
            }
        });
    }
}
