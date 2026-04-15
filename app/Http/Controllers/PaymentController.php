<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\CaisseTransaction;
use App\Models\Client;
use App\Models\HistoriqueClientSolde;
use App\Models\Payment;
use App\Models\PlancheBonLivraison;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PaymentController extends Controller
{
    public function store($clientId,Request $request)
    {
        $request->validate([
            //'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:1',
            //'date' => 'required|date',
            'caisse_id' => 'nullable|exists:caisses,id',
        ]);

        $client = Client::findOrFail($clientId);
        $amountToPay = $request->amount;
        $remainingAmount = $amountToPay;

        //dd($remainingAmount);

        // Récupérer les bons de livraison non soldés, triés par date
        $bonsLivraison = PlancheBonLivraison::where('client_id', $client->id)
            ->where('status', '!=', 'validated')
            ->orderBy('date_livraison')
            ->get();

        // Créer un enregistrement de paiement
        $payment = Payment::create([
            'client_id' => $client->id,
            'amount' => $amountToPay,
            'date' => $request->transaction_date,
        ]);

        $transaction = Transaction::firstOrCreate([
            'client_id'        => $client->id,
            'type'             => 'payment',
            'amount'           => $amountToPay,
            'transaction_date' => $request->transaction_date,
        ]);

        if (!$bonsLivraison->isEmpty()) {
            foreach ($bonsLivraison as $bl) {
                if ($remainingAmount <= 0) break;

                $blDue = (float) $bl->montant - (float) $bl->montant_solde;

                if ($blDue > 0) {
                    $paidAmount = min($remainingAmount, $blDue);
                    $remainingAmount -= $paidAmount;

                    $bl->montant_solde += $paidAmount;
                    $bl->status = $bl->montant_solde >= $bl->montant ? 'validated' : 'pending';
                    $bl->save();
                }
            }
        }


        // **Enregistrer l'entrée dans la caisse**
        CaisseTransaction::create([
            'caisse_id'             => $request->input('caisse_id') ?: $this->resolveDefaultCaisseId(),
            'transaction_id'        => $transaction->id,
            'type'                  => 'entree', // legacy
            'movement_type'         => CaisseTransaction::MOV_ENTREE_CLIENT,
            'amount'                => $amountToPay,
            'payment_id'            => $payment->id,
            'date'                  => $request->transaction_date,
            'objet'                 => "Paiement par le  client : ".$client->name,
            'user_id'               => auth()->id(),
        ]);
        return response()->json([
            'message' => 'Paiement enregistré avec succès.',
            'remaining_amount' => $remainingAmount,
        ]);
    }



    public function miseAjourCaisseTransaction(){
        $transactions = Transaction::where('type', 'payment')->get();

        foreach ($transactions as $transaction) {
            $amountToPay = number_format((float)$transaction->amount, 2, '.', '');

            $caisse = CaisseTransaction::where([
                'type' => 'entree',
                'amount' => $amountToPay,
                'date' => $transaction->transaction_date,
            ])->update([
                'transaction_id' => $transaction->id,
            ]);

        }
    }

    public function cancelSolde(Client $client, Request $request)
    {
        $amountToCancel = (float) $client->amount_solde;

        if ($amountToCancel == 0) {
            return response()->json(['message' => 'Le solde est déjà à zéro.'], 400);
        }

        // 1. Sauvegarder dans l'historique
        HistoriqueClientSolde::query()->firstOrCreate([
            'client_id' => $client->id,
            'amount'    => $amountToCancel,
            'date'      => Carbon::now()
        ]);

        if ($amountToCancel > 0) {
            // Solde positif : le client doit de l'argent → on crée un paiement d'ajustement
            Transaction::create([
                'client_id'        => $client->id,
                'type'             => 'payment',
                'isSolde'          => true,
                'amount'           => $amountToCancel,
                'transaction_date' => Carbon::now()->toDateString(),
            ]);

            // Marquer tous les BL non soldés comme validés
            PlancheBonLivraison::where('client_id', $client->id)
                ->where('status', '!=', 'validated')
                ->where('statut', '!=', 'annule')
                ->each(function (PlancheBonLivraison $bl) {
                    $bl->montant_solde = $bl->montant;
                    $bl->status        = 'validated';
                    $bl->save();
                });
        } else {
            // Solde négatif : le client a un crédit → on crée une facture d'ajustement pour absorber le crédit
            Transaction::create([
                'client_id'        => $client->id,
                'type'             => 'invoice',
                'isSolde'          => true,
                'amount'           => abs($amountToCancel),
                'transaction_date' => Carbon::now()->toDateString(),
            ]);
        }

        return response()->json(['message' => 'Le compte est maintenant soldé.']);
    }

    public function updatePayment($transactionId,Request $request)
    {
        $amountToPay = $request->amount;
        $transaction = Transaction::where('id', $transactionId)->first();

        $clientId = $transaction->client_id;

        $client = Client::query()->find($clientId);

        $oldAmountPaiement = $client->amount_payment - $amountToPay;
        $oldAmountSolde = $client->amount_solde + $amountToPay;

        $newOldAmountPaiement = $oldAmountPaiement + $amountToPay;
        $newOldAmountSolde = $oldAmountSolde - $amountToPay;

        //dd($oldAmountPaiement,$oldAmountSolde,$newOldAmountPaiement,$newOldAmountSolde);

        //$client->amount_payment = $newOldAmountPaiement;
        //$client->amount_solde = $newOldAmountSolde;

        //$client->save();


        $transaction->update([

            'amount'           => $amountToPay,
            'transaction_date' => $request->transaction_date,
        ]);

        CaisseTransaction::where('transaction_id', $transactionId)->update([
            'amount' => $amountToPay,
            'date' => $transaction->transaction_date,
        ]);
        return response()->json(['message' => 'Paiement modifié avec succès.',]);

    }

    public function deletePayment($transactionId)
    {
        $transaction = Transaction::where('id', $transactionId)->first();

        if (!$transaction) {
            return response()->json(['error' => 'Transaction non trouvée.'], 404);
        }

        CaisseTransaction::where('transaction_id', $transactionId)->delete();

        $client = Client::query()->find($transaction->client_id);

        $transaction->delete();

        return response()->json(['message' => 'Le paiement a été supprimé avec succès.']);
    }

    /**
     * Récupère l'identifiant de la caisse par défaut (première caisse active).
     * Lève une erreur si aucune caisse n'existe tant que l'UI ne permet pas la sélection.
     */
    private function resolveDefaultCaisseId(): int
    {
        $caisse = Caisse::query()->where('active', true)->orderBy('id')->first();
        if (!$caisse) {
            abort(response()->json([
                'message' => "Aucune caisse active n'a été trouvée. Créez au moins une caisse avant d'enregistrer des paiements.",
            ], 422));
        }
        return (int) $caisse->id;
    }

    public function generatePaymentInvoice($payment_id)
    {
        $payment = Payment::with(['client', 'invoices'])->findOrFail($payment_id);

        $pdf = Pdf::loadView('pdf.payment_invoice', compact('payment'));

        return $pdf->download('facture_paiement_'.$payment->id.'.pdf');
    }
}
