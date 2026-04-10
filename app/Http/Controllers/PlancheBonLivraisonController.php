<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlancheBonLivraisonRequest;
use App\Http\Requests\UpdatePlancheBonLivraisonRequest;
use App\Models\Client;
use App\Models\Epaisseur;
use App\Models\Invoice;
use App\Models\PlancheBonLivraison;
use App\Models\PlancheDetail;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
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
            'clients' => Client::query()->select('id', 'name')->orderBy('name')->get(),
            'epaisseurs' => Epaisseur::query()->orderBy('intitule')->get(['id', 'intitule', 'slug']),
            'availableDetails' => [],
        ]);
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
            'clients' => Client::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function getBonsLivraison(Request $request)
    {
        $query = PlancheBonLivraison::query()
            ->with(['client:id,name', 'invoice:id,client_id,planche_bon_livraison_id,matricule'])
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
        $bons->setCollection(
            $bons->getCollection()->map(fn (PlancheBonLivraison $bon) => $this->decorateBonLivraison($bon))
        );

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
                $request->integer('exclude_bon_livraison_id') ?: null
            )
        );
    }

    public function store(StorePlancheBonLivraisonRequest $request)
    {
        $bonLivraison = DB::transaction(function () use ($request) {
            $bonLivraison = PlancheBonLivraison::query()->create([
                'client_id' => $request->integer('client_id'),
                'numero_bl' => $this->generateNumeroBl(),
                'date_livraison' => $request->input('date_livraison'),
                'statut' => 'valide',
            ]);

            $bonLivraison->lignes()->createMany(
                collect($request->validated('lignes'))
                    ->map(fn (array $ligne) => $this->mapBonLivraisonLinePayload($ligne))
                    ->values()
                    ->all()
            );

            if ($bonLivraison->statut === 'valide') {
                $this->createInvoiceFromBonLivraison($bonLivraison);
            }

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
        $this->ensureBonLivraisonIsDraft($plancheBonLivraison);

        $plancheBonLivraison = DB::transaction(function () use ($request, $plancheBonLivraison) {
            $plancheBonLivraison->update([
                'client_id' => $request->integer('client_id'),
                'date_livraison' => $request->input('date_livraison'),
                'statut' => $request->string('statut')->toString(),
            ]);

            $plancheBonLivraison->lignes()->delete();
            $plancheBonLivraison->lignes()->createMany(
                collect($request->validated('lignes'))
                    ->map(fn (array $ligne) => $this->mapBonLivraisonLinePayload($ligne))
                    ->values()
                    ->all()
            );

            if ($plancheBonLivraison->statut === 'valide') {
                $this->createInvoiceFromBonLivraison($plancheBonLivraison);
            }

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
        $this->ensureBonLivraisonIsDraft($plancheBonLivraison);

        DB::transaction(function () use ($plancheBonLivraison) {
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
            'invoice:id,client_id,planche_bon_livraison_id,matricule',
            'lignes:id,planche_bon_livraison_id,planche_detail_id,quantite_livree,prix_unitaire,prix_total',
            'lignes.plancheDetail:id,planche_id,planche_couleur_id,categorie,epaisseur,quantite_prevue',
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
        ?int $excludeBonLivraisonId = null
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
            ->when($supplierId, function ($query) use ($supplierId) {
                $query->whereHas('planche.contrat', function ($contratQuery) use ($supplierId) {
                    $contratQuery->where('supplier_id', $supplierId);
                });
            })
            ->when(trim($numeroContrat) !== '', function ($query) use ($numeroContrat) {
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
            return [
                'id'              => $ligne->id,
                'quantite_livree' => (int) $ligne->quantite_livree,
                'prix_unitaire'   => (float) $ligne->prix_unitaire,
                'prix_total'      => (float) $ligne->prix_total,
                'detail_id'       => $ligne->plancheDetail?->id,
                'supplier_name'   => $ligne->plancheDetail?->planche?->contrat?->supplier?->name,
                'numero_contrat'  => $ligne->plancheDetail?->planche?->contrat?->numero,
                'code_couleur'    => $ligne->plancheDetail?->couleur?->code,
                'categorie'       => $ligne->plancheDetail?->categorie,
                'epaisseur'       => $ligne->plancheDetail?->epaisseur,
                'quantite_prevue' => (int) ($ligne->plancheDetail?->quantite_prevue ?? 0),
            ];
        })->values();

        return [
            'id' => $bonLivraison->id,
            'client_id' => $bonLivraison->client_id,
            'client_name' => $bonLivraison->client?->name,
            'numero_bl' => $bonLivraison->numero_bl,
            'date_livraison' => optional($bonLivraison->date_livraison)->format('Y-m-d'),
            'statut' => $bonLivraison->statut,
            'can_edit' => $bonLivraison->statut === 'brouillon',
            'invoice_id' => $bonLivraison->invoice?->id,
            'invoice_matricule' => $bonLivraison->invoice?->matricule,
            'lignes_count' => $lignes->count(),
            'quantite_totale_livree' => $lignes->sum('quantite_livree'),
            'montant_total' => (float) $lignes->sum('prix_total'),
            'contrats' => $lignes->pluck('numero_contrat')->filter()->unique()->values(),
            'fournisseurs' => $lignes->pluck('supplier_name')->filter()->unique()->values(),
            'lignes' => $lignes,
        ];
    }

    private function createInvoiceFromBonLivraison(PlancheBonLivraison $plancheBonLivraison): Invoice
    {
        $plancheBonLivraison->loadMissing('invoice', 'client');

        if ($plancheBonLivraison->invoice) {
            return $plancheBonLivraison->invoice;
        }

        $invoice = Invoice::query()->create([
            'client_id' => $plancheBonLivraison->client_id,
            'planche_bon_livraison_id' => $plancheBonLivraison->id,
            'date' => optional($plancheBonLivraison->date_livraison)->format('Y-m-d'),
            'total_price' => 0,
            'status' => 'pending',
            'montant_solde' => 0,
        ]);

        $invoice->updateTotalPrice();

        return $invoice;
    }

    private function ensureBonLivraisonIsDraft(PlancheBonLivraison $plancheBonLivraison): void
    {
        if ($plancheBonLivraison->statut !== 'brouillon') {
            throw ValidationException::withMessages([
                'statut' => 'Cette facture est valide et ne peut plus etre modifiee ou supprimee.',
            ]);
        }
    }

    private function mapBonLivraisonLinePayload(array $ligne): array
    {
        $quantiteLivree = (int) $ligne['quantite_livree'];
        $prixUnitaire = round((float) $ligne['prix_unitaire'], 2);

        return [
            'planche_detail_id' => $ligne['planche_detail_id'],
            'quantite_livree' => $quantiteLivree,
            'prix_unitaire' => $prixUnitaire,
            'prix_total' => round($quantiteLivree * $prixUnitaire, 2),
        ];
    }

    private function generateNumeroBl(): string
    {
        $datePrefix = now()->format('Ymd');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $suffix = strtoupper(Str::random(4));
            $numero = "BL-{$datePrefix}-{$suffix}";

            if (! PlancheBonLivraison::query()->where('numero_bl', $numero)->exists()) {
                return $numero;
            }
        }

        throw ValidationException::withMessages([
            'numero_bl' => 'Impossible de generer automatiquement un numero de facture unique. Veuillez reessayer.',
        ]);
    }
}
