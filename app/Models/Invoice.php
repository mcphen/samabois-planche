<?php

namespace App\Models;

use App\Traits\HasSyncUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, HasSyncUuid;

    protected $fillable = ['client_id', 'planche_bon_livraison_id', 'date', 'total_price', 'matricule','status','montant_solde'];

    /*public static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $invoice->matricule = self::generateMatricule($invoice->date);
        });
    }*/

    protected static function boot()
    {
        parent::boot();
        static::bootHasSyncUuid();

        static::creating(function (Invoice $invoice) {
            // Récupère la plus haute séquence, y compris les soft-deleted
            $lastSeq = Invoice::max('sequence') ?? 0;
            $nextSeq = $lastSeq + 1;

            $invoice->sequence  = $nextSeq;
            $invoice->matricule = $nextSeq . '/' . now()->year;
        });
    }
    private static function generateMatricule($date)
    {
        $year = date('Y', strtotime($date));

        // Compter le nombre de factures existantes pour cette année
        $count = self::whereYear('date', $year)->count() + 1;

        return "{$count}/{$year}";
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function plancheBonLivraison()
    {
        return $this->belongsTo(PlancheBonLivraison::class, 'planche_bon_livraison_id');
    }

    // Met à jour le prix total de la facture
    public function updateTotalPrice()
    {
        $this->loadMissing('items', 'plancheBonLivraison.lignes');

        $this->total_price = $this->planche_bon_livraison_id
            ? (float) $this->plancheBonLivraison?->lignes?->sum('prix_total')
            : $this->items->sum(fn($item) => $item->total_price_item);


        $this->save();

        Transaction::query()->updateOrCreate([
            'invoice_id'        => $this->id,
            'type'             => 'invoice',
        ],[
            'client_id'        => $this->client_id,


            'amount'           => $this->total_price,
            'transaction_date' => $this->date,
        ]);

        $client = Client::where('id',$this->client_id)->first();
        $this->updateAmountClient($client,$client->id);


    }

    private function updateAmountClient(Client $client, $clientId){
        $clients = Client::whereHas('transactions') // Clients ayant au moins une transaction
        ->withSum(['transactions as total_invoices' => function ($query) {
            $query->where('type', 'invoice');
        }], 'amount')
            ->withSum(['transactions as total_payments' => function ($query) {
                $query->where('type', 'payment');
            }], 'amount')
            ->where('id',$clientId)->first();
        $client->amount_due = $clients->total_invoices;
        $client->amount_solde = $clients->balance;
        $client->save();
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class)->withPivot('amount_paid')->withTimestamps();
    }

    /**
     * Scope pour rechercher des factures par critères de ArticleItem
     *
     * @param Builder $query
     * @param array $criteria Critères de recherche pour ArticleItem
     * @return Builder
     */
    public function scopeWithArticleItemCriteria(Builder $query, array $criteria)
    {
        return $query->whereHas('items.articleItem', function (Builder $subQuery) use ($criteria) {
            foreach ($criteria as $field => $value) {
                $subQuery->where($field, 'like', "%{$value}%");
            }
        });
    }


}
