<?php

namespace App\Http\Controllers;

use App\Models\AccountingHistory;
use App\Models\Client;
use App\Models\HistoriqueClientSolde;
use App\Models\Invoice;
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
        // Uniquement les factures liées aux planches (planche_bon_livraison_id non null)
        $totalInvoice = Invoice::where('client_id', $client->id)
            ->whereNotNull('planche_bon_livraison_id')
            ->where('status', '!=', 'canceled')
            ->count();

        // Montant total facturé planches
        $totalDue = Transaction::where('client_id', $client->id)
            ->where('type', 'invoice')
            ->whereHas('invoice', fn($q) => $q->whereNotNull('planche_bon_livraison_id'))
            ->sum('amount');

        // Montant total payé (les paiements ne sont pas liés à un type de facture spécifique)
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
        // Uniquement les transactions planches :
        // - paiements (type = payment) : toujours inclus
        // - factures (type = invoice) : seulement si la facture liée concerne les planches
        $transactions = Transaction::with('invoice')
            ->where('client_id', $client->id)
            ->where(function ($q) {
                $q->where('type', 'payment')
                  ->orWhere(function ($q2) {
                      $q2->where('type', 'invoice')
                         ->whereHas('invoice', fn($q3) => $q3->whereNotNull('planche_bon_livraison_id'));
                  });
            })
            ->orderBy('transaction_date', 'asc')
            ->get();


        /**
         * Si vous souhaitez calculer un solde « cumulé » au fil des lignes,
         * vous pouvez le faire ici en construisant un tableau intermédiaire.
         */
        $runningBalance = 0;
        $data = [];

        foreach ($transactions as $transaction) {
            if ($transaction->type === 'invoice') {
                $runningBalance += $transaction->amount;
            } else {
                // type = payment
                $runningBalance -= $transaction->amount;
            }

            $data[] = [
                'id'       => $transaction->id,
                'date'       => $transaction->transaction_date,
                'invoice'    => $transaction->type === 'invoice' ? $transaction->amount : null,
                'payment'    => $transaction->type === 'payment' ? $transaction->amount : null,
                'cumul'      => $runningBalance,
                'facture'   => $transaction->invoice,
                'type'     => $transaction->type,
                'isSolde'  => (bool) $transaction->isSolde,
            ];
        }

        //dd($data);

        return response()->json($data);

    }

    public function geratePDF(Client $client)
    {
        // Même filtre planches que fetchTransactionPaiement
        $transactions = Transaction::with('invoice')
            ->where('client_id', $client->id)
            ->where(function ($q) {
                $q->where('type', 'payment')
                  ->orWhere(function ($q2) {
                      $q2->where('type', 'invoice')
                         ->whereHas('invoice', fn($q3) => $q3->whereNotNull('planche_bon_livraison_id'));
                  });
            })
            ->orderBy('transaction_date', 'asc')
            ->get();


        /**
         * Si vous souhaitez calculer un solde « cumulé » au fil des lignes,
         * vous pouvez le faire ici en construisant un tableau intermédiaire.
         */
        $runningBalance = 0;
        $data = [];

        foreach ($transactions as $transaction) {
            if ($transaction->type === 'invoice') {
                $runningBalance += $transaction->amount;
            } else {
                // type = payment
                $runningBalance -= $transaction->amount;
            }

            $data[] = [
                'date'       => $transaction->transaction_date,
                'invoice'    => $transaction->type === 'invoice' ? $transaction->amount : null,
                'payment'    => $transaction->type === 'payment' ? $transaction->amount : null,
                'cumul'      => $runningBalance,
                'facture'   => $transaction->invoice,
                'isSolde'    => (bool) $transaction->isSolde,
            ];
        }

        //dd($data);

        // Générer le PDF
        $pdf = Pdf::loadView('pdf.facture_client', compact('data','client'));

        return $pdf->stream('historique_caisse.pdf');

    }

    public function fetchInvoicesPaiement(Client $client)
    {
        $invoices = InvoicePayment::with('invoice')->whereHas('invoice',function($q) use($client){
            $q->where(['client_id'=>$client->id]);
        })->paginate(25);

        return response()->json($invoices);

    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $client = Client::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        return response()->json(['message' => 'Client ajouté avec succès.', 'client' => $client]);
    }
    public function destroy(Client $client)
    {

        $client->delete();

        return response()->json(['message' => 'Client supprimé avec succès.']);
    }

    public function showPortefeuille(Client $client)
    {
        $client->load(['invoices' => function ($query) {
            $query->where('status', '!=', 'canceled')->orderBy('date');
        }]);

        // Calculer le total à payer
        $totalAPayer = $client->invoices->sum(fn($invoice) => $invoice->total_price - $invoice->montant_solde);

        return Inertia::render('Clients/Portefeuille', [
            'client' => $client,
            'totalAPayer' => $totalAPayer
        ]);
    }

    public function enregistrerPaiement(Request $request, Client $client)
    {
        $request->validate([
            'montant' => 'required|numeric|min:1',
        ]);

        $montantPaye = $request->montant;

        // Vérifier si le client a un crédit disponible
        if ($client->credit_disponible > 0) {
            $creditUtilise = min($client->credit_disponible, $montantPaye);
            $montantPaye -= $creditUtilise;
            $client->update([
                'credit_disponible' => $client->credit_disponible - $creditUtilise
            ]);
        }

        // Répartir le paiement sur les factures
        $factures = $client->invoices()->where('status', '!=', 'canceled')->orderBy('date')->get();

        foreach ($factures as $facture) {
            $resteAFacturer = $facture->total_price - $facture->montant_solde;

            if ($montantPaye <= 0) {
                break;
            }

            if ($montantPaye >= $resteAFacturer) {
                $facture->update([
                    'montant_solde' => $facture->total_price,
                    'status' => 'validated'
                ]);
                $montantPaye -= $resteAFacturer;
            } else {
                $facture->update([
                    'montant_solde' => $facture->montant_solde + $montantPaye
                ]);
                $montantPaye = 0;
            }
        }

        // Si un reste après paiement, l'ajouter en crédit client
        if ($montantPaye > 0) {
            $client->update([
                'credit_disponible' => $client->credit_disponible + $montantPaye
            ]);
        }

        return redirect()->route('clients.portefeuille', $client->id);
    }



    /**
     * Récupère les comptes clients avec des soldes dus
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function getClientsWithBalances()
    {
        return Client::whereHas('invoices', function ($query) {
                $query->whereNotNull('planche_bon_livraison_id');
            })
            ->withSum(['transactions as total_invoices' => function ($query) {
                $query->where('type', 'invoice');
            }], 'amount')
            ->withSum(['transactions as total_payments' => function ($query) {
                $query->where('type', 'payment');
            }], 'amount')
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'uuid' => $client->uuid,
                    'name' => $client->name,
                    'total_invoices' => $client->total_invoices ?? 0,
                    'total_paid' => $client->total_payments ?? 0,
                    'remaining_due' => $client->amount_solde, // Utilisation de l'attribut balance
                ];
            })
            ->filter(function ($client) {
                return $client['remaining_due'] != 0; // Exclure les clients sans dette
            })
            ->sortByDesc('remaining_due') // Trier du plus grand au plus petit
            ->values(); // Réindexer les clés du tableau
    }

    /**
     * Récupère les comptes clients avec des soldes à zéro (comptes soldés)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function getClientsWithZeroBalances()
    {
        return Client::whereHas('invoices', function ($query) {
                $query->whereNotNull('planche_bon_livraison_id');
            })
            ->withSum(['transactions as total_invoices' => function ($query) {
                $query->where('type', 'invoice');
            }], 'amount')
            ->withSum(['transactions as total_payments' => function ($query) {
                $query->where('type', 'payment');
            }], 'amount')
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'uuid' => $client->uuid,
                    'name' => $client->name,
                    'total_invoices' => $client->total_invoices ?? 0,
                    'total_paid' => $client->total_payments ?? 0,
                    'remaining_due' => $client->amount_solde, // Utilisation de l'attribut balance
                ];
            })
            ->filter(function ($client) {
                return $client['remaining_due'] == 0; // Inclure uniquement les clients avec solde à zéro
            })
            ->sortByDesc('total_invoices') // Trier par montant total facturé
            ->values(); // Réindexer les clés du tableau
    }

    /**
     * Retourne les comptes clients avec des soldes dus au format JSON
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClientAccounts()
    {
        $filteredClients = $this->getClientsWithBalances();
        return response()->json($filteredClients);
    }

    /**
     * Retourne les comptes clients soldés (solde à zéro) au format JSON
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettledClientAccounts()
    {
        $settledClients = $this->getClientsWithZeroBalances();
        return response()->json($settledClients);
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
}



