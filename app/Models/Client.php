<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory, HasSyncUuid, SoftDeletes;

    protected $fillable = ['name', 'slug', 'address', 'phone', 'email'];

    public function getAmountDueAttribute(): float
    {
        return (float) $this->transactions()->where('type', 'invoice')->sum('amount');
    }

    public function getAmountPaymentAttribute(): float
    {
        return (float) $this->transactions()->where('type', 'payment')->sum('amount');
    }

    public function getAmountSoldeAttribute(): float
    {
        return $this->getAmountDueAttribute() - $this->getAmountPaymentAttribute();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Génération automatique du slug lors de l'enregistrement
    protected static function boot()
    {
        parent::boot();
        static::bootHasSyncUuid();

        static::creating(function ($client) {
            $client->slug = Str::slug($client->name);

            // Vérifier si un client avec le même slug existe déjà
            $existing = self::where('slug', $client->slug)->exists();
            if ($existing) {
                throw new \Exception("Ce nom de client existe déjà !");
            }
        });
    }

    /**
     * Relation : Un client peut avoir plusieurs factures.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Obtenir le total dû par le client (somme des factures non payées).
     */
    public function getTotalDueAttribute()
    {
        return $this->invoices()->where('status', '!=', 'payé')->sum('total_price');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

}
