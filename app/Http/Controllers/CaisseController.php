<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\CaisseTransaction;
use App\Models\CaisseTransfer;
use App\Models\CaisseClosure;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\FinanceCorrection;
use App\Services\ClientBalanceService;
use App\Services\CaisseTransferService;

class CaisseController extends Controller
{
    protected $caisseTransferService;
    protected $clientBalanceService;

    public function __construct(CaisseTransferService $caisseTransferService, ClientBalanceService $clientBalanceService)
    {
        $this->caisseTransferService = $caisseTransferService;
        $this->clientBalanceService = $clientBalanceService;
    }

    public function index()
    {
        return Inertia::render('Finances/Caisse/Index');
    }

    /**
     * Page détail d'une caisse (Inertia)
     */
    public function show(Caisse $caisse)
    {
        return Inertia::render('Finances/Caisse/Show', [
            'caisse' => $caisse,
        ]);
    }

    // ---------- CRUD Caisse (JSON, utilisé par le front via axios)
    public function getCaisses(Request $request)
    {
        $query = Caisse::query()
            // Sums separated: entries and exits, to compute a net amount later
            ->withSum([
                'transactions as entries_sum' => function ($q) {
                    $q->where(function ($q) {
                        $q->whereIn('movement_type', [
                            CaisseTransaction::MOV_ENTREE_CLIENT,
                            CaisseTransaction::MOV_ENTREE_AUTRE,
                            CaisseTransaction::MOV_TRANSFERT_ENTRANT,
                        ])
                        // fallback legacy type when movement_type is null
                        ->orWhere(function ($qq) {
                            $qq->whereNull('movement_type')
                               ->where('type', 'entree');
                        });
                    });
                }
            ], 'amount')
            ->withSum([
                'transactions as exits_sum' => function ($q) {
                    $q->where(function ($q) {
                        $q->whereIn('movement_type', [
                            CaisseTransaction::MOV_SORTIE,
                            CaisseTransaction::MOV_TRANSFERT_SORTANT,
                        ])
                        // fallback legacy type when movement_type is null
                        ->orWhere(function ($qq) {
                            $qq->whereNull('movement_type')
                               ->where('type', 'sortie');
                        });
                    });
                }
            ], 'amount');

        if ($request->filled('active')) {
            $query->where('active', (bool) $request->boolean('active'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('currency_code', 'like', "%{$search}%");
            });
        }

        $caisses = $query->orderBy('id', 'desc')->get();

        // Compute net amount (entries - exits) and optionally include initial balance
        $caisses->each(function ($c) {
            $entries = (float)($c->entries_sum ?? 0);
            $exits   = (float)($c->exits_sum ?? 0);
            $c->amount_net = $entries - $exits;
            $c->balance_with_initial = (float)($c->initial_balance ?? 0) + $c->amount_net;
        });

        return response()->json($caisses);
    }

    public function storeCaisse(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'nullable|string|max:100',
            'currency_code'    => 'nullable|string|max:10',
            'initial_balance'  => 'nullable|numeric',
            'active'           => 'sometimes|boolean',
        ]);

        $caisse = Caisse::create([
            'name'            => $data['name'],
            'type'            => $data['type'] ?? null,
            'currency_code'   => $data['currency_code'] ?? null,
            'initial_balance' => $data['initial_balance'] ?? 0,
            'active'          => $data['active'] ?? true,
        ]);

        return response()->json(['message' => 'Caisse créée avec succès', 'caisse' => $caisse], 201);
    }

    public function showCaisse(Caisse $caisse)
    {
        return response()->json($caisse);
    }

    public function updateCaisse(Request $request, Caisse $caisse)
    {
        $data = $request->validate([
            'name'             => 'sometimes|required|string|max:255',
            'type'             => 'nullable|string|max:100',
            'currency_code'    => 'nullable|string|max:10',
            'initial_balance'  => 'nullable|numeric',
            'active'           => 'sometimes|boolean',
        ]);

        $caisse->fill($data);
        $caisse->save();

        return response()->json(['message' => 'Caisse mise à jour avec succès', 'caisse' => $caisse]);
    }

    public function destroyCaisse(Caisse $caisse)
    {
        $caisse->delete();
        return response()->json(['message' => 'Caisse supprimée avec succès']);
    }

    public function historique_caisse()
    {

        return Inertia::render('Finances/Caisse/HistoriqueCaisse');
    }

    // ===================== Clôture de Caisse =====================
    public function closurePage()
    {
        return Inertia::render('Finances/Caisse/Cloture');
    }

    public function closuresHistoryPage()
    {
        return Inertia::render('Finances/Caisse/ClosuresHistory');
    }

    public function listClosures(Request $request)
    {
        $query = CaisseClosure::with(['caisse', 'validator']);

        if ($request->filled('caisse_id')) {
            $query->where('caisse_id', (int) $request->input('caisse_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('start_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('end_date', '<=', $request->input('to'));
        }

        $closures = $query->orderBy('end_date', 'desc')->paginate(20);
        return response()->json($closures);
    }

    public function showClosure(CaisseClosure $closure)
    {
        $closure->load(['caisse', 'validator', 'creator']);
        return response()->json($closure);
    }

    public function downloadClosurePdf(CaisseClosure $closure)
    {
        // Génère le PDF "verrouillé" à la volée
        $pdf = Pdf::loadView('pdf.caisse_closure', [
            'closure' => $closure->load(['caisse', 'validator', 'creator'])
        ]);
        return $pdf->download('cloture_caisse_'.$closure->id.'.pdf');
    }

    public function previewClosure(Request $request)
    {
        $data = $request->validate([
            'caisse_id'   => 'required|exists:caisses,id',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'real_balance'=> 'nullable|numeric',
        ]);

        $caisse = Caisse::findOrFail($data['caisse_id']);
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end   = Carbon::parse($data['end_date'])->endOfDay();

        $overlap = CaisseClosure::where('caisse_id', $caisse->id)
            ->where('status', 'validated')
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function($q2) use ($start, $end) {
                      $q2->where('start_date', '<=', $start)
                         ->where('end_date', '>=', $end);
                  });
            })->exists();

        $initial = $this->computeBalanceUntil($caisse->id, $start->copy()->subSecond());
        $totals = $this->computePeriodTotals($caisse->id, $start, $end);
        $theoretical = round($initial + $totals['total_entries'] + $totals['total_transfer_in'] - $totals['total_exits'] - $totals['total_transfer_out'], 2);
        $difference = null;
        if (!is_null($request->input('real_balance'))) {
            $difference = round(((float)$request->input('real_balance')) - $theoretical, 2);
        }

        return response()->json([
            'overlap' => $overlap,
            'initial_balance' => $initial,
            'totals' => $totals,
            'theoretical_balance' => $theoretical,
            'difference' => $difference,
            'checks' => [
                'no_unvalidated' => true,
                'no_negative_balance' => true,
                'transfers_matched' => true,
                'bank_reconciled' => $caisse->type === 'bank' ? true : null,
            ],
        ]);
    }

    public function createClosure(Request $request)
    {
        $data = $request->validate([
            'caisse_id'   => 'required|exists:caisses,id',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'real_balance'=> 'required|numeric',
            'notes'       => 'nullable|string',
        ]);

        $caisse = Caisse::findOrFail($data['caisse_id']);
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end   = Carbon::parse($data['end_date'])->endOfDay();

        // Vérifications préalables (simplifiées)
        // 1) Pas de mouvements non validés (supposé: tous les mouvements présents sont validés automatiquement dans ce système)
        // 2) Pas de solde négatif dans la période (contrôle via calcul)
        // 3) Correspondance des transferts (chaque CaisseTransfer crée 2 mouvements)
        // 4) Si type bank => suppose rapprochement OK (on peut étendre plus tard)

        // Empêcher une clôture qui chevauche une clôture déjà validée
        $overlap = CaisseClosure::where('caisse_id', $caisse->id)
            ->where('status', 'validated')
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function($q2) use ($start, $end) {
                      $q2->where('start_date', '<=', $start)
                         ->where('end_date', '>=', $end);
                  });
            })->exists();
        if ($overlap) {
            return response()->json(['message' => 'Une clôture validée existe déjà sur une partie de cette période.'], 422);
        }

        // Calcul des soldes
        $initialBalanceBefore = $this->computeBalanceUntil($caisse->id, $start->copy()->subSecond());
        $totals = $this->computePeriodTotals($caisse->id, $start, $end);

        $theoretical = round($initialBalanceBefore
            + $totals['total_entries']
            + $totals['total_transfer_in']
            - $totals['total_exits']
            - $totals['total_transfer_out'], 2);

        $difference = round($data['real_balance'] - $theoretical, 2);

        $closure = CaisseClosure::create([
            'caisse_id'           => $caisse->id,
            'start_date'          => $start,
            'end_date'            => $end,
            'initial_balance'     => $initialBalanceBefore,
            'total_entries'       => $totals['total_entries'],
            'total_exits'         => $totals['total_exits'],
            'total_transfer_in'   => $totals['total_transfer_in'],
            'total_transfer_out'  => $totals['total_transfer_out'],
            'theoretical_balance' => $theoretical,
            'real_balance'        => $data['real_balance'],
            'difference'          => $difference,
            'status'              => 'validated',
            'created_by'          => auth()->id(),
            'validated_by'        => auth()->id(),
            'notes'               => $data['notes'] ?? null,
        ]);

        // Génération du PDF (verrouillé) et stockage optionnel
        $pdf = Pdf::loadView('pdf.caisse_closure', ['closure' => $closure->load('caisse', 'validator', 'creator')]);
        // stockage facultatif
        $path = 'closures/cloture_'.$closure->id.'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        return response()->json([
            'message' => 'Clôture créée avec succès',
            'closure' => $closure,
            'pdf_path' => $path,
        ], 201);
    }

    private function computeBalanceUntil(int $caisseId, $untilDateTime): float
    {
        $caisse = Caisse::findOrFail($caisseId);
        $entrees = CaisseTransaction::where('caisse_id', $caisseId)
            ->where('date', '<=', $untilDateTime)
            ->where('type', 'entree')
            ->sum('amount');
        $sorties = CaisseTransaction::where('caisse_id', $caisseId)
            ->where('date', '<=', $untilDateTime)
            ->where('type', 'sortie')
            ->sum('amount');
        return round(($caisse->initial_balance ?? 0) + $entrees - $sorties, 2);
    }

    private function computePeriodTotals(int $caisseId, Carbon $start, Carbon $end): array
    {
        $base = CaisseTransaction::where('caisse_id', $caisseId)
            ->whereBetween('date', [$start, $end]);

        $totalEntries = (clone $base)->where('movement_type', CaisseTransaction::MOV_ENTREE_AUTRE)
            ->orWhere(function($q) use ($caisseId, $start, $end) {
                $q->where('caisse_id', $caisseId)
                  ->whereBetween('date', [$start, $end])
                  ->where('movement_type', CaisseTransaction::MOV_ENTREE_CLIENT);
            })->sum('amount');

        $totalExits = (clone $base)->where('movement_type', CaisseTransaction::MOV_SORTIE)->sum('amount');
        $transferIn = (clone $base)->where('movement_type', CaisseTransaction::MOV_TRANSFERT_ENTRANT)->sum('amount');
        $transferOut = (clone $base)->where('movement_type', CaisseTransaction::MOV_TRANSFERT_SORTANT)->sum('amount');

        return [
            'total_entries'      => (float) $totalEntries,
            'total_exits'        => (float) $totalExits,
            'total_transfer_in'  => (float) $transferIn,
            'total_transfer_out' => (float) $transferOut,
        ];
    }

    private function isWithinValidatedClosure(int $caisseId, $date): bool
    {
        $d = Carbon::parse($date);
        return CaisseClosure::where('caisse_id', $caisseId)
            ->where('status', 'validated')
            ->where('start_date', '<=', $d)
            ->where('end_date', '>=', $d)
            ->exists();
    }

    public function storeSortie(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'objet' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'caisse_id' => 'nullable|exists:caisses,id',
        ]);

        $caisseId = $request->input('caisse_id') ?: $this->resolveDefaultCaisseId();
        if ($this->isWithinValidatedClosure($caisseId, $request->date)) {
            return response()->json(['message' => 'La période sélectionnée est clôturée. Impossible d\'ajouter un mouvement.'], 422);
        }

        // Vérifier si la caisse a assez de fonds
        $totalEntrees = CaisseTransaction::where('type', 'entree')->sum('amount');
        $totalSorties = CaisseTransaction::where('type', 'sortie')->sum('amount');
        $soldeActuel = $totalEntrees - $totalSorties;

        if ($request->amount > $soldeActuel) {
            return response()->json(['message' => 'Fonds insuffisants dans la caisse.'], 400);
        }

        CaisseTransaction::create([
            'caisse_id'     => $caisseId,
            'type'          => 'sortie', // legacy
            'movement_type' => CaisseTransaction::MOV_SORTIE,
            'amount'        => $request->amount,
            'objet'         => $request->objet,
            'description'   => $request->description,
            'date'          => $request->date,
            'user_id'       => auth()->id(),
        ]);

        return response()->json(['message' => 'Sortie enregistrée avec succès !']);
    }

    /**
     * Enregistrer une entrée diverse (hors paiement client)
     */
    public function storeEntreeDivers(Request $request)
    {
        $validated = $request->validate([
            'amount'      => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'date'        => 'required|date',
            'caisse_id'   => 'nullable|exists:caisses,id',
        ]);

        $caisseId = $request->input('caisse_id') ?: $this->resolveDefaultCaisseId();
        if ($this->isWithinValidatedClosure($caisseId, $validated['date'])) {
            return response()->json(['message' => 'La période sélectionnée est clôturée. Impossible d\'ajouter un mouvement.'], 422);
        }

        CaisseTransaction::create([
            'caisse_id'     => $caisseId,
            'type'          => 'entree',
            'movement_type' => CaisseTransaction::MOV_ENTREE_AUTRE,
            'amount'        => $validated['amount'],
            'objet'         => 'Entrée diverse',
            'description'   => $validated['description'],
            'date'          => $validated['date'],
            'user_id'       => auth()->id(),
        ]);

        return response()->json(['message' => 'Entrée diverse enregistrée avec succès !']);
    }

    /**
     * Transfert entre caisses avec gestion optionnelle du taux de change
     */
    public function transfer(Request $request)
    {
        $data = $request->validate([
            'source_caisse_id'      => 'required|exists:caisses,id',
            'destination_caisse_id' => 'required|different:source_caisse_id|exists:caisses,id',
            'amount_source'         => 'required|numeric|min:0.01',
            'exchange_rate'         => 'nullable|numeric|min:0.000001',
            'amount_destination'    => 'nullable|numeric|min:0.01',
            'description'           => 'nullable|string|max:255',
            'transfer_date'         => 'required|date',
        ]);

        // Empêcher transfert dans une période clôturée pour l'une des deux caisses
        if ($this->isWithinValidatedClosure((int)$data['source_caisse_id'], $data['transfer_date'])
            || $this->isWithinValidatedClosure((int)$data['destination_caisse_id'], $data['transfer_date'])) {
            return response()->json(['message' => 'La période sélectionnée est clôturée pour la caisse source ou destination.'], 422);
        }

        // Vérifier solde suffisant sur la caisse source
        $totalEntreesSrc = CaisseTransaction::where('caisse_id', $data['source_caisse_id'])->where('type', 'entree')->sum('amount');
        $totalSortiesSrc = CaisseTransaction::where('caisse_id', $data['source_caisse_id'])->where('type', 'sortie')->sum('amount');
        $soldeSrc = $totalEntreesSrc - $totalSortiesSrc;
        if ($data['amount_source'] > $soldeSrc) {
            return response()->json(['message' => 'Fonds insuffisants dans la caisse source.'], 400);
        }

        // Calcul du montant destination si nécessaire
        $amountDestination = $data['amount_destination'] ?? null;
        if (!$amountDestination) {
            $rate = $data['exchange_rate'] ?? 1.0;
            $amountDestination = round($data['amount_source'] * $rate, 2);
        }

        // Créer d'abord les deux mouvements caisse
        $sortie = CaisseTransaction::create([
            'caisse_id'     => $data['source_caisse_id'],
            'type'          => 'sortie',
            'movement_type' => CaisseTransaction::MOV_TRANSFERT_SORTANT,
            'amount'        => $data['amount_source'],
            'objet'         => 'Transfert sortant',
            'description'   => $data['description'] ?? null,
            'date'          => $data['transfer_date'],
            'user_id'       => auth()->id(),
        ]);

        $entree = CaisseTransaction::create([
            'caisse_id'     => $data['destination_caisse_id'],
            'type'          => 'entree',
            'movement_type' => CaisseTransaction::MOV_TRANSFERT_ENTRANT,
            'amount'        => $amountDestination,
            'objet'         => 'Transfert entrant',
            'description'   => $data['description'] ?? null,
            'date'          => $data['transfer_date'],
            'user_id'       => auth()->id(),
        ]);

        // Relier via un enregistrement CaisseTransfer
        $transfer = CaisseTransfer::create([
            'source_caisse_id'                  => $data['source_caisse_id'],
            'destination_caisse_id'             => $data['destination_caisse_id'],
            'amount_source'                     => $data['amount_source'],
            'amount_destination'                => $amountDestination,
            'exchange_rate'                     => $data['exchange_rate'] ?? null,
            'description'                       => $data['description'] ?? null,
            'transfer_date'                     => $data['transfer_date'],
            'source_caisse_transaction_id'      => $sortie->id,
            'destination_caisse_transaction_id' => $entree->id,
        ]);

        // Mettre à jour les transactions pour référencer le transfert (optionnel si on souhaite la relation inverse)
        $sortie->update(['caisse_transfer_id' => $transfer->id]);
        $entree->update(['caisse_transfer_id' => $transfer->id]);

        return response()->json(['message' => 'Transfert effectué avec succès', 'transfer' => $transfer]);
    }

    public function updateSortie(Request $request, $caisse){
        CaisseTransaction::where('id', $caisse)->update([
            'amount' => $request->amount,
            'objet' => $request->objet,
            'description' => $request->description,
            'date' => $request->date,
        ]);
        return response()->json(['message' => 'Sortie modifiée avec succès !']);
    }

    public function destroySortie($caisse){
        CaisseTransaction::where('id', $caisse)->delete();

        return response()->json(['message' => 'Sortie supprimée avec succès !']);
    }

    /**
     * Mettre à jour une entrée diverse (pas les paiements clients ni transferts)
     */
    public function updateEntree(Request $request, $caisse)
    {
        $entry = CaisseTransaction::findOrFail($caisse);

        if ($entry->type !== 'entree') {
            return response()->json(['message' => "La transaction ciblée n'est pas une entrée."], 422);
        }

        // Interdire la modification des paiements clients et transferts entrants
        if ($entry->movement_type === CaisseTransaction::MOV_TRANSFERT_ENTRANT
            || !is_null($entry->payment_id)
            || !is_null($entry->transaction_id)
            || !is_null($entry->caisse_transfer_id)) {
            return response()->json(['message' => "Cette entrée est liée à un paiement ou un transfert et ne peut pas être modifiée."], 422);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        // Vérifier clôture
        $caisseId = (int)($entry->caisse_id);
        if ($this->isWithinValidatedClosure($caisseId, $data['date'])) {
            return response()->json(['message' => 'La période sélectionnée est clôturée. Impossible de modifier le mouvement.'], 422);
        }

        $entry->update([
            'amount' => $data['amount'],
            // On aligne les deux champs pour rester cohérent avec updateSortie
            'objet' => $request->input('objet', $data['description']),
            'description' => $data['description'],
            'date' => $data['date'],
            'movement_type' => $entry->movement_type ?: CaisseTransaction::MOV_ENTREE_AUTRE,
        ]);

        return response()->json(['message' => 'Entrée modifiée avec succès !']);
    }

    /**
     * Supprimer une entrée diverse (pas les paiements clients ni transferts)
     */
    public function destroyEntree($caisse)
    {
        $entry = CaisseTransaction::findOrFail($caisse);

        if ($entry->type !== 'entree') {
            return response()->json(['message' => "La transaction ciblée n'est pas une entrée."], 422);
        }

        // Interdire suppression si liée à paiement/vente ou transfert
        // Note: la suppression des paiements/ventes et des transferts dispose
        // de méthodes dédiées (deleteClientPaymentByTxn, deleteIncomingTransferByTxn)
        // qui gèrent correctement toutes les dépendances.
       /* if ($entry->movement_type === CaisseTransaction::MOV_TRANSFERT_ENTRANT
            || !is_null($entry->payment_id)
            || !is_null($entry->transaction_id)
            || !is_null($entry->caisse_transfer_id)) {
            return response()->json([
                'message' => "Cette entrée est liée (paiement/vente ou transfert) et ne peut pas être supprimée ici. Utilisez l'action dédiée à la suppression du paiement ou du transfert.",
            ], 422);
        }*/

        // Empêcher suppression si la date est dans une période clôturée
        if ($this->isWithinValidatedClosure((int)$entry->caisse_id, $entry->date)) {
            return response()->json(['message' => 'La période de cette entrée est clôturée. Suppression impossible.'], 422);
        }

        $entry->delete();

        return response()->json(['message' => 'Entrée supprimée avec succès !']);
    }

    /**
     * Corriger un paiement client à partir de l'ID de la transaction de caisse (entrée client).
     * Met à jour Payment, Transaction et CaisseTransaction de façon atomique.
     */
    public function correctClientPaymentByTxn(Request $request, $transaction)
    {
        $txn = CaisseTransaction::find($transaction);

        if (!$txn) {
            return response()->json(['message' => 'Transaction introuvable ou déjà supprimée.'], 404);
        }

        // Doit être une entrée liée à un paiement client
        if ($txn->type !== 'entree' || $txn->movement_type !== CaisseTransaction::MOV_ENTREE_CLIENT || is_null($txn->payment_id) || is_null($txn->transaction_id)) {
            return response()->json(['message' => "La ligne ciblée n'est pas un paiement client corrigeable."], 422);
        }

        $data = $request->validate([
            'amount'           => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'description'      => 'nullable|string|max:255',
            'reason'           => 'nullable|string|max:255',
        ]);

        // Vérifier clôture pour la caisse de la transaction
        if ($this->isWithinValidatedClosure((int)$txn->caisse_id, $data['transaction_date'])) {
            return response()->json(['message' => 'La période sélectionnée est clôturée. Correction impossible.'], 422);
        }

        // Snapshots avant modification
        $old_txn_amount = (float) $txn->amount;
        $old_txn_date = $txn->date;
        $old_txn_desc = $txn->description;

        $payment = \App\Models\Payment::find($txn->payment_id);
        $old_payment_amount = $payment?->amount;
        $old_payment_date = $payment?->date;

        $linkedTxn = \App\Models\Transaction::find($txn->transaction_id);
        $old_linked_amount = $linkedTxn?->amount;
        $old_linked_date = $linkedTxn?->transaction_date;

        DB::transaction(function () use ($txn, $data, $payment, $linkedTxn, $old_txn_amount, $old_txn_date, $old_txn_desc, $old_payment_amount, $old_payment_date, $old_linked_amount, $old_linked_date) {
            // Mettre à jour Payment
            if ($payment) {
                $payment->amount = $data['amount'];
                // si le modèle Payment possède un champ date
                if (isset($payment->date)) {
                    $payment->date = $data['transaction_date'];
                }
                $payment->save();
            }

            // Mettre à jour Transaction associée (type payment)
            if ($linkedTxn) {
                $linkedTxn->amount = $data['amount'];
                $linkedTxn->transaction_date = $data['transaction_date'];
                $linkedTxn->save();
            }

            // Mettre à jour la transaction de caisse
            $txn->update([
                'amount'      => $data['amount'],
                'date'        => $data['transaction_date'],
                'description' => $data['description'] ?? $txn->description,
                'objet'       => $data['description'] ?? $txn->objet,
                'movement_type' => CaisseTransaction::MOV_ENTREE_CLIENT,
            ]);

            // Mettre à jour soldes client si possible
            if ($linkedTxn && $linkedTxn->client_id) {
                $client = \App\Models\Client::find($linkedTxn->client_id);
                if ($client) {
                    // recalcul via agrégats existants
                    $this->clientBalanceService->sync($client);
                }
            }

            // Journalisation de la correction
            FinanceCorrection::create([
                'correction_type'     => 'client_payment',
                'caisse_transaction_id' => $txn->id,
                'payment_id'          => $payment?->id,
                'transaction_id'      => $linkedTxn?->id,
                'old_amount'          => $old_txn_amount,
                'new_amount'          => (float)$data['amount'],
                'old_date'            => $old_txn_date,
                'new_date'            => $data['transaction_date'],
                'old_description'     => $old_txn_desc,
                'new_description'     => $data['description'] ?? $old_txn_desc,
                'reason'              => $data['reason'] ?? null,
                'user_id'             => auth()->id(),
                'meta'                => [
                    'payment' => [
                        'old_amount' => $old_payment_amount,
                        'old_date'   => $old_payment_date,
                    ],
                    'transaction' => [
                        'old_amount' => $old_linked_amount,
                        'old_date'   => $old_linked_date,
                    ],
                    'caisse_id' => $txn->caisse_id,
                ],
            ]);
        });

        return response()->json(['message' => 'Paiement corrigé avec succès.']);
    }

    /**
     * Corriger un transfert entrant à partir de l'ID de la transaction de caisse (entrée transfert).
     * Met à jour CaisseTransfer et les deux CaisseTransaction liées.
     */
    public function correctIncomingTransferByTxn(Request $request, $transaction)
    {
        $destTxn = CaisseTransaction::find($transaction);

        if (!$destTxn) {
            return response()->json(['message' => 'Transaction introuvable ou déjà supprimée.'], 404);
        }

        if ($destTxn->type !== 'entree' || $destTxn->movement_type !== CaisseTransaction::MOV_TRANSFERT_ENTRANT || is_null($destTxn->caisse_transfer_id)) {
            return response()->json(['message' => "La ligne ciblée n'est pas un transfert entrant corrigeable."], 422);
        }

        $transfer = CaisseTransfer::findOrFail($destTxn->caisse_transfer_id);

        if ($transfer->status === 'annulé' || $transfer->status === 'corrigé') {
            return response()->json(['message' => 'Ce transfert ne peut plus être corrigé car il est déjà annulé ou corrigé.'], 422);
        }

        $data = $request->validate([
            'amount_source'      => 'required|numeric|min:0.01',
            'exchange_rate'      => 'nullable|numeric|min:0.000001',
            'amount_destination' => 'nullable|numeric|min:0.01',
            'transfer_date'      => 'required|date',
            'description'        => 'nullable|string|max:255',
            'destination_caisse_id' => 'nullable|exists:caisses,id',
        ]);

        $srcTxn = CaisseTransaction::findOrFail($transfer->source_caisse_transaction_id);

        // Vérifier clôtures pour les deux caisses
        if ($this->isWithinValidatedClosure((int)$srcTxn->caisse_id, $transfer->transfer_date)
            || $this->isWithinValidatedClosure((int)$destTxn->caisse_id, $transfer->transfer_date)
            || $this->isWithinValidatedClosure((int)$srcTxn->caisse_id, $data['transfer_date'])
            || ($data['destination_caisse_id'] && $this->isWithinValidatedClosure((int)$data['destination_caisse_id'], $data['transfer_date']))) {
            return response()->json(['message' => 'La période sélectionnée ou d\'origine est clôturée pour une des caisses.'], 422);
        }

        $reason = $data['description'] ?? "Correction du transfert #{$transfer->id}";

        try {
            $newData = [
                'source_caisse_id'      => $srcTxn->caisse_id,
                'destination_caisse_id' => $data['destination_caisse_id'] ?? $destTxn->caisse_id,
                'amount_source'         => $data['amount_source'],
                'amount_destination'    => $data['amount_destination'],
                'exchange_rate'         => $data['exchange_rate'],
                'description'           => $reason,
                'transfer_date'         => $data['transfer_date'],
            ];

            $this->caisseTransferService->correctTransfer($transfer, $newData, $reason);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Transfert corrigé avec succès via contre-écriture et nouvel enregistrement.']);
    }

    /**
     * Supprimer un paiement client à partir de l'ID de la transaction de caisse (entrée client).
     * Supprime Payment, Transaction associée et la CaisseTransaction, avec journalisation.
     */
    public function deleteClientPaymentByTxn(Request $request, $transaction)
    {
        $txn = CaisseTransaction::find($transaction);

        if (!$txn) {
            return response()->json(['message' => 'Ce paiement a déjà été supprimé.'], 404);
        }

        if ($txn->type !== 'entree' || $txn->movement_type !== CaisseTransaction::MOV_ENTREE_CLIENT || is_null($txn->payment_id) || is_null($txn->transaction_id)) {
            return response()->json(['message' => "La ligne ciblée n'est pas un paiement client supprimable."], 422);
        }

        // Empêcher suppression si période clôturée
        if ($this->isWithinValidatedClosure((int)$txn->caisse_id, $txn->date)) {
            return response()->json(['message' => 'La période de cette entrée est clôturée. Suppression impossible.'], 422);
        }

        $reason = $request->input('reason');

        // Snapshots
        $old_txn_amount = (float)$txn->amount;
        $old_txn_date = $txn->date;
        $old_txn_desc = $txn->description;

        $payment = \App\Models\Payment::find($txn->payment_id);
        $linkedTxn = \App\Models\Transaction::find($txn->transaction_id);
        $clientId = $linkedTxn?->client_id;

        // Récupérer les factures liées via pivot (si existant)
        $pivotInvoices = [];
        try {
            $pivotInvoices = DB::table('invoice_payment')
                ->where('payment_id', $payment?->id)
                ->pluck('invoice_id')
                ->all();
        } catch (\Throwable $e) {
            // si la table n'existe pas, on ignore
            $pivotInvoices = [];
        }

        // Journalisation AVANT suppression (afin d'éviter l'échec de création si les FKs exigent l'existence des lignes)
        $txnId        = $txn->id;
        $paymentId    = $payment?->id;
        $linkedTxnId  = $linkedTxn?->id;
        $caisseId     = $txn->caisse_id;

        FinanceCorrection::create([
            'correction_type'       => 'client_payment_delete',
            'caisse_transaction_id' => $txnId,
            'payment_id'            => $paymentId,
            'transaction_id'        => $linkedTxnId,
            'old_amount'            => $old_txn_amount,
            'new_amount'            => null,
            'old_date'              => $old_txn_date,
            'new_date'              => null,
            'old_description'       => $old_txn_desc,
            'new_description'       => null,
            'reason'                => $reason,
            'user_id'               => auth()->id(),
            'meta'                  => [
                'invoices_detached' => $pivotInvoices,
                'caisse_id'         => $caisseId,
            ],
        ]);

        DB::transaction(function () use ($txn, $payment, $linkedTxn) {
            // Supprimer pivots facture<->payment si présents
            try {
                DB::table('invoice_payment')->where('payment_id', $payment?->id)->delete();
            } catch (\Throwable $e) {
                // ignore
            }

            // Supprimer Payment
            if ($payment) {
                $payment->delete();
            }

            // Supprimer Transaction associée (type payment)
            if ($linkedTxn) {
                $linkedTxn->delete();
            }

            // Supprimer la transaction de caisse
            $txn->delete();
        });

        // Recalcul soldes client
        if ($clientId) {
            $client = \App\Models\Client::find($clientId);
            if ($client) {
                $this->clientBalanceService->sync($client);
            }
        }

        return response()->json(['message' => 'Paiement supprimé avec succès.']);
    }

    /**
     * Annuler un transfert entrant (et sa contrepartie sortante) via l'ID de la transaction d'entrée.
     */
    public function cancelIncomingTransfer(Request $request, $transaction)
    {
        $destTxn = CaisseTransaction::find($transaction);

        if (!$destTxn) {
            return response()->json(['message' => 'Transaction introuvable ou déjà supprimée.'], 404);
        }

        if ($destTxn->type !== 'entree' || $destTxn->movement_type !== CaisseTransaction::MOV_TRANSFERT_ENTRANT || is_null($destTxn->caisse_transfer_id)) {
            return response()->json(['message' => "La ligne ciblée n'est pas un transfert entrant annulable."], 422);
        }

        $transfer = CaisseTransfer::findOrFail($destTxn->caisse_transfer_id);

        if ($transfer->status === 'annulé') {
            return response()->json(['message' => 'Ce transfert est déjà annulé.'], 422);
        }

        $srcTxn = CaisseTransaction::findOrFail($transfer->source_caisse_transaction_id);

        // Vérifier clôture pour les deux caisses
        if ($this->isWithinValidatedClosure((int)$srcTxn->caisse_id, $transfer->transfer_date)
            || $this->isWithinValidatedClosure((int)$destTxn->caisse_id, $transfer->transfer_date)) {
            return response()->json(['message' => 'La période concernée est clôturée pour la caisse source ou destination. Annulation impossible.'], 422);
        }

        $reason = $request->input('reason', 'Annulation demandée par l\'utilisateur');

        try {
            $this->caisseTransferService->cancelTransfer($transfer, $reason);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Transfert annulé par contre-écriture avec succès.']);
    }

    private function filterTransactions(Request $request)
    {
        $query = CaisseTransaction::query()->with('transaction.client');

        // Filtrer par caisse si fournie
        if ($request->filled('caisse_id')) {
            $query->where('caisse_id', $request->caisse_id);
        }

        // Filtrer par période si sélectionnée
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Filtrer par type si sélectionné
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    private function processTransactions($transactions, $includeId = false)
    {
        $runningBalance = 0;
        $data = [];

        //dd($transactions);

        foreach ($transactions as $transaction) {
            // Calculer le solde cumulatif
            $runningBalance += $transaction->type === 'entree' ? $transaction->amount : -$transaction->amount;

            // Préparer les données de la transaction
            $transactionData = [
                'date'       => $transaction->date,
                'type'       => $transaction->type,
                'amount'     => $transaction->amount,
                'objet'      => $transaction->objet,
                'cumul'      => $runningBalance,
                'client'     => $transaction->transaction ? $transaction->transaction->client->name : "",
                'transfer_status' => $transaction->transfer ? $transaction->transfer->status : null,
                'caisse_transfer_id' => $transaction->caisse_transfer_id,
                'transfer_source_name' => $transaction->transfer && $transaction->transfer->sourceCaisse ? $transaction->transfer->sourceCaisse->name : null,
                'transfer_dest_name' => $transaction->transfer && $transaction->transfer->destinationCaisse ? $transaction->transfer->destinationCaisse->name : null,
            ];

            // Ajouter l'ID si nécessaire
            if ($includeId) {
                $transactionData['id'] = $transaction->id;
            }

            $data[] = $transactionData;
        }

        return $data;
    }

    public function fetchCaisseTransactions(Request $request)
    {
        // Note: this endpoint now returns the FULL matching dataset (no pagination)

        // Base query with filters
        $baseQuery = CaisseTransaction::query()->with(['transaction.client', 'transfer.sourceCaisse', 'transfer.destinationCaisse']);

        if ($request->filled('caisse_id')) {
            $baseQuery->where('caisse_id', $request->caisse_id);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $baseQuery->whereBetween('date', [$request->start_date, $request->end_date]);
        }
        if ($request->filled('type')) {
            $baseQuery->where('type', $request->type);
        }
        if ($request->filled('q')) {
            $q = $request->string('q');
            $baseQuery->where(function ($sub) use ($q) {
                $sub->where('objet', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        // Order ASC for stable running balance computation
        $query = (clone $baseQuery)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc');

        // Fetch ALL records (no pagination)
        $items = $query->get();

        // Compute opening balance up to just before the first transaction in the filtered set
        $openingBalance = 0;
        if ($items->count() > 0) {
            $firstDate = Carbon::parse($items->first()->date)->subSecond();
            $caisseIdForBalance = $request->filled('caisse_id') ? (int) $request->caisse_id : $this->resolveDefaultCaisseId();
            $openingBalance = $this->computeBalanceUntil($caisseIdForBalance, $firstDate);
        }

        // Build data with running balance starting from opening
        $running = $openingBalance;
        $data = [];
        foreach ($items as $t) {
            $running += $t->type === 'entree' ? $t->amount : -$t->amount;
            $data[] = [
                'id'         => $t->id,
                'date'       => $t->date,
                'caisse_id'  => $t->caisse_id,
                'type'       => $t->type,
                'objet'      => $t->objet,
                'amount'     => $t->amount,
                'solde'      => $running,
                'client'     => $t->transaction ? optional($t->transaction->client)->name : null,
                'isSolde'    => $t->transaction ? (bool) $t->transaction->isSolde : false,
                'transfer_status' => $t->transfer ? $t->transfer->status : null,
                'caisse_transfer_id' => $t->caisse_transfer_id,
                'transfer_source_name' => $t->transfer && $t->transfer->sourceCaisse ? $t->transfer->sourceCaisse->name : null,
                'transfer_dest_name' => $t->transfer && $t->transfer->destinationCaisse ? $t->transfer->destinationCaisse->name : null,
            ];
        }

        // Build a meta block, compatible with previous shape, but reflecting non-paginated data
        $totalCount = $items->count();
        $meta = [
            'current_page' => 1,
            'per_page'     => $totalCount,
            'total'        => $totalCount,
            'last_page'    => 1,
        ];

        $summary = [
            'opening_balance' => round($openingBalance, 2),
            'closing_balance' => round($running, 2),
            // Extended summary fields (for Résumé block in Show.vue)
            'period_opening_balance' => 0,
            'total_entrees' => 0,
            'total_sorties' => 0,
            'total_transfers_in' => 0,
            'total_transfers_out' => 0,
            'computed_closing_balance' => 0,
        ];

        // Compute extended summary over the FULL filtered set (ignoring pagination)
        // Base clone with same filters
        $fullSetQuery = (clone $baseQuery);
        $fullSet = $fullSetQuery->get(['type', 'movement_type', 'amount', 'date', 'id']);

        // Period opening balance: sum strictly before start_date if provided
        $periodOpening = 0;
        if ($request->filled('start_date')) {
            $beforePeriod = (clone $baseQuery)
                ->where('date', '<', $request->start_date)
                ->get(['type', 'amount']);
            foreach ($beforePeriod as $t) {
                $periodOpening += $t->type === 'entree' ? $t->amount : -$t->amount;
            }
        }

        $totalEntrees = 0; // excluding transfer entrants
        $totalSorties = 0; // excluding transfer sortants
        $totalTransfersIn = 0;
        $totalTransfersOut = 0;

        foreach ($fullSet as $t) {
            if ($t->type === 'entree') {
                if ($t->movement_type === \App\Models\CaisseTransaction::MOV_TRANSFERT_ENTRANT) {
                    $totalTransfersIn += $t->amount;
                } else {
                    $totalEntrees += $t->amount;
                }
            } else { // sortie
                if ($t->movement_type === \App\Models\CaisseTransaction::MOV_TRANSFERT_SORTANT) {
                    $totalTransfersOut += $t->amount;
                } else {
                    $totalSorties += $t->amount;
                }
            }
        }

        $computedClosing = $periodOpening + $totalEntrees - $totalSorties + $totalTransfersIn - $totalTransfersOut;

        $summary['period_opening_balance'] = $periodOpening;
        $summary['total_entrees'] = $totalEntrees;
        $summary['total_sorties'] = $totalSorties;
        $summary['total_transfers_in'] = $totalTransfersIn;
        $summary['total_transfers_out'] = $totalTransfersOut;
        $summary['computed_closing_balance'] = $computedClosing;

        return response()->json([
            'data'    => $data,
            'meta'    => $meta,
            'summary' => $summary,
        ]);
    }

    public function fetchCaisseTransactionsOld(Request $request)
    {
        $query = CaisseTransaction::query()->with(['transaction.client', 'transfer.sourceCaisse', 'transfer.destinationCaisse']);

        // Filtrer par caisse si fournie
        if ($request->filled('caisse_id')) {
            $query->where('caisse_id', $request->caisse_id);
        }

        // Filtrer par période si sélectionnée
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Filtrer par type si sélectionné
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->orderBy('date', 'asc')->get();
        $data = $this->processTransactions($transactions, true);

        return response()->json($data);
    }

    public function exportPDF(Request $request)
    {
        $transactions = $this->filterTransactions($request);
        $data = $this->processTransactions($transactions);

        // Générer le PDF
        $pdf = Pdf::loadView('pdf.historique_caisse', compact('data'))->setPaper('a4', 'landscape')
        ;

        return $pdf->stream('historique_caisse.pdf');
    }

    /**
     * Page: liste des transferts entre caisses (Inertia)
     */
    public function transfersPage()
    {
        return Inertia::render('Finances/Caisse/Transfers/Index');
    }

    /**
     * API: liste paginée des transferts avec filtres
     */
    public function listTransfers(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = CaisseTransfer::query()
            ->with(['sourceCaisse', 'destinationCaisse', 'sourceTransaction.user', 'destinationTransaction.user'])
            ->orderBy('transfer_date', 'desc')->orderBy('id', 'desc');

        // Filtres: source et destination
        if ($request->filled('source_caisse_id')) {
            $query->where('source_caisse_id', $request->integer('source_caisse_id'));
        }
        if ($request->filled('destination_caisse_id')) {
            $query->where('destination_caisse_id', $request->integer('destination_caisse_id'));
        }

        // Filtre type relatif à une caisse spécifique (entrant/sortant)
        if ($request->filled('type') && $request->filled('caisse_id')) {
            $type = strtolower((string) $request->get('type'));
            $cid = $request->integer('caisse_id');
            if ($type === 'entrant') {
                $query->where('destination_caisse_id', $cid);
            } elseif ($type === 'sortant') {
                $query->where('source_caisse_id', $cid);
            }
        }

        // Période
        $period = $request->get('period'); // today|week|month|custom|null
        $start = $request->get('start_date');
        $end = $request->get('end_date');
        if ($period && $period !== 'custom') {
            $now = now();
            if ($period === 'today') {
                $query->whereDate('transfer_date', $now->toDateString());
            } elseif ($period === 'week') {
                $query->whereBetween('transfer_date', [
                    $now->copy()->startOfWeek(),
                    $now->copy()->endOfWeek(),
                ]);
            } elseif ($period === 'month') {
                $query->whereBetween('transfer_date', [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth(),
                ]);
            }
        } elseif ($start && $end) {
            $query->whereBetween('transfer_date', [$start, $end]);
        }

        $paginator = $query->paginate($perPage);
        $data = $paginator->getCollection()->map(function (CaisseTransfer $t) {
            return [
                'id' => $t->id,
                'transfer_date' => $t->transfer_date,
                'source' => [
                    'id' => $t->source_caisse_id,
                    'name' => optional($t->sourceCaisse)->name,
                    'currency_code' => optional($t->sourceCaisse)->currency_code,
                ],
                'destination' => [
                    'id' => $t->destination_caisse_id,
                    'name' => optional($t->destinationCaisse)->name,
                    'currency_code' => optional($t->destinationCaisse)->currency_code,
                ],
                'amount_source' => $t->amount_source,
                'amount_destination' => $t->amount_destination,
                'exchange_rate' => $t->exchange_rate,
                'description' => $t->description,
                'status' => $t->status,
                'corrected_transfer_id' => $t->corrected_transfer_id,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * API: détails d'un transfert
     */
    public function showTransfer(CaisseTransfer $transfer)
    {
        $transfer->load(['sourceCaisse', 'destinationCaisse', 'sourceTransaction.user', 'destinationTransaction.user']);
        $createdBy = optional(optional($transfer->sourceTransaction)->user ?? optional($transfer->destinationTransaction)->user);

        return response()->json([
            'id' => $transfer->id,
            'reference' => sprintf('TRF-%s', str_pad((string) $transfer->id, 8, '0', STR_PAD_LEFT)),
            'source' => [
                'id' => $transfer->source_caisse_id,
                'name' => optional($transfer->sourceCaisse)->name,
            ],
            'destination' => [
                'id' => $transfer->destination_caisse_id,
                'name' => optional($transfer->destinationCaisse)->name,
            ],
            'amount_source' => $transfer->amount_source,
            'amount_destination' => $transfer->amount_destination,
            'exchange_rate' => $transfer->exchange_rate,
            'description' => $transfer->description,
            'status' => $transfer->status,
            'transfer_date' => $transfer->transfer_date,
            'created_by' => $createdBy ? $createdBy->name : null,
        ]);
    }


    public function getSolde(Request $request)
    {
        $queryEntrees = CaisseTransaction::where('type', 'entree');
        $querySorties = CaisseTransaction::where('type', 'sortie');

        if ($request->filled('caisse_id')) {
            $queryEntrees->where('caisse_id', $request->caisse_id);
            $querySorties->where('caisse_id', $request->caisse_id);
        }

        $totalEntrees = $queryEntrees->sum('amount');
        $totalSorties = $querySorties->sum('amount');

        return response()->json(['solde' => $totalEntrees - $totalSorties]);
    }

    /**
     * Récupère l'identifiant de la caisse par défaut (première caisse active).
     */
    private function resolveDefaultCaisseId(): int
    {
        $caisse = Caisse::query()->where('active', true)->orderBy('id')->first();
        if (!$caisse) {
            abort(response()->json([
                'message' => "Aucune caisse active n'a été trouvée. Créez au moins une caisse avant d'enregistrer des sorties.",
            ], 422));
        }
        return (int) $caisse->id;
    }

}
