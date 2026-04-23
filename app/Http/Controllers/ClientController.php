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
use App\Services\ClientBalanceService;

class ClientController extends Controller
{
    // Vue principale pour l'interface
    public function index()
    {
        return Inertia::render('Clients/Index');
    }

    public function store(Request $request)
    {
        $slug = Str::slug($request->input('name', ''));

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($slug) {
                    if (Client::where('slug', $slug)->exists()) {
                        $fail('Un client avec un nom similaire existe deja.');
                    }
                },
            ],
            'phone'   => 'nullable|string|max:20',
            'email'   => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $client = Client::create($validated);

        return response()->json($client, 201);
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

        $query->withSum(['transactions as amount_due' => fn ($q) => $q->where('type', 'invoice')], 'amount')
              ->withSum(['transactions as amount_payment' => fn ($q) => $q->where('type', 'payment')], 'amount');

        $clients = $query->paginate(500);

        $clients->getCollection()->transform(function ($client) {
            $client->amount_due     = (float) ($client->amount_due ?? 0);
            $client->amount_payment = (float) ($client->amount_payment ?? 0);
            $client->amount_solde   = $client->amount_due - $client->amount_payment;
            return $client;
        });

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
                $totalInvoices = (float) ($client->total_invoices ?? 0);
                $totalPayments = (float) ($client->total_payments ?? 0);
                return [
                    'id'            => $client->id,
                    'name'          => $client->name,
                    'total_invoices'=> $totalInvoices,
                    'total_paid'    => $totalPayments,
                    'remaining_due' => $totalInvoices - $totalPayments,
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

    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Client deleted successfully']);
    }

    public function fetchInvoicesPaiement(Client $client)
    {
        $invoices = Invoice::where([
            'client_id' => $client->id
        ])->paginate(25);

        return response()->json($invoices);
    }

    public function showPortefeuille(Client $client)
    {
        return Inertia::render('Clients/Portefeuille', [
            'client' => $client,
        ]);
    }

    public function enregistrerPaiement(Request $request, Client $client)
    {
        // This method seems to be for registering payments
        // Consider using PaymentController::store instead
        return response()->json(['message' => 'Use PaymentController for payment processing']);
    }

    public function updateAmountClient(Request $request, Client $client, $clientId)
    {
        if ($client->id != $clientId) {
            return response()->json(['error' => 'Client ID mismatch'], 400);
        }

        $balances = app(ClientBalanceService::class)->sync($client);

        return response()->json([
            'message' => 'Comptabilite du client retablie avec succes.',
            'client' => $client->fresh(),
            'balances' => $balances,
        ]);
    }
}

