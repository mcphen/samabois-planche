<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlancheBonLivraisonRequest;
use App\Http\Requests\UpdatePlancheBonLivraisonRequest;
use App\Models\Client;
use App\Models\Contrat;
use App\Models\Epaisseur;
use App\Models\PlancheBenefitHistory;
use App\Models\PlancheBonLivraison;
use App\Models\PlancheDetail;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Inertia\Inertia;

class PlancheBonLivraisonController extends Controller
{
    public function index()
    {
        return Inertia::render('PlancheBonsLivraison/Index', [
            'suppliers' => Supplier::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('PlancheBonsLivraison/Create', [
            'suppliers' => Supplier::query()->select('id', 'name')->orderBy('name')->get(),
            'clients' => Client::query()->select('id', 'name', 'slug')->orderBy('name')->get(),
            'epaisseurs' => Epaisseur::query()->orderBy('intitule')->get(['id', 'intitule', 'slug']),
            'contrats' => Contrat::query()->select('id', 'numero')->orderBy('numero')->get(),
            'availableDetails' => [],
        ]);
    }

    public function generatePDF(PlancheBonLivraison $plancheBonLivraison)
    {
        $plancheBonLivraison->load($this->bonRelations());

        $pdf = PDF::loadView('planche-bons-livraison.pdf', ['bon' => $plancheBonLivraison]);

        $safeNumeroBl = str_replace(['/', '\\'], '-', $plancheBonLivraison->numero_bl);

        return $pdf->stream("bon-livraison_{$safeNumeroBl}.pdf");
    }

    public function show(PlancheBonLivraison $plancheBonLivraison)
    {
        $plancheBonLivraison->load($this->bonRelations());

        return Inertia::render('PlancheBonsLivraison/Show', [
            'bonLivraison' => $this->decorateBonLivraison($plancheBonLivraison),
            'availableDetails' => $plancheBonLivraison->statut === 'brouillon'
                ? $this->availableDetailsCollection(null, '', '', '', '', $plancheBonLivraison->id)
                : [],
            'suppliers' => Supplier::query()->select('id', 'name')->orderBy('name')->get(),
            'clients' => Client::query()->select('id', 'name', 'slug')->orderBy('name')->get(),
        ]);
    }

    public function edit(PlancheBonLivraison $plancheBonLivraison)
    {
        $plancheBonLivraison->load($this->bonRelations());

        return Inertia::render('PlancheBonsLivraison/Edit', [
            'bonLivraison' => $this->decorateBonLivraison($plancheBonLivraison),
            'availableDetails' => $this->availableDetailsCollection(null, '', '', '', '', $plancheBonLivraison->id),
            'suppliers' => Supplier::query()->select('id', 'name')->orderBy('name')->get(),
            'clients' => Client::query()->select('id', 'name', 'slug')->orderBy('name')->get(),
            'epaisseurs' => Epaisseur::query()->orderBy('intitule')->get(['id', 'intitule', 'slug']),
            'contrats' => Contrat::query()->select('id', 'numero')->orderBy('numero')->get(),
        ]);
    }

    public function getBonsLivraison(Request $request)
    {
        $query = PlancheBonLivraison::query()
            ->with(['client:id,name'])
            ->with($this->bonRelations())
            ->withCount('lignes')
            ->latest();

        if ($request->filled('numero_bl')) {
            $query->where('numero_bl', 'like', '%' . trim($request->string('numero_bl')->toString()) . '%');
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut')->toString());
        }

        if ($request->filled('date_livraison')) {
            $query->whereDate('date_livraison', $request->date('date_livraison'));
        }

        $bons = $query->paginate(20);
        $bons->getCollection()->transform(fn (PlancheBonLivraison $bon) => $this->decorateBonLivraison($bon));

        return response()->json($bons);
    }

    public function getAvailableDetails(Request $request)
    {
        return response()->json(
            $this->availableDetailsCollection(
                $request->integer('supplier_id') ?: null,
                $request->string('numero_contrat')->toString(),
                $request->string('code_couleur')->toString(),
                $request->string('categorie')->toString(),
                $request->string('epaisseur')->toString(),
                $request->integer('exclude_bon_livraison_id') ?: null,
                $request->integer('contrat_id') ?: null
            )
        );
    }

    public function store(StorePlancheBonLivraisonRequest $request)
    {
        $validated = $request->validated();
        $contratMap = Contrat::query()
            ->whereIn('numero', collect($validated['lignes'])->pluck('contrat')->filter()->unique())
            ->pluck('id', 'numero');

        $bonLivraison = DB::transaction(function () use ($request, $contratMap) {
            $bonLivraison = PlancheBonLivraison::query()->create([
                'client_id' => $request->integer('client_id'),
                'numero_bl' => $this->generateNumeroBl(),
                'date_livraison' => $request->input('date_livraison'),
                'statut' => 'valide',
            ]);

            $bonLivraison->lignes()->createMany(
                collect($request->validated('lignes'))
                    ->map(fn (array $ligne) => $this->mapBonLivraisonLinePayload($ligne, $contratMap))
                    ->values()
                    ->all()
            );

            $bonLivraison->recalculerMontant();

            Transaction::create([
                'client_id'                => $bonLivraison->client_id,
                'type'                     => 'invoice',
                'amount'                   => $bonLivraison->montant,
                'transaction_date'         => $bonLivraison->date_livraison,
                'planche_bon_livraison_id' => $bonLivraison->id,
            ]);

            $bonLivraison->load('lignes.plancheDetail:id,prix_de_revient');
            PlancheBenefitHistory::create([
                'user_id' => auth()->id(),
                'planche_bon_livraison_id' => $bonLivraison->id,
                'action' => 'bon_livraison_created',
                'new_data' => [
                    'montant' => $bonLivraison->montant,
                    'lines' => $bonLivraison->lignes->map(fn ($ligne) => [
                        'planche_detail_id'  => $ligne->planche_detail_id,
                        'prix_unitaire'      => (float) $ligne->prix_unitaire,
                        'prix_total'         => (float) $ligne->prix_total,
                        'prix_de_revient'    => $ligne->plancheDetail?->prix_de_revient,
                    ])->all(),
                ],
            ]);

            return $bonLivraison;
        });

        return response()->json([
            'message' => 'Facture enregistree avec succes.',
            'data' => [
                'id' => $bonLivraison->id,
                'redirect_to' => route('planche-bons-livraison.show', $bonLivraison),
            ],
        ], 201);
    }

    public function update(UpdatePlancheBonLivraisonRequest $request, PlancheBonLivraison $plancheBonLivraison)
    {
        $plancheBonLivraison = DB::transaction(function () use ($request, $plancheBonLivraison) {
            $plancheBonLivraison->load('lignes.plancheDetail:id,prix_de_revient');
            $oldLines = $plancheBonLivraison->lignes->map(fn ($ligne) => [
                'planche_bon_livraison_ligne_id' => $ligne->id,
                'planche_detail_id'  => $ligne->planche_detail_id,
                'prix_unitaire'      => (float) $ligne->prix_unitaire,
                'prix_total'         => (float) $ligne->prix_total,
                'prix_de_revient'    => $ligne->plancheDetail?->prix_de_revient,
            ])->all();
            $oldMontant = (float) $plancheBonLivraison->montant;

            $plancheBonLivraison->update([
                'client_id' => $request->integer('client_id'),
                'date_livraison' => $request->input('date_livraison'),
                'statut' => $request->string('statut')->toString(),
            ]);

            $validated = $request->validated();
            $contratMap = Contrat::query()
                ->whereIn('numero', collect($validated['lignes'])->pluck('contrat')->filter()->unique())
                ->pluck('id', 'numero');

            $plancheBonLivraison->lignes()->delete();
            $plancheBonLivraison->lignes()->createMany(
                collect($validated['lignes'])
                    ->map(fn (array $ligne) => $this->mapBonLivraisonLinePayload($ligne, $contratMap))
                    ->values()
                    ->all()
            );

            $plancheBonLivraison->recalculerMontant();
            $plancheBonLivraison->load('lignes.plancheDetail:id,prix_de_revient');
            $newLines = $plancheBonLivraison->lignes->map(fn ($ligne) => [
                'planche_bon_livraison_ligne_id' => $ligne->id,
                'planche_detail_id'  => $ligne->planche_detail_id,
                'prix_unitaire'      => (float) $ligne->prix_unitaire,
                'prix_total'         => (float) $ligne->prix_total,
                'prix_de_revient'    => $ligne->plancheDetail?->prix_de_revient,
            ])->all();

            if (json_encode($oldLines) !== json_encode($newLines) || $oldMontant !== (float) $plancheBonLivraison->montant) {
                PlancheBenefitHistory::create([
                    'user_id' => auth()->id(),
                    'planche_bon_livraison_id' => $plancheBonLivraison->id,
                    'action' => 'bon_livraison_updated',
                    'old_data' => [
                        'montant' => $oldMontant,
                        'lines' => $oldLines,
                    ],
                    'new_data' => [
                        'montant' => (float) $plancheBonLivraison->montant,
                        'lines' => $newLines,
                    ],
                ]);
            }

            // Mettre à jour la transaction liée
            $plancheBonLivraison->transactions()
                ->where('type', 'invoice')
                ->update([
                    'client_id'        => $plancheBonLivraison->client_id,
                    'amount'           => $plancheBonLivraison->montant,
                    'transaction_date' => $plancheBonLivraison->date_livraison,
                ]);

            return $plancheBonLivraison->fresh();
        });

        return response()->json([
            'message' => 'Facture mise a jour avec succes.',
            'data' => [
                'id' => $plancheBonLivraison->id,
                'redirect_to' => route('planche-bons-livraison.show', $plancheBonLivraison),
            ],
        ]);
    }

    public function destroy(PlancheBonLivraison $plancheBonLivraison)
    {
        DB::transaction(function () use ($plancheBonLivraison) {
            // Supprimer toutes les transactions liées
            $plancheBonLivraison->transactions()->delete();

            $plancheBonLivraison->delete();
        });

        return response()->json([
            'message' => 'Facture supprimee avec succes.',
            'data' => [
                'redirect_to' => route('planche-bons-livraison.index'),
            ],
        ]);
    }

    private function bonRelations(): array
    {
        return [
            'client:id,name',
            'lignes:id,planche_bon_livraison_id,planche_detail_id,quantite_livree,prix_unitaire,prix_total',
            'lignes.plancheDetail:id,planche_id,planche_couleur_id,categorie,epaisseur,quantite_prevue,prix_de_revient',
            'lignes.plancheDetail.couleur:id,code',
            'lignes.plancheDetail.planche:id,contrat_id',
            'lignes.plancheDetail.planche.contrat:id,supplier_id,numero',
            'lignes.plancheDetail.planche.contrat.supplier:id,name',
        ];
    }

    private function availableDetailsCollection(
        ?int $supplierId = null,
        string $numeroContrat = '',
        string $codeCouleur = '',
        string $categorie = '',
        string $epaisseur = '',
        ?int $excludeBonLivraisonId = null,
        ?int $contratId = null
    ) {
        $details = PlancheDetail::query()
            ->with([
                'couleur:id,code',
                'planche:id,contrat_id',
                'planche.contrat:id,supplier_id,numero',
                'planche.contrat.supplier:id,name',
            ])
            ->withSum([
                'bonLivraisonLignes as total_quantite_livree' => function ($query) use ($excludeBonLivraisonId) {
                    if ($excludeBonLivraisonId) {
                        $query->where('planche_bon_livraison_id', '!=', $excludeBonLivraisonId);
                    }
                },
            ], 'quantite_livree')
            ->when($contratId, function ($query) use ($contratId) {
                $query->whereHas('planche.contrat', function ($contratQuery) use ($contratId) {
                    $contratQuery->where('id', $contratId);
                });
            })
            ->when(!$contratId && $supplierId, function ($query) use ($supplierId) {
                $query->whereHas('planche.contrat', function ($contratQuery) use ($supplierId) {
                    $contratQuery->where('supplier_id', $supplierId);
                });
            })
            ->when(!$contratId && trim($numeroContrat) !== '', function ($query) use ($numeroContrat) {
                $query->whereHas('planche.contrat', function ($contratQuery) use ($numeroContrat) {
                    $contratQuery->where('numero', 'like', '%' . trim($numeroContrat) . '%');
                });
            })
            ->when(trim($codeCouleur) !== '', function ($query) use ($codeCouleur) {
                $query->whereHas('couleur', function ($couleurQuery) use ($codeCouleur) {
                    $couleurQuery->where('code', 'like', '%' . trim($codeCouleur) . '%');
                });
            })
            ->when(trim($categorie) !== '', function ($query) use ($categorie) {
                $query->where('categorie', trim($categorie));
            })
            ->when(trim($epaisseur) !== '', function ($query) use ($epaisseur) {
                $query->where('epaisseur', (float) str_replace(',', '.', trim($epaisseur)));
            })
            ->get();

        return $details
            ->map(function (PlancheDetail $detail) {
                $quantiteLivree     = (int) ($detail->total_quantite_livree ?? 0);
                $quantiteDisponible = max((int) $detail->quantite_prevue - $quantiteLivree, 0);

                return [
                    'id'                    => $detail->id,
                    'supplier_name'         => $detail->planche?->contrat?->supplier?->name,
                    'numero_contrat'        => $detail->planche?->contrat?->numero,
                    'code_couleur'          => $detail->couleur?->code,
                    'categorie'             => $detail->categorie,
                    'epaisseur'             => $detail->epaisseur,
                    'quantite_prevue'       => (int) $detail->quantite_prevue,
                    'quantite_livree_total' => $quantiteLivree,
                    'quantite_disponible'   => $quantiteDisponible,
                ];
            })
            ->filter(fn (array $detail) => $detail['quantite_disponible'] > 0)
            ->sortBy([
                ['supplier_name', 'asc'],
                ['numero_contrat', 'asc'],
                ['code_couleur', 'asc'],
                ['categorie', 'asc'],
                ['epaisseur', 'asc'],
            ])
            ->values();
    }

    private function decorateBonLivraison(PlancheBonLivraison $bonLivraison): array
    {
        $lignes = $bonLivraison->lignes->map(function ($ligne) {
            $prixDeRevient    = $ligne->plancheDetail?->prix_de_revient !== null ? (float) $ligne->plancheDetail->prix_de_revient : null;
            $beneficeUnitaire = ($prixDeRevient !== null) ? ((float) $ligne->prix_unitaire - $prixDeRevient) : null;
            $beneficeTotal    = ($beneficeUnitaire !== null) ? $beneficeUnitaire * (int) $ligne->quantite_livree : null;

            return [
                'id'                => $ligne->id,
                'quantite_livree'   => (int) $ligne->quantite_livree,
                'prix_unitaire'     => (float) $ligne->prix_unitaire,
                'prix_total'        => (float) $ligne->prix_total,
                'detail_id'         => $ligne->plancheDetail?->id,
                'supplier_name'     => $ligne->plancheDetail?->planche?->contrat?->supplier?->name,
                'numero_contrat'    => $ligne->plancheDetail?->planche?->contrat?->numero,
                'code_couleur'      => $ligne->plancheDetail?->couleur?->code,
                'categorie'         => $ligne->plancheDetail?->categorie,
                'epaisseur'         => $ligne->plancheDetail?->epaisseur,
                'quantite_prevue'   => (int) ($ligne->plancheDetail?->quantite_prevue ?? 0),
                'prix_de_revient'   => $prixDeRevient,
                'benefice_unitaire' => $beneficeUnitaire,
                'benefice_total'    => $beneficeTotal,
            ];
        })->values();

        return [
            'id'                     => $bonLivraison->id,
            'client_id'              => $bonLivraison->client_id,
            'client_name'            => $bonLivraison->client?->name,
            'numero_bl'              => $bonLivraison->numero_bl,
            'date_livraison'         => optional($bonLivraison->date_livraison)->format('Y-m-d'),
            'statut'                 => $bonLivraison->statut,
            'status'                 => $bonLivraison->status,
            'montant_solde'          => (float) $bonLivraison->montant_solde,
            'can_edit'               => true,
            'can_cancel'             => true,
            'lignes_count'           => $lignes->count(),
            'quantite_totale_livree' => $lignes->sum('quantite_livree'),
            'montant_total'          => (float) $lignes->sum('prix_total'),
            'benefice_total'         => $lignes->every(fn ($l) => $l['benefice_total'] === null)
                                            ? null
                                            : (float) $lignes->sum(fn ($l) => $l['benefice_total'] ?? 0),
            'contrats'               => $lignes->pluck('numero_contrat')->filter()->unique()->values(),
            'fournisseurs'           => $lignes->pluck('supplier_name')->filter()->unique()->values(),
            'lignes'                 => $lignes,
        ];
    }

    private function mapBonLivraisonLinePayload(array $ligne, $contratMap): array
    {
        $quantiteLivree = (int) $ligne['quantite_livree'];
        $prixUnitaire = round((float) $ligne['prix_unitaire'], 2);

        return [
            'planche_detail_id' => $ligne['planche_detail_id'],
            'contrat_id' => $contratMap->get($ligne['contrat']),
            'quantite_livree' => $quantiteLivree,
            'prix_unitaire' => $prixUnitaire,
            'prix_total' => round($quantiteLivree * $prixUnitaire, 2),
        ];
    }

    private function generateNumeroBl(): string
    {
        $year = now()->year;

        $last = PlancheBonLivraison::query()
            ->whereYear('created_at', $year)
            ->where('numero_bl', 'like', "%/{$year}")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $next = 1;

        if ($last && preg_match('/^(\d+)\/\d{4}$/', $last->numero_bl, $matches)) {
            $next = (int) $matches[1] + 1;
        }

        return "{$next}/{$year}";
    }

}
