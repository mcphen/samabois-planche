<?php

namespace App\Http\Controllers;

use App\Models\AccountingHistory;
use App\Models\Client;
use App\Models\HistoriqueClientSolde;
use App\Models\Invoice;
use App\Models\PlancheBonLivraison;
use App\Models\InvoicePayment;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    // Vue principale pour l'interface
    public function index()
    {
        return Inertia::render('Clients/Index');
    }

    public function comptes(){
        return Inertia::render('Clients/ClientAccounts');
    }
    // Vue principale pour l'interface
    public function show(Client $client)
    {
        $totalInvoice = PlancheBonLivraison::where('client_id', $client->id)
            ->where('status', '!=', 'canceled')
            ->count();

        $totalDue = Transaction::where('client_id', $client->id)
            ->where('type', 'invoice')
            ->whereNotNull('planche_bon_livraison_id')
            ->sum('amount');

        $totalPaid = Transaction::where('client_id', $client->id)
            ->where('type', 'payment')
            ->sum('amount');

        $totalSolde = $totalDue - $totalPaid;

        return Inertia::render('Clients/Show', [
            'client'        => $client,
            'total_due'     => $totalDue,
            'total_paid'    => $totalPaid,
            'total_solde'   => $totalSolde,
            'total_invoice' => $totalInvoice,
        ]);
    }

    // API pour récupérer les clients paginés
    public function getClients(Request $request)
    {
        $query = Client::query();

        // Filtrer par nom si fourni
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filtrer par adresse si fournie
        if ($request->has('address') && !empty($request->address)) {
            $query->where('address', 'like', '%' . $request->address . '%');
        }

        // Filtrer par téléphone si fourni
        if ($request->has('phone') && !empty($request->phone)) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        // Filtrer par email si fourni
        if ($request->has('email') && !empty($request->email)) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        $clients = $query->paginate(500); // 500 clients par page
        return response()->json($clients);
    }

    public function fetchInvoices(Client $client)
    {
        $invoices = Invoice::where([
            'client_id'=>$client->id
        ])->paginate(25);

        return response()->json($invoices);

    }

    public function fetchPaiement(Client $client)
    {
        $invoices = Invoice::where([
            'client_id'=>$client->id
        ])->paginate(25);

        return response()->json($invoices);

    }

    public function update(Request $request, Client $client)
    {
       // dd($client);
        $client->name = $request['name'];
        $client->phone = $request['phone'];
        $client->email = $request['email'];
        $client->address = $request['address'];

        $client->save();

        return response()->json($client);

    }

    public function fetchTransactionPaiement(Client $client)
    {
        $transactions = Transaction::with('plancheBonLivraison')
            ->where('client_id', $client->id)
            ->where(function ($q) {
                $q->where('type', 'payment')
                  ->orWhere(function ($q2) {
                      $q2->where('type', 'invoice')
                          ->whereNotNull('planche_bon_livraison_id');
                  });
            })
            ->orderBy('transaction_date', 'asc')
            ->get();

        $runningBalance = 0;
        $data = [];

        foreach ($transactions as $transaction) {
            $montantFacture = null;

            if ($transaction->type === 'invoice') {
                $montantFacture = (float) (
                    $transaction->plancheBonLivraison?->montant
                    ?? $transaction->amount
                );
                $runningBalance += $montantFacture;
            } else {
                $runningBalance -= $transaction->amount;
            }

            $data[] = [
                'id'      => $transaction->id,
                'date'    => $transaction->transaction_date,
                'invoice' => $montantFacture,
                'payment' => $transaction->type === 'payment' ? $transaction->amount : null,
                'cumul'   => $runningBalance,
                'facture' => $transaction->plancheBonLivraison,
                'type'    => $transaction->type,
                'isSolde' => (bool) $transaction->isSolde,
            ];
        }

        return response()->json($data);
    }

    public function geratePDF(Client $client)
    {
        $transactions = Transaction::with('plancheBonLivraison')
            ->where('client_id', $client->id)
            ->where(function ($q) {
                $q->where('type', 'payment')
                  ->orWhere(function ($q2) {
                      $q2->where('type', 'invoice')
                          ->whereNotNull('planche_bon_livraison_id');
                  });
            })
            ->orderBy('transaction_date', 'asc')
            ->get();

        $runningBalance = 0;
        $data = [];

        foreach ($transactions as $transaction) {
            $montantFacture = null;
            if ($transaction->type === 'invoice') {
                $montantFacture = (float) (
                    $transaction->plancheBonLivraison?->montant ?? $transaction->amount
                );
                $runningBalance += $montantFacture;
            } else {
                $runningBalance -= $transaction->amount;
            }

            $data[] = [
                'date'    => $transaction->transaction_date,
                'invoice' => $montantFacture,
                'payment' => $transaction->type === 'payment' ? $transaction->amount : null,
                'cumul'   => $runningBalance,
                'facture' => $transaction->plancheBonLivraison,
                'isSolde' => (bool) $transaction->isSolde,
            ];
        }

        $pdf = Pdf::loadView('pdf.facture_client', compact('data', 'client'));
        return $pdf->stream('historique_caisse.pdf');
    }

    /**
     * Génère un rapport PDF des comptes clients
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function generatePDF(Invoice $invoice)
    {
        $filteredClients = $this->getClientsWithBalances();

        $pdf = PDF::loadView('pdf.client_account', ['clients' => $filteredClients]);
        return $pdf->stream("repport_client_compte.pdf");
    }

    public function recuperationCompte()
    {
        $clients = Client::all();

        foreach ($clients as $client){
            $total_invoices = Client::where('id',$client->id)->whereHas('transactions') // Clients ayant au moins une transaction
            ->withSum(['transactions as total_invoices' => function ($query) {
                $query->where('type', 'invoice');
            }], 'amount')->first();
            //dd($total_invoices);

            $total_payments = Client::where('id',$client->id)->whereHas('transactions') // Clients ayant au moins une transaction
            ->withSum(['transactions as total_payments' => function ($query) {
                $query->where('type', 'payment');
            }], 'amount')->first();

           // dd($total_invoices,$total_payments);

            $amount_due = !empty($total_invoices->total_invoices)? $total_invoices->total_invoices : 0;
            $amount_payment = !empty($total_payments->total_payments) ?  $total_payments->total_payments : 0;
            $amount_solde = $amount_due - $amount_payment;
            /*if(!empty($total_invoices->total_invoices) && !empty($total_payments->total_payments)){
                $amount_due = $total_invoices->total_invoices;
                $amount_payment = $total_payments->total_payments;


                //dd($total_invoices->total_invoices,$total_payments->total_payments,$soldes);

            }*/

            $client->amount_due = $amount_due;
            $client->amount_payment = $amount_payment;
            $client->amount_solde = $amount_solde;
            $client->save();

        }
    }




    /**
     * Met à jour les montants du client (amount_due, amount_solde) basés sur ses transactions
     *
     * @param Client $client
     * @param int $clientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAmountClient(Client $client, $clientId)
    {
        $clients = Client::whereHas('transactions') // Clients ayant au moins une transaction
        ->withSum(['transactions as total_invoices' => function ($query) {
            $query->where('type', 'invoice');
        }], 'amount')
            ->withSum(['transactions as total_payments' => function ($query) {
                $query->where('type', 'payment');
            }], 'amount')
            ->where('id', $clientId)->first();

        $amount_due = !empty($clients->total_invoices) ? $clients->total_invoices : 0;
        $amount_payment = !empty($clients->total_payments) ? $clients->total_payments : 0;
        $amount_solde = $amount_due - $amount_payment;

        // Récupérer la somme des montants de HistoriqueClientSolde pour ce client
        $historiqueSum = HistoriqueClientSolde::where('client_id', $clientId)
            ->sum('amount');

        // Calculer le nouveau solde en soustrayant amount_solde de la somme des historiques
        //$newAmountSolde = $amount_solde - $historiqueSum ;

        $client->amount_due = $amount_due;
        $client->amount_payment = $amount_payment;
        $client->amount_solde = $amount_solde;
        $client->save();

        // Enregistrer l'historique de rétablissement de la comptabilité
        AccountingHistory::create([
            'user_id' => Auth::id(),
            'client_id' => $client->id,
            'amount_due' => $amount_due,
            'amount_payment' => $amount_payment,
            'amount_solde' => $amount_payment,
            'notes' => 'Rétablissement de la comptabilité par ' . Auth::user()->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Montants du client mis à jour avec succès',
            'client' => $client
        ]);
    }

    /**
     * Affiche l'historique des rétablissements de comptabilité
     *
     * @return \Inertia\Response
     */
    public function accountingHistory()
    {
        $history = AccountingHistory::with(['user', 'client'])
            ->whereHas('client', function ($query) {
                $query->whereHas('invoices', function ($q) {
                    $q->whereNotNull('planche_bon_livraison_id');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return Inertia::render('Clients/AccountingHistory', [
            'history' => $history
        ]);
    }

    /**
     * Récupère l'historique des comptes soldés pour un client spécifique
     *
     * @param Client $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHistoriqueClientSolde(Client $client)
    {
        $historique = HistoriqueClientSolde::where('client_id', $client->id)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($historique);
    }

    /**
     * Récupère l'historique de comptabilité pour un client spécifique
     *
     * @param Client $client
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClientAccountingHistory(Client $client)
    {
        $history = AccountingHistory::with('user')
            ->where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($history);
    }

    private function getClientsWithBalances()
    {
        return Client::whereHas('transactions', function ($query) {
                $query->whereNotNull('planche_bon_livraison_id');
            })
            ->withSum(['transactions as total_invoices' => function ($query) {
                $query->where('type', 'invoice')->whereNotNull('planche_bon_livraison_id');
            }], 'amount')
            ->withSum(['transactions as total_payments' => function ($query) {
                $query->where('type', 'payment');
            }], 'amount')
            ->get()
            ->map(function ($client) {
                return [
                    'id'            => $client->id,
                    'name'          => $client->name,
                    'total_invoices'=> $client->total_invoices ?? 0,
                    'total_paid'    => $client->total_payments ?? 0,
                    'remaining_due' => $client->amount_solde,
                ];
            })
            ->filter(fn ($c) => $c['remaining_due'] != 0)
            ->sortByDesc('remaining_due')
            ->values();
    }

    private function getClientsWithZeroBalances()
    {
        return Client::whereHas('transactions', function ($query) {
                $query->whereNotNull('planche_bon_livraison_id');
            })
            ->withSum(['transactions as total_invoices' => function ($query) {
                $query->where('type', 'invoice')->whereNotNull('planche_bon_livraison_id');
            }], 'amount')
            ->withSum(['transactions as total_payments' => function ($query) {
                $query->where('type', 'payment');
            }], 'amount')
            ->get()
            ->map(function ($client) {
                return [
                    'id'            => $client->id,
                    'name'          => $client->name,
                    'total_invoices'=> $client->total_invoices ?? 0,
                    'total_paid'    => $client->total_payments ?? 0,
                    'remaining_due' => $client->amount_solde,
                ];
            })
            ->filter(fn ($c) => $c['remaining_due'] == 0)
            ->sortByDesc('total_invoices')
            ->values();
    }

    public function getClientAccounts()
    {
        return response()->json($this->getClientsWithBalances());
    }

    public function getSettledClientAccounts()
    {
        return response()->json($this->getClientsWithZeroBalances());
    }
}
