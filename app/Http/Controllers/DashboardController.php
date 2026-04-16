<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\CaisseTransaction;
use App\Models\Client;
use App\Models\PlancheBonLivraison;
use App\Models\Transaction;
use App\Services\SyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function sync(SyncService $syncService)
    {
        $models  = [\App\Models\User::class];
        $results = [];

        foreach ($models as $model) {
            $name          = class_basename($model);
            $results[$name] = [
                'pushed' => $syncService->push($model),
                'pulled' => $syncService->pull($model),
            ];
        }

        return response()->json(['message' => 'Synchronisation terminée', 'details' => $results]);
    }

    // ─────────────────────────────────────────────────────────────
    //  KPIs généraux — uniquement liés aux planches
    // ─────────────────────────────────────────────────────────────
    public function getStats()
    {
        // Chiffre d'affaires : transactions invoice liées directement à des BL planches
        $chiffreAffaire = Transaction::where('type', 'invoice')
            ->where('old_transaction', 0)
            ->whereNotNull('planche_bon_livraison_id')
            ->sum('amount');

        $chiffreAffaireOld = Transaction::where('type', 'invoice')
            ->where('old_transaction', 1)
            ->whereNotNull('planche_bon_livraison_id')
            ->sum('amount');

        $montantPaye  = Transaction::where('type', 'payment')->sum('amount');
        $montantTotal = $chiffreAffaire + $chiffreAffaireOld;
        $montantDue   = $montantTotal - $montantPaye;

        // Nombre de bons de livraison (remplace "colis en stock")
        $nbBonsLivraison = PlancheBonLivraison::count();

        // Solde caisse
        $soldeCaisse = CaisseTransaction::where('type', 'entree')->sum('amount')
                     - CaisseTransaction::where('type', 'sortie')->sum('amount');

        return response()->json([
            'chiffre_affaires'    => $chiffreAffaire,
            'chiffre_affaire_old' => $chiffreAffaireOld,
            'montant_paye'        => $montantPaye,
            'montant_du'          => $montantDue,
            'nb_bons_livraison'   => $nbBonsLivraison,
            'soldeCaisse'         => $soldeCaisse,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  CA par mois — planches uniquement
    // ─────────────────────────────────────────────────────────────
    protected function applyDashboardFilters($query, Request $request)
    {
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('couleur_id') || $request->filled('epaisseur') || $request->filled('categorie')) {
            $query->whereHas('lignes.plancheDetail', function ($q) use ($request) {
                if ($request->filled('couleur_id')) {
                    $q->where('planche_couleur_id', $request->couleur_id);
                }
                if ($request->filled('epaisseur')) {
                    $q->where('epaisseur', $request->epaisseur);
                }
                if ($request->filled('categorie')) {
                    $q->where('categorie', $request->categorie);
                }
            });
        }

        return $query;
    }

    protected function getChiffreAffaireBeneficeStats(Request $request)
    {
        $baseQuery = PlancheBonLivraison::where('statut', '!=', 'annule');

        $revenueQuery = $this->applyDashboardFilters(clone $baseQuery, $request)
            ->select(
                DB::raw('YEAR(date_livraison) as year'),
                DB::raw('MONTH(date_livraison) as month'),
                DB::raw('SUM(montant) as total_revenue')
            )
            ->groupBy(DB::raw('YEAR(date_livraison), MONTH(date_livraison)'))
            ->orderBy(DB::raw('YEAR(date_livraison), MONTH(date_livraison)'));

        $costQuery = $this->applyDashboardFilters(clone $baseQuery, $request)
            ->join('planche_bon_livraison_lignes as lignes', 'planche_bons_livraison.id', '=', 'lignes.planche_bon_livraison_id')
            ->leftJoin('planche_details as details', 'lignes.planche_detail_id', '=', 'details.id')
            ->select(
                DB::raw('YEAR(planche_bons_livraison.date_livraison) as year'),
                DB::raw('MONTH(planche_bons_livraison.date_livraison) as month'),
                DB::raw('SUM(COALESCE(lignes.quantite_livree * details.prix_de_revient, 0)) as total_cost')
            )
            ->groupBy(DB::raw('YEAR(planche_bons_livraison.date_livraison), MONTH(planche_bons_livraison.date_livraison)'))
            ->orderBy(DB::raw('YEAR(planche_bons_livraison.date_livraison), MONTH(planche_bons_livraison.date_livraison)'));

        $costRows = $costQuery->get()->keyBy(fn ($row) => sprintf('%s-%s', $row->year, $row->month));

        return $revenueQuery->get()->map(function ($stat) use ($costRows) {
            $key = sprintf('%s-%s', $stat->year, $stat->month);
            $cost = $costRows->get($key)->total_cost ?? 0;

            $stat->cost_base = (float) $cost;
            $stat->profit    = (float) ($stat->total_revenue - $cost);

            return $stat;
        });
    }

    public function getChiffreAffaireBeneficeParMois(Request $request)
    {
        $stats = $this->getChiffreAffaireBeneficeStats($request);
        return response()->json($stats);
    }

    public function exportChiffreAffaireBeneficePDF(Request $request)
    {
        $stats = $this->getChiffreAffaireBeneficeStats($request);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.stats_ca_benefice', compact('stats'));
        return $pdf->download('chiffre_affaire_planches.pdf');
    }

    // ─────────────────────────────────────────────────────────────
    //  Évolution mensuelle CA — planches uniquement
    // ─────────────────────────────────────────────────────────────
    public function getEvolutionMensuelleCA(Request $request)
    {
        $now    = Carbon::now();
        $months = [];

        for ($i = 5; $i >= 0; $i--) {
            $date                        = $now->copy()->subMonths($i);
            $months[$date->format('Y-m')] = ['month' => $date->format('M Y'), 'total_revenue' => 0];
        }

        $query = PlancheBonLivraison::where('statut', '!=', 'annule')
            ->select(
                DB::raw("DATE_FORMAT(date_livraison, '%Y-%m') as period"),
                DB::raw('SUM(montant) as total_revenue')
            )
            ->where('date_livraison', '>=', $now->copy()->subMonths(6)->startOfMonth())
            ->groupBy('period')
            ->orderBy('period', 'ASC');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('couleur_id') || $request->filled('epaisseur') || $request->filled('categorie')) {
            $query->whereHas('lignes.plancheDetail', function ($q) use ($request) {
                if ($request->filled('couleur_id')) {
                    $q->where('planche_couleur_id', $request->couleur_id);
                }
                if ($request->filled('epaisseur')) {
                    $q->where('epaisseur', $request->epaisseur);
                }
                if ($request->filled('categorie')) {
                    $q->where('categorie', $request->categorie);
                }
            });
        }

        foreach ($query->get() as $entry) {
            if (isset($months[$entry->period])) {
                $months[$entry->period]['total_revenue'] = $entry->total_revenue;
            }
        }

        return response()->json(array_values($months));
    }

    // ─────────────────────────────────────────────────────────────
    //  Top clients — planches uniquement
    // ─────────────────────────────────────────────────────────────
    public function getTopClients(Request $request)
    {
        $query = Client::select(
            'clients.id',
            'clients.name',
            DB::raw('SUM(pbl.montant) as total_revenue')
        )
            ->join('planche_bons_livraison as pbl', 'clients.id', '=', 'pbl.client_id')
            ->where('pbl.statut', '!=', 'annule')
            ->groupBy('clients.id', 'clients.name')
            ->orderByDesc('total_revenue');

        if ($request->filled('client_id')) {
            $query->where('clients.id', $request->client_id);
        }

        return response()->json($query->limit(5)->get());
    }

    // ─────────────────────────────────────────────────────────────
    //  Stats clients — planches uniquement
    // ─────────────────────────────────────────────────────────────
    public function getClientsStats()
    {
        // KPIs globaux planches
        $totalCA   = Transaction::whereNull('deleted_at')
            ->where('type', 'invoice')
            ->whereNotNull('planche_bon_livraison_id')
            ->sum('amount');

        $totalPaye = Transaction::whereNull('deleted_at')->where('type', 'payment')->sum('amount');
        $totalDu   = $totalCA - $totalPaye;
        $tauxRecouvrement = $totalCA > 0 ? round(($totalPaye / $totalCA) * 100, 1) : 0;

        $nbClients = Client::whereHas('transactions', fn ($q) => $q->whereNotNull('planche_bon_livraison_id'))->count();

        $nbClientsAvecCreances = DB::table('clients')
            ->join('transactions', 'clients.id', '=', 'transactions.client_id')
            ->whereNull('transactions.deleted_at')
            ->whereNotNull('transactions.planche_bon_livraison_id')
            ->select('clients.id')
            ->groupBy('clients.id')
            ->havingRaw("SUM(CASE WHEN transactions.type = 'invoice' THEN transactions.amount ELSE -transactions.amount END) > 0")
            ->get()->count();

        // Évolution 6 mois
        $now    = Carbon::now();
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $d                        = $now->copy()->subMonths($i);
            $months[$d->format('Y-m')] = ['month' => $d->format('M Y'), 'total_facture' => 0, 'total_paye' => 0];
        }
        $sixAgo = $now->copy()->subMonths(6)->startOfMonth();

        // Factures planches par mois
        $facturesParMois = Transaction::whereNull('deleted_at')
            ->where('type', 'invoice')
            ->whereNotNull('planche_bon_livraison_id')
            ->where('transaction_date', '>=', $sixAgo)
            ->selectRaw("DATE_FORMAT(transaction_date,'%Y-%m') as period, SUM(amount) as total")
            ->groupBy('period')->get();

        $paiementsParMois = Transaction::whereNull('deleted_at')
            ->where('type', 'payment')
            ->where('transaction_date', '>=', $sixAgo)
            ->selectRaw("DATE_FORMAT(transaction_date,'%Y-%m') as period, SUM(amount) as total")
            ->groupBy('period')->get();

        foreach ($facturesParMois as $row) {
            if (isset($months[$row->period])) $months[$row->period]['total_facture'] = (float) $row->total;
        }
        foreach ($paiementsParMois as $row) {
            if (isset($months[$row->period])) $months[$row->period]['total_paye'] = (float) $row->total;
        }

        // Liste clients avec stats planches
        $clientsData = DB::table('clients')
            ->leftJoin('transactions', function ($join) {
                $join->on('clients.id', '=', 'transactions.client_id')
                     ->whereNull('transactions.deleted_at');
            })
            ->selectRaw("
                clients.id,
                clients.name,
                clients.phone,
                COALESCE(SUM(CASE WHEN transactions.type = 'invoice' AND transactions.planche_bon_livraison_id IS NOT NULL THEN transactions.amount ELSE 0 END), 0) AS total_ca,
                COALESCE(SUM(CASE WHEN transactions.type = 'payment' THEN transactions.amount ELSE 0 END), 0) AS total_paye,
                COALESCE(SUM(CASE WHEN transactions.type = 'invoice' AND transactions.planche_bon_livraison_id IS NOT NULL THEN transactions.amount
                                  WHEN transactions.type = 'payment' THEN -transactions.amount
                                  ELSE 0 END), 0) AS montant_du,
                COUNT(DISTINCT CASE WHEN transactions.type = 'invoice' AND transactions.planche_bon_livraison_id IS NOT NULL THEN transactions.planche_bon_livraison_id END) AS nb_factures,
                MAX(CASE WHEN transactions.type = 'invoice' AND transactions.planche_bon_livraison_id IS NOT NULL THEN transactions.transaction_date END) AS derniere_facture
            ")
            ->groupBy('clients.id', 'clients.name', 'clients.phone')
            ->orderByDesc('total_ca')
            ->get()
            ->map(function ($c) {
                $c->taux_recouvrement = $c->total_ca > 0
                    ? round(($c->total_paye / $c->total_ca) * 100, 1)
                    : 100;
                return $c;
            });

        return response()->json([
            'kpi' => [
                'nb_clients'               => $nbClients,
                'total_ca'                 => $totalCA,
                'total_paye'               => $totalPaye,
                'total_du'                 => $totalDu,
                'taux_recouvrement'        => $tauxRecouvrement,
                'nb_clients_avec_creances' => $nbClientsAvecCreances,
            ],
            'evolution' => array_values($months),
            'clients'   => $clientsData,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  Stats caisse — inchangées (pas liées au bois/planches)
    // ─────────────────────────────────────────────────────────────
    public function getCaisseStats()
    {
        $now = Carbon::now();

        $entreeTypes = [
            CaisseTransaction::MOV_ENTREE_CLIENT,
            CaisseTransaction::MOV_ENTREE_AUTRE,
            CaisseTransaction::MOV_TRANSFERT_ENTRANT,
        ];
        $sortieTypes = [
            CaisseTransaction::MOV_SORTIE,
            CaisseTransaction::MOV_TRANSFERT_SORTANT,
        ];

        $whereEntree = function ($q) use ($entreeTypes) {
            $q->where(function ($inner) use ($entreeTypes) {
                $inner->whereIn('movement_type', $entreeTypes)
                      ->orWhere(fn ($leg) => $leg->whereNull('movement_type')->where('type', 'entree'));
            });
        };

        $whereSortie = function ($q) use ($sortieTypes) {
            $q->where(function ($inner) use ($sortieTypes) {
                $inner->whereIn('movement_type', $sortieTypes)
                      ->orWhere(fn ($leg) => $leg->whereNull('movement_type')->where('type', 'sortie'));
            });
        };

        $entreesMois = CaisseTransaction::whereNull('deleted_at')
            ->where($whereEntree)
            ->whereMonth('date', $now->month)->whereYear('date', $now->year)
            ->sum('amount');

        $sortiesMois = CaisseTransaction::whereNull('deleted_at')
            ->where($whereSortie)
            ->whereMonth('date', $now->month)->whereYear('date', $now->year)
            ->sum('amount');

        $entreesClientsMois = CaisseTransaction::whereNull('deleted_at')
            ->where('movement_type', CaisseTransaction::MOV_ENTREE_CLIENT)
            ->whereMonth('date', $now->month)->whereYear('date', $now->year)
            ->sum('amount');

        $nbCaisses = Caisse::where('active', true)->count();

        $caisses = Caisse::where('active', true)
            ->withSum(['transactions as entries_sum' => fn ($q) => $whereEntree($q)], 'amount')
            ->withSum(['transactions as exits_sum'   => fn ($q) => $whereSortie($q)], 'amount')
            ->orderBy('id')
            ->get()
            ->map(function ($c) {
                $entries      = (float) ($c->entries_sum ?? 0);
                $exits        = (float) ($c->exits_sum   ?? 0);
                $c->total_entrees = $entries;
                $c->total_sorties = $exits;
                $c->solde         = (float) ($c->initial_balance ?? 0) + $entries - $exits;
                return $c;
            });

        $soldeTotalCaisses = $caisses->sum('solde');

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $d                        = $now->copy()->subMonths($i);
            $months[$d->format('Y-m')] = ['month' => $d->format('M Y'), 'entrees' => 0, 'sorties' => 0];
        }
        $sixAgo = $now->copy()->subMonths(6)->startOfMonth();

        foreach (CaisseTransaction::whereNull('deleted_at')->where($whereEntree)->where('date', '>=', $sixAgo)
            ->selectRaw("DATE_FORMAT(date,'%Y-%m') as period, SUM(amount) as total")->groupBy('period')->get() as $row) {
            if (isset($months[$row->period])) $months[$row->period]['entrees'] = (float) $row->total;
        }
        foreach (CaisseTransaction::whereNull('deleted_at')->where($whereSortie)->where('date', '>=', $sixAgo)
            ->selectRaw("DATE_FORMAT(date,'%Y-%m') as period, SUM(amount) as total")->groupBy('period')->get() as $row) {
            if (isset($months[$row->period])) $months[$row->period]['sorties'] = (float) $row->total;
        }

        $repartition = CaisseTransaction::whereNull('deleted_at')
            ->whereIn('movement_type', $entreeTypes)
            ->selectRaw('movement_type, SUM(amount) as total')
            ->groupBy('movement_type')->get();

        $dernieres = CaisseTransaction::with('caisse')->whereNull('deleted_at')
            ->orderBy('date', 'desc')->orderBy('id', 'desc')->limit(20)->get()
            ->map(function ($t) use ($entreeTypes) {
                $isEntree = in_array($t->movement_type, $entreeTypes)
                    || ($t->movement_type === null && $t->type === 'entree');
                return [
                    'id'            => $t->id,
                    'date'          => $t->date,
                    'caisse'        => $t->caisse?->name ?? '—',
                    'movement_type' => $t->movement_type ?? $t->type,
                    'objet'         => $t->objet ?? $t->description,
                    'amount'        => $t->amount,
                    'sens'          => $isEntree ? 'entree' : 'sortie',
                ];
            });

        return response()->json([
            'kpi' => [
                'solde_total'          => $soldeTotalCaisses,
                'entrees_mois'         => $entreesMois,
                'sorties_mois'         => $sortiesMois,
                'entrees_clients_mois' => $entreesClientsMois,
                'balance_nette_mois'   => $entreesMois - $sortiesMois,
                'nb_caisses'           => $nbCaisses,
            ],
            'par_caisse'  => $caisses->values(),
            'evolution'   => array_values($months),
            'repartition' => $repartition,
            'dernieres'   => $dernieres,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  Stats stock — supprimé (non pertinent pour planches)
    // ─────────────────────────────────────────────────────────────
    public function getStockStats()
    {
        return response()->json([]);
    }
}
