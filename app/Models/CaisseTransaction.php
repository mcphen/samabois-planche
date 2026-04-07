<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaisseTransaction extends Model
{
    use HasFactory, SoftDeletes, HasSyncUuid;

    /**
     * Mouvement caisse détaillé (remplace la simple colonne legacy `type`).
     * Valeurs possibles:
     *  - entree_client        : entrée liée à un paiement client
     *  - entree_autre         : entrée diverse (autre que paiement client)
     *  - sortie               : sortie simple (dépense)
     *  - transfert_entrant    : entrée issue d'un transfert depuis une autre caisse
     *  - transfert_sortant    : sortie liée à un transfert vers une autre caisse
     */
    public const MOV_ENTREE_CLIENT      = 'entree_client';
    public const MOV_ENTREE_AUTRE       = 'entree_autre';
    public const MOV_SORTIE             = 'sortie';
    public const MOV_TRANSFERT_ENTRANT  = 'transfert_entrant';
    public const MOV_TRANSFERT_SORTANT  = 'transfert_sortant';

    /**
     * Liste des valeurs autorisées pour `movement_type`.
     */
    public const ALLOWED_MOVEMENT_TYPES = [
        self::MOV_ENTREE_CLIENT,
        self::MOV_ENTREE_AUTRE,
        self::MOV_SORTIE,
        self::MOV_TRANSFERT_ENTRANT,
        self::MOV_TRANSFERT_SORTANT,
    ];

    protected $fillable = [
        'caisse_id',
        'type', // legacy: 'entree' | 'sortie'
        'movement_type', // voir constantes ci-dessus
        'amount',
        'objet',
        'description',
        'payment_id',
        'transaction_id',
        'date',
        'caisse_transfer_id',
        'user_id',
    ];

    public function caisse()
    {
        return $this->belongsTo(Caisse::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function transfer()
    {
        return $this->belongsTo(CaisseTransfer::class, 'caisse_transfer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include transactions of a given caisse.
     */
    public function scopeForCaisse($query, int $caisseId)
    {
        return $query->where('caisse_id', $caisseId);
    }

    /**
     * Get the sum of amounts for a specific caisse.
     */
    public static function sumAmountForCaisse(int $caisseId): float
    {
        return (float) static::where('caisse_id', $caisseId)->sum('amount');
    }
}
