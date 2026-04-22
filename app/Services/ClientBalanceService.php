<?php

namespace App\Services;

use App\Models\Client;
use App\Models\PlancheBonLivraison;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ClientBalanceService
{
    public function sync(Client $client): array
    {
        return DB::transaction(function () use ($client) {
            $totalDue = (float) Transaction::query()
                ->where('client_id', $client->id)
                ->where('type', 'invoice')
                ->sum('amount');

            $totalPaid = (float) Transaction::query()
                ->where('client_id', $client->id)
                ->where('type', 'payment')
                ->sum('amount');

            $remainingPayment = $totalPaid;

            $bonsLivraison = PlancheBonLivraison::query()
                ->where('client_id', $client->id)
                ->where(function ($query) {
                    $query->whereNull('statut')
                        ->orWhere('statut', '!=', 'annule');
                })
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '!=', 'canceled');
                })
                ->orderBy('date_livraison')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($bonsLivraison as $bonLivraison) {
                $montant = (float) $bonLivraison->montant;
                $montantSolde = min(max($remainingPayment, 0), $montant);
                $remainingPayment -= $montantSolde;

                $bonLivraison->forceFill([
                    'montant_solde' => round($montantSolde, 2),
                    'status' => $montant > 0 && $montantSolde >= $montant ? 'validated' : 'pending',
                ])->save();
            }

            return [
                'amount_due' => $totalDue,
                'amount_payment' => $totalPaid,
                'amount_solde' => $totalDue - $totalPaid,
                'credit_disponible' => max($remainingPayment, 0),
            ];
        });
    }
}
