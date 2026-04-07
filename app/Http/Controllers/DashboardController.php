<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleItem;
use App\Models\Caisse;
use App\Models\CaisseTransaction;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Services\SyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function sync(SyncService $syncService)
    {
        $models = [
            \App\Models\User::class,

        ];

        $results = [];
        foreach ($models as $model) {
            $name = class_basename($model);
            $pushed = $syncService->push($model);
            $pulled = $syncService->pull($model);

            $results[$name] = [
                'pushed' => $pushed,
                'pulled' => $pulled,
            ];
        }

        return response()->json([
            'message' => 'Synchronisation terminée',
            'details' => $results
        ]);
    }

    public function getStats(){
        $chiffreAffaire = Transaction::where([
            'type'=>'invoice',
            'old_transaction'=>0
        ])->sum('amount');
        $chiffreAffaireOld = Transaction::where([
            'type'=>'invoice',
            'old_transaction'=>1
        ])->sum('amount');

        $montantPaye = Transaction::where([
            'type'=>'payment'
        ])->sum('amount');
        $montantTotal = $chiffreAffaire + $chiffreAffaireOld;

        $montantDue = $montantTotal - $montantPaye;

        $stockDisponible = ArticleItem::where('indisponible',0)->count();

        // Utiliser la valeur legacy correcte 'entree' (sans accent)
        $soldeCaisse = CaisseTransaction::where('type', 'entree')->sum('amount') - CaisseTransaction::where('type', 'sortie')->sum('amount');


        return response()->json([
            'chiffre_affaires' => $chiffreAffaire,
            'chiffre_affaire_old' => $chiffreAffaireOld,
            'montant_paye' => $montantPaye,
            'montant_du' => $montantDue,
            'stock_disponible' => $stockDisponible,
            'soldeCaisse' => $soldeCaisse
        ]);
    }

    public function getChiffreAffaireBeneficeParMois(Request $request)
    {
        $query = InvoiceItem::join('article_items', 'invoice_items.article_item_id', '=', 'article_items.id')
            ->join('articles', 'article_items.article_id', '=', 'articles.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->select(
                DB::raw('YEAR(invoices.date) as year'),
                DB::raw('MONTH(invoices.date) as month'),
                DB::raw('SUM(invoice_items.total_price_item) as total_revenue'),
                DB::raw('SUM(article_items.volume * articles.price_per_m3) as cost_base')
            )
            ->groupBy(DB::raw('YEAR(invoices.date), MONTH(invoices.date)'));

        // 📌 **Filtrage dynamique**
        if ($request->filled('client_id')) {
            $query->where('invoices.client_id', $request->client_id);
        }

        if ($request->filled('essence')) {
            $query->where('articles.essence', $request->essence);
        }

        if ($request->filled('epaisseur')) {
            $query->where('article_items.epaisseur', $request->epaisseur);
        }

        if ($request->filled('fournisseur_id')) {
            $query->where('articles.fournisseur_id', $request->fournisseur_id);
        }

        if ($request->filled('contract_number')) {
            $query->where('articles.contract_number', 'like', "%{$request->contract_number}%");
        }

        $stats = $query->get();

        // Ajouter le bénéfice à chaque mois
        $stats = $stats->map(function ($stat) {
            $stat->profit = $stat->total_revenue - $stat->cost_base;
            return $stat;
        });

        return response()->json($stats);
    }

    public function exportChiffreAffaireBeneficePDF(Request $request)
    {
        $query = InvoiceItem::join('article_items', 'invoice_items.article_item_id', '=', 'article_items.id')
            ->join('articles', 'article_items.article_id', '=', 'articles.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->select(
                DB::raw('YEAR(invoices.date) as year'),
                DB::raw('MONTH(invoices.date) as month'),
                DB::raw('SUM(invoice_items.total_price_item) as total_revenue'),
                DB::raw('SUM(article_items.volume * articles.price_per_m3) as cost_base')
            )
            ->groupBy(DB::raw('YEAR(invoices.date), MONTH(invoices.date)'));

        // 📌 **Appliquer les filtres**
        if ($request->filled('client_id')) {
            $query->where('invoices.client_id', $request->client_id);
        }

        if ($request->filled('essence')) {
            $query->where('articles.essence', $request->essence);
        }

        if ($request->filled('epaisseur')) {
            $query->where('article_items.epaisseur', $request->epaisseur);
        }

        if ($request->filled('fournisseur_id')) {
            $query->where('articles.fournisseur_id', $request->fournisseur_id);
        }

        if ($request->filled('contract_number')) {
            $query->where('articles.contract_number', 'like', "%{$request->contract_number}%");
        }

        $stats = $query->get();

        // Ajouter le bénéfice à chaque mois
        $stats = $stats->map(function ($stat) {
            $stat->profit = $stat->total_revenue - $stat->cost_base;
            return $stat;
        });

        // Générer le PDF
        $pdf = Pdf::loadView('pdf.stats_ca_benefice', compact('stats'));

        return $pdf->download('chiffre_affaire_benefice.pdf');
    }

    public function getEvolutionMensuelleCA(Request $request)
    {
        $now = Carbon::now();
        $months = [];

        // Générer les 6 derniers mois dynamiquement
        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $months[$date->format('Y-m')] = [
                'month' => $date->format('M Y'),
                'total_revenue' => 0
            ];
        }

        // Requête de base
        $query = Invoice::join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('article_items', 'invoice_items.article_item_id', '=', 'article_items.id')
            ->join('articles', 'article_items.article_id', '=', 'articles.id')
            ->select(
                DB::raw("DATE_FORMAT(invoices.date, '%Y-%m') as period"),
                DB::raw("SUM(invoice_items.total_price_item) as total_revenue")
            )
            ->where('invoices.date', '>=', $now->subMonths(6)->startOfMonth())
            ->groupBy('period')
            ->orderBy('period', 'ASC');

        // 📌 **Ajout des filtres**
        if ($request->filled('client_id')) {
            $query->where('invoices.client_id', $request->client_id);
        }

        if ($request->filled('contract_number')) {
            $query->where('articles.contract_number', 'like', "%{$request->contract_number}%");
        }

        if ($request->filled('essence')) {
            $query->where('articles.essence', $request->essence);
        }

        if ($request->filled('epaisseur')) {
            $query->where('article_items.epaisseur', $request->epaisseur);
        }

        if ($request->filled('fournisseur_id')) {
            $query->where('articles.fournisseur_id', $request->fournisseur_id);
        }

        $data = $query->get();

        // Ajouter les données récupérées dans la structure
        foreach ($data as $entry) {
            if (isset($months[$entry->period])) {
                $months[$entry->period]['total_revenue'] = $entry->total_revenue;
            }
        }

        return response()->json(array_values($months));
    }


    public function getTopClients(Request $request)
    {
        $query = Client::select(
            'clients.id',
            'clients.name',
            DB::raw("SUM(invoices.total_price) as total_revenue")
        )
            ->join('invoices', 'clients.id', '=', 'invoices.client_id')
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('article_items', 'invoice_items.article_item_id', '=', 'article_items.id')
            ->join('articles', 'article_items.article_id', '=', 'articles.id')
            ->groupBy('clients.id', 'clients.name')
            ->orderByDesc('total_revenue');

        // 📌 **Ajout des filtres**
        if ($request->filled('contract_number')) {
            $query->where('articles.contract_number', 'like', "%{$request->contract_number}%");
        }

        if ($request->filled('essence')) {
            $query->where('articles.essence', $request->essence);
        }

        if ($request->filled('epaisseur')) {
            $query->where('article_items.epaisseur', $request->epaisseur);
        }

        $clients = $query->limit(5)->get();

        return response()->json($clients);
    }

    public function getClientsStats()
    {
        // ── KPIs globaux ────────────────────────────────────────────────
        $totalCA   = Transaction::whereNull('deleted_at')->where('type', 'invoice')->sum('amount');
        $totalPaye = Transaction::whereNull('deleted_at')->where('type', 'payment')->sum('amount');
        $totalDu   = $totalCA - $totalPaye;
        $tauxRecouvrement = $totalCA > 0 ? round(($totalPaye / $totalCA) * 100, 1) : 0;
        $nbClients = Client::count();

        $nbClientsAvecCreances = DB::table('clients')
            ->join('transactions', 'clients.id', '=', 'transactions.client_id')
            ->whereNull('transactions.deleted_at')
            ->select('clients.id')
            ->groupBy('clients.id')
            ->havingRaw("SUM(CASE WHEN transactions.type = 'invoice' THEN transactions.amount ELSE -transactions.amount END) > 0")
            ->get()->count();

        // ── Évolution 6 mois : factures vs paiements ────────────────────
        $now    = Carbon::now();
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = $now->copy()->subMonths($i);
            $months[$d->format('Y-m')] = [
                'month'         => $d->format('M Y'),
                'total_facture' => 0,
                'total_paye'    => 0,
            ];
        }
        $sixAgo = $now->copy()->subMonths(6)->startOfMonth();

        foreach (
            Transaction::whereNull('deleted_at')
                ->where('type', 'invoice')
                ->where('transaction_date', '>=', $sixAgo)
                ->selectRaw("DATE_FORMAT(transaction_date,'%Y-%m') as period, SUM(amount) as total")
                ->groupBy('period')->get() as $row
        ) {
            if (isset($months[$row->period])) $months[$row->period]['total_facture'] = (float) $row->total;
        }

        foreach (
            Transaction::whereNull('deleted_at')
                ->where('type', 'payment')
                ->where('transaction_date', '>=', $sixAgo)
                ->selectRaw("DATE_FORMAT(transaction_date,'%Y-%m') as period, SUM(amount) as total")
                ->groupBy('period')->get() as $row
        ) {
            if (isset($months[$row->period])) $months[$row->period]['total_paye'] = (float) $row->total;
        }

        // ── Liste complète clients avec stats ────────────────────────────
        $clientsData = DB::table('clients')
            ->leftJoin('transactions', function ($join) {
                $join->on('clients.id', '=', 'transactions.client_id')
                     ->whereNull('transactions.deleted_at');
            })
            ->selectRaw("
                clients.id,
                clients.name,
                clients.phone,
                COALESCE(SUM(CASE WHEN transactions.type = 'invoice' THEN transactions.amount ELSE 0 END), 0)                   AS total_ca,
                COALESCE(SUM(CASE WHEN transactions.type = 'payment' THEN transactions.amount ELSE 0 END), 0)                   AS total_paye,
                COALESCE(SUM(CASE WHEN transactions.type = 'invoice' THEN transactions.amount ELSE -transactions.amount END), 0) AS montant_du,
                COUNT(DISTINCT CASE WHEN transactions.type = 'invoice' THEN transactions.invoice_id END)                        AS nb_factures,
                MAX(CASE WHEN transactions.type = 'invoice' THEN transactions.transaction_date END)                             AS derniere_facture
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

    public function getStockStats()
    {
        $base = ArticleItem::query()
            ->join('articles', 'article_items.article_id', '=', 'articles.id')
            ->whereNull('article_items.deleted_at')
            ->whereNull('articles.deleted_at')
            ->where('article_items.indisponible', 0);

        // KPI globaux
        $kpi = (clone $base)
            ->selectRaw('
                COUNT(article_items.id)                              AS total_colis,
                COALESCE(SUM(article_items.volume), 0)              AS volume_total,
                COALESCE(SUM(article_items.volume * articles.price_per_m3), 0) AS valeur_stock,
                COUNT(DISTINCT articles.essence)                     AS nb_essences,
                COUNT(DISTINCT articles.supplier_id)                 AS nb_fournisseurs
            ')
            ->first();

        // Colis indisponibles (vendus / réservés)
        $totalIndisponible = ArticleItem::whereNull('deleted_at')
            ->where('indisponible', 1)
            ->count();

        // Par essence
        $parEssence = (clone $base)
            ->selectRaw('
                articles.essence,
                COUNT(article_items.id)                              AS nb_colis,
                COALESCE(SUM(article_items.volume), 0)              AS volume_total,
                COALESCE(SUM(article_items.volume * articles.price_per_m3), 0) AS valeur_estimee
            ')
            ->groupBy('articles.essence')
            ->orderByDesc('nb_colis')
            ->get();

        // Par épaisseur
        $parEpaisseur = (clone $base)
            ->selectRaw('
                article_items.epaisseur,
                COUNT(article_items.id)                              AS nb_colis,
                COALESCE(SUM(article_items.volume), 0)              AS volume_total
            ')
            ->groupBy('article_items.epaisseur')
            ->orderBy('article_items.epaisseur')
            ->get();

        // Par fournisseur
        $parFournisseur = (clone $base)
            ->join('suppliers', 'articles.supplier_id', '=', 'suppliers.id')
            ->selectRaw('
                suppliers.name,
                COUNT(article_items.id)                              AS nb_colis,
                COALESCE(SUM(article_items.volume), 0)              AS volume_total,
                COALESCE(SUM(article_items.volume * articles.price_per_m3), 0) AS valeur_estimee
            ')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('nb_colis')
            ->get();

        return response()->json([
            'kpi' => array_merge($kpi->toArray(), ['total_indisponible' => $totalIndisponible]),
            'par_essence'     => $parEssence,
            'par_epaisseur'   => $parEpaisseur,
            'par_fournisseur' => $parFournisseur,
        ]);
    }

    public function getCaisseStats()
    {
        $now = Carbon::now();

        // Types mouvement
        $entreeTypes = [
            CaisseTransaction::MOV_ENTREE_CLIENT,
            CaisseTransaction::MOV_ENTREE_AUTRE,
            CaisseTransaction::MOV_TRANSFERT_ENTRANT,
        ];
        $sortieTypes = [
            CaisseTransaction::MOV_SORTIE,
            CaisseTransaction::MOV_TRANSFERT_SORTANT,
        ];

        // Closure réutilisable : filtre entrées
        $whereEntree = function ($q) use ($entreeTypes) {
            $q->where(function ($inner) use ($entreeTypes) {
                $inner->whereIn('movement_type', $entreeTypes)
                      ->orWhere(function ($leg) {
                          $leg->whereNull('movement_type')->where('type', 'entree');
                      });
            });
        };

        // Closure réutilisable : filtre sorties
        $whereSortie = function ($q) use ($sortieTypes) {
            $q->where(function ($inner) use ($sortieTypes) {
                $inner->whereIn('movement_type', $sortieTypes)
                      ->orWhere(function ($leg) {
                          $leg->whereNull('movement_type')->where('type', 'sortie');
                      });
            });
        };

        // ── KPIs globaux ─────────────────────────────────────────────
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

        // ── Solde par caisse ─────────────────────────────────────────
        $caisses = Caisse::where('active', true)
            ->withSum(['transactions as entries_sum' => fn($q) => $whereEntree($q)], 'amount')
            ->withSum(['transactions as exits_sum'   => fn($q) => $whereSortie($q)], 'amount')
            ->orderBy('id')
            ->get()
            ->map(function ($c) {
                $entries = (float)($c->entries_sum ?? 0);
                $exits   = (float)($c->exits_sum   ?? 0);
                $c->total_entrees = $entries;
                $c->total_sorties = $exits;
                $c->solde         = (float)($c->initial_balance ?? 0) + $entries - $exits;
                return $c;
            });

        $soldeTotalCaisses = $caisses->sum('solde');

        // ── Évolution 6 mois ─────────────────────────────────────────
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = $now->copy()->subMonths($i);
            $months[$d->format('Y-m')] = [
                'month'   => $d->format('M Y'),
                'entrees' => 0,
                'sorties' => 0,
            ];
        }
        $sixAgo = $now->copy()->subMonths(6)->startOfMonth();

        $entreesParMois = CaisseTransaction::whereNull('deleted_at')
            ->where($whereEntree)
            ->where('date', '>=', $sixAgo)
            ->selectRaw("DATE_FORMAT(date,'%Y-%m') as period, SUM(amount) as total")
            ->groupBy('period')->get();

        $sortiesParMois = CaisseTransaction::whereNull('deleted_at')
            ->where($whereSortie)
            ->where('date', '>=', $sixAgo)
            ->selectRaw("DATE_FORMAT(date,'%Y-%m') as period, SUM(amount) as total")
            ->groupBy('period')->get();

        foreach ($entreesParMois as $row) {
            if (isset($months[$row->period])) $months[$row->period]['entrees'] = (float)$row->total;
        }
        foreach ($sortiesParMois as $row) {
            if (isset($months[$row->period])) $months[$row->period]['sorties'] = (float)$row->total;
        }

        // ── Répartition entrées par type ──────────────────────────────
        $repartition = CaisseTransaction::whereNull('deleted_at')
            ->whereIn('movement_type', $entreeTypes)
            ->selectRaw('movement_type, SUM(amount) as total')
            ->groupBy('movement_type')
            ->get();

        // ── 20 dernières transactions ─────────────────────────────────
        $dernieres = CaisseTransaction::with('caisse')
            ->whereNull('deleted_at')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get()
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

}
