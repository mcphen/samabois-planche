<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\CaisseTransaction;
use App\Models\Client;
use App\Models\FinanceCorrection;
use App\Models\HistoriqueClientSolde;
use App\Models\Payment;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

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

        // Récupérer les factures non soldées, triées par date
        $invoices = $client->invoices()->where('status', '!=', 'validated')->orderBy('date')->get();

        /*if ($invoices->isEmpty()) {
            return response()->json(['message' => 'Aucune facture en attente de paiement.'], 400);
        }*/

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


        if(!$invoices->isEmpty()){
            foreach ($invoices as $invoice) {
                if ($remainingAmount <= 0) break;

                $invoiceDue = $invoice->total_price - $invoice->payments()->sum('amount_paid');

                if ($invoiceDue > 0) {
                    $paidAmount = min($remainingAmount, $invoiceDue);
                    $remainingAmount -= $paidAmount;

                    // Enregistrer le paiement pour cette facture
                    $invoice->payments()->attach($payment->id, ['amount_paid' => $paidAmount]);

                    // Mettre à jour le montant payé et statut
                    $invoice->montant_solde += $paidAmount;
                    if ($invoice->montant_solde >= $invoice->total_price) {
                        $invoice->status = 'validated'; // Facture soldée
                    } else {
                        $invoice->status = 'pending'; // Facture partiellement payée
                    }
                    $invoice->save();
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
        $this->updateAmountClient($client, $client->id);
       // $client->amount_payment += $amountToPay;
       // $client->amount_solde -= $amountToPay;
       // $client->save();
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

        // 2. Mettre à jour les montants du client
        $this->updateAmountClient($client, $client->id);

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
        $this->updateAmountClient($client, $clientId);

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

        if ($client) {
            $this->updateAmountClient($client, $client->id);
        }

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

    public function updateAmountClient(Client $client, $clientId){
        // Snapshots avant recalcul
        $before_amount_payment = (float) $client->amount_payment;
        $before_amount_solde   = (float) $client->amount_solde;

        $clients = Client::whereHas('transactions') // Clients ayant au moins une transaction
        ->withSum(['transactions as total_invoices' => function ($query) {
            $query->where('type', 'invoice');
        }], 'amount')
            ->withSum(['transactions as total_payments' => function ($query) {
                $query->where('type', 'payment');
            }], 'amount')
            ->where('id',$clientId)->first();

        // Valeurs calculées
        $new_amount_payment = (float) ($clients->total_payments ?? 0);
        $new_amount_solde   = (float) ($clients->balance ?? 0);

        $client->amount_payment = $new_amount_payment;
        $client->amount_solde   = $new_amount_solde;
        $client->save();

        // Logs détaillés du rétablissement/comptabilité client
        Log::info('updateAmountClient: Client amounts recomputed', [
            'client_id'            => $clientId,
            'before_amount_payment'=> $before_amount_payment,
            'before_amount_solde'  => $before_amount_solde,
            'new_amount_payment'   => $new_amount_payment,
            'new_amount_solde'     => $new_amount_solde,
            'delta_payment'        => $new_amount_payment - $before_amount_payment,
            'delta_solde'          => $new_amount_solde - $before_amount_solde,
            'timestamp'            => now()->toDateTimeString(),
            'source'               => 'PaymentController::updateAmountClient',
        ]);

        // Journalisation en base de données (table finance_corrections)
        // On consigne l'opération de rétablissement des montants client
        FinanceCorrection::create([
            'correction_type' => 'client_account_recompute',
            // champs génériques: on utilise amount pour le total des paiements
            'old_amount'      => $before_amount_payment,
            'new_amount'      => $new_amount_payment,
            'old_date'        => null,
            'new_date'        => null,
            'old_description' => null,
            'new_description' => null,
            'reason'          => null,
            'user_id'         => auth()->id(),
            'meta'            => [
                'client_id'     => $clientId,
                'before'        => [
                    'amount_payment' => $before_amount_payment,
                    'amount_solde'   => $before_amount_solde,
                ],
                'after'         => [
                    'amount_payment' => $new_amount_payment,
                    'amount_solde'   => $new_amount_solde,
                ],
                'deltas'        => [
                    'payment' => $new_amount_payment - $before_amount_payment,
                    'solde'   => $new_amount_solde - $before_amount_solde,
                ],
                'source'        => 'PaymentController::updateAmountClient',
                'timestamp'     => now()->toDateTimeString(),
            ],
        ]);
    }

    public function storeOldPaiement(Request $request,Client $client){
        Transaction::firstOrCreate([
            'client_id'=>$client->id,
            'type'=>'invoice',
            'amount'=>$request['amount'],
            'transaction_date'=> \Carbon\Carbon::now(),
            //'invoice_id',
            'old_transaction'=>1
        ]);

        $client->amount_due += $request['amount'];
        $client->amount_solde += $request['amount'];
        $client->save();
    }


    public function generatePaymentInvoice($payment_id)
    {
        $payment = Payment::with(['client', 'invoices'])->findOrFail($payment_id);

        $pdf = Pdf::loadView('pdf.payment_invoice', compact('payment'));

        return $pdf->download('facture_paiement_'.$payment->id.'.pdf');
    }
}
