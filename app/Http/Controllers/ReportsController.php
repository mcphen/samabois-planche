<?php

namespace App\Http\Controllers;

use App\Exports\GenericReportExport;
use App\Models\Caisse;
use App\Models\CaisseTransaction;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    public function index()
    {
        $caisses = Caisse::orderBy('name')->get(['id','name','type']);
        $clients = Client::orderBy('name')->get(['id','name']);
        $caisseTypes = Caisse::query()->select('type')->whereNotNull('type')->distinct()->pluck('type');

        return Inertia::render('Finances/Rapports/Index', [
            'caisses' => $caisses,
            'clients' => $clients,
            'caisseTypes' => $caisseTypes,
        ]);
    }

    public function data(Request $request)
    {
        $report = $request->string('report');
        [$start, $end] = $this->parseDateRange($request);
        $caisseId = $request->integer('caisse_id') ?: null;
        $clientId = $request->integer('client_id') ?: null;
        $typeCaisse = $request->string('caisse_type') ?: null;
        $period = $request->string('period') ?: null; // day|week|month|quarter

        switch ($report) {
            case 'journal_all':
                $rows = $this->formatTransactions(
                    $this->buildJournalQuery($start, $end, null, $typeCaisse)->get()
                );
                break;
            case 'journal_caisse':
                $rows = $this->formatTransactions(
                    $this->buildJournalQuery($start, $end, $caisseId, null)->get()
                );
                break;
            case 'entrees':
                $rows = $this->formatTransactions(
                    $this->buildJournalQuery($start, $end, $caisseId, $typeCaisse)
                    ->whereIn('movement_type', [
                        CaisseTransaction::MOV_ENTREE_CLIENT,
                        CaisseTransaction::MOV_ENTREE_AUTRE,
                        CaisseTransaction::MOV_TRANSFERT_ENTRANT,
                    ])->get()
                );
                break;
            case 'sorties':
                $rows = $this->formatTransactions(
                    $this->buildJournalQuery($start, $end, $caisseId, $typeCaisse)
                    ->whereIn('movement_type', [
                        CaisseTransaction::MOV_SORTIE,
                        CaisseTransaction::MOV_TRANSFERT_SORTANT,
                    ])->get()
                );
                break;
            case 'transferts':
                $rows = $this->formatTransactions(
                    $this->buildJournalQuery($start, $end, $caisseId, $typeCaisse)
                    ->whereIn('movement_type', [
                        CaisseTransaction::MOV_TRANSFERT_ENTRANT,
                        CaisseTransaction::MOV_TRANSFERT_SORTANT,
                    ])->get()
                );
                break;
            case 'par_client':
                $query = $this->buildJournalQuery($start, $end, $caisseId, $typeCaisse)
                    ->where('movement_type', CaisseTransaction::MOV_ENTREE_CLIENT);
                if ($clientId) {
                    $query->whereHas('payment', function ($q) use ($clientId) {
                        $q->where('client_id', $clientId);
                    });
                }
                $rows = $this->formatTransactions($query->get());
                break;
            case 'par_type_caisse':
                $query = $this->buildJournalQuery($start, $end, null, $typeCaisse);
                $rows = $this->formatTransactions($query->get());
                break;
            case 'par_periode':
                $rows = $this->groupByPeriod($start, $end, $period ?: 'day', $caisseId, $typeCaisse);
                break;
            case 'comparatif':
                $rows = $this->comparatif($start, $end, $caisseId, $typeCaisse);
                break;
            case 'soldes':
                $rows = $this->soldesParCaisse($end);
                break;
            default:
                return response()->json(['message' => 'Type de rapport invalide'], 422);
        }

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $dataset = $this->fetchForExport($request);
        $title = $request->string('title') ?: 'Rapport';
        $pdf = Pdf::loadView('reports.generic', [
            'title' => $title,
            'columns' => $dataset['columns'],
            'rows' => $dataset['rows'],
            'generated_at' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($this->makeFilename($title, 'pdf'));
    }

    public function exportExcel(Request $request)
    {
        $dataset = $this->fetchForExport($request);
        $title = $request->string('title') ?: 'Rapport';
        return Excel::download(new GenericReportExport($dataset['columns'], $dataset['rows']), $this->makeFilename($title, 'xlsx'));
    }

    private function fetchForExport(Request $request): array
    {
        $json = $this->data($request)->getData(true);
        $rows = collect($json['data']);

        // Normalize rows to flat arrays for export
        if ($rows->isEmpty()) {
            return ['columns' => [], 'rows' => []];
        }

        $first = (array) $rows->first();
        $columns = array_keys($first);
        $flatRows = $rows->map(fn($r) => (array) $r)->values()->all();
        return ['columns' => $columns, 'rows' => $flatRows];
    }

    private function buildJournalQuery($start, $end, $caisseId = null, $typeCaisse = null)
    {
        $query = CaisseTransaction::query()
            ->with(['caisse:id,name,type', 'payment:id,client_id', 'payment.client:id,name', 'transfer:id,from_caisse_id,to_caisse_id'])
            ->whereBetween('date', [$start, $end])
            ->orderBy('date');

        if ($caisseId) {
            $query->where('caisse_id', $caisseId);
        }

        if ($typeCaisse) {
            $query->whereHas('caisse', function ($q) use ($typeCaisse) {
                $q->where('type', $typeCaisse);
            });
        }

        return $query->select([
            'id', 'date', 'caisse_id', 'movement_type', 'type', 'amount', 'objet', 'description', 'payment_id', 'caisse_transfer_id'
        ]);
    }

    private function formatTransactions($collection)
    {
        return $collection->map(function ($t) {
            return [
                'date' => optional($t->date)->format('Y-m-d'),
                'caisse' => optional($t->caisse)->name,
                'type_caisse' => optional($t->caisse)->type,
                'movement_type' => $t->movement_type,
                'type' => $t->type,
                'amount' => (float) $t->amount,
                'objet' => $t->objet,
                'description' => $t->description,
                'client' => optional(optional($t->payment)->client)->name,
            ];
        })->values();
    }

    private function groupByPeriod($start, $end, string $period, $caisseId = null, $typeCaisse = null)
    {
        $query = $this->buildJournalQuery($start, $end, $caisseId, $typeCaisse);
        $rows = $query->get()->map(function ($t) {
            return [
                'date' => $t->date,
                'movement_type' => $t->movement_type,
                'amount' => (float) $t->amount,
            ];
        });

        $grouped = $rows->groupBy(function ($row) use ($period) {
            $d = Carbon::parse($row['date']);
            return match ($period) {
                'week' => $d->startOfWeek()->format('Y-m-d'),
                'month' => $d->format('Y-m'),
                'quarter' => $d->format('Y') . 'Q' . $d->quarter,
                default => $d->format('Y-m-d'),
            };
        })->map(function (Collection $items, $key) {
            return [
                'periode' => $key,
                'entrees' => $items->whereIn('movement_type', [
                    CaisseTransaction::MOV_ENTREE_CLIENT,
                    CaisseTransaction::MOV_ENTREE_AUTRE,
                    CaisseTransaction::MOV_TRANSFERT_ENTRANT,
                ])->sum('amount'),
                'sorties' => $items->whereIn('movement_type', [
                    CaisseTransaction::MOV_SORTIE,
                    CaisseTransaction::MOV_TRANSFERT_SORTANT,
                ])->sum('amount'),
                'net' => $items->whereIn('movement_type', [
                        CaisseTransaction::MOV_ENTREE_CLIENT,
                        CaisseTransaction::MOV_ENTREE_AUTRE,
                        CaisseTransaction::MOV_TRANSFERT_ENTRANT,
                    ])->sum('amount')
                    - $items->whereIn('movement_type', [
                        CaisseTransaction::MOV_SORTIE,
                        CaisseTransaction::MOV_TRANSFERT_SORTANT,
                    ])->sum('amount'),
            ];
        })->values()->sortBy('periode')->values();

        return $grouped;
    }

    private function comparatif($start, $end, $caisseId = null, $typeCaisse = null)
    {
        // Compare current period sum vs previous period sum (same length)
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        $days = $start->diffInDays($end) + 1;
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);

        $sumFor = function ($s, $e) use ($caisseId, $typeCaisse) {
            $q = $this->buildJournalQuery($s, $e, $caisseId, $typeCaisse);
            $rows = $q->get();
            $entrees = $rows->whereIn('movement_type', [
                CaisseTransaction::MOV_ENTREE_CLIENT,
                CaisseTransaction::MOV_ENTREE_AUTRE,
                CaisseTransaction::MOV_TRANSFERT_ENTRANT,
            ])->sum('amount');
            $sorties = $rows->whereIn('movement_type', [
                CaisseTransaction::MOV_SORTIE,
                CaisseTransaction::MOV_TRANSFERT_SORTANT,
            ])->sum('amount');
            return [
                'entrees' => $entrees,
                'sorties' => $sorties,
                'net' => $entrees - $sorties,
            ];
        };

        $current = $sumFor($start, $end);
        $previous = $sumFor($prevStart, $prevEnd);

        return [
            'periode_actuelle' => [$start->toDateString(), $end->toDateString()],
            'periode_precedente' => [$prevStart->toDateString(), $prevEnd->toDateString()],
            'current' => $current,
            'previous' => $previous,
            'variation' => [
                'entrees' => $this->variation($previous['entrees'], $current['entrees']),
                'sorties' => $this->variation($previous['sorties'], $current['sorties']),
                'net' => $this->variation($previous['net'], $current['net']),
            ],
        ];
    }

    private function variation($old, $new)
    {
        if ((float)$old === 0.0) {
            return $new > 0 ? 100.0 : 0.0;
        }
        return round((($new - $old) / $old) * 100, 2);
    }

    private function soldesParCaisse($atDate)
    {
        $at = Carbon::parse($atDate)->endOfDay();
        $caisses = Caisse::withSum(['transactions as entrees_sum' => function ($q) use ($at) {
            $q->where('date', '<=', $at)
              ->whereIn('movement_type', [
                  CaisseTransaction::MOV_ENTREE_CLIENT,
                  CaisseTransaction::MOV_ENTREE_AUTRE,
                  CaisseTransaction::MOV_TRANSFERT_ENTRANT,
              ]);
        }], 'amount')
            ->withSum(['transactions as sorties_sum' => function ($q) use ($at) {
                $q->where('date', '<=', $at)
                  ->whereIn('movement_type', [
                      CaisseTransaction::MOV_SORTIE,
                      CaisseTransaction::MOV_TRANSFERT_SORTANT,
                  ]);
            }], 'amount')
            ->get(['id','name','type','initial_balance']);

        return $caisses->map(function ($c) {
            $entrees = (float) ($c->entrees_sum ?? 0);
            $sorties = (float) ($c->sorties_sum ?? 0);
            $solde = (float) ($c->initial_balance ?? 0) + $entrees - $sorties;
            return [
                'caisse_id' => $c->id,
                'caisse' => $c->name,
                'type' => $c->type,
                'entrees' => $entrees,
                'sorties' => $sorties,
                'solde' => $solde,
            ];
        });
    }

    private function parseDateRange(Request $request): array
    {
        $start = $request->date('start_date') ?: now()->startOfMonth();
        $end = $request->date('end_date') ?: now()->endOfMonth();
        return [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()];
    }

    private function makeFilename(string $title, string $ext): string
    {
        $slug = str_replace([' ', '/','\\'], '-', strtolower($title));
        return $slug . '-' . now()->format('Ymd-His') . '.' . $ext;
    }
}
