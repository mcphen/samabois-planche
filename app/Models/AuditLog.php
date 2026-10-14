<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'event',
        'auditable_type',
        'auditable_id',
        'auditable_label',
        'old_values',
        'new_values',
        'ip',
        'url',
        'route_name',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Libellés lisibles des éléments suivis (clé = classe du modèle).
     */
    public const TYPE_LABELS = [
        Contrat::class                  => 'Contrat',
        Planche::class                  => 'Planche',
        PlancheDetail::class            => 'Ligne de planche (stock)',
        PlancheCouleur::class           => 'Couleur',
        Epaisseur::class                => 'Épaisseur',
        Supplier::class                 => 'Fournisseur',
        Client::class                   => 'Client',
        PlancheBonLivraison::class      => 'Facture / Bon de livraison',
        PlancheBonLivraisonLigne::class => 'Ligne de facture',
        PlancheTarif::class             => 'Prix de revient',
        Transaction::class              => 'Transaction client',
        HistoriqueClientSolde::class    => 'Solde de compte client',
        User::class                     => 'Utilisateur',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
