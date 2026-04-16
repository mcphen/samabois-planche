<?php

namespace App\Http\Controllers;

use App\Models\Contrat;
use App\Models\Epaisseur;
use App\Models\Planche;
use App\Models\PlancheBenefitHistory;
use App\Models\PlancheDetail;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ContratController extends Controller
{
    public function index()
    {
        return Inertia::render('Contrats/Index', [
            'suppliers' => Supplier::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Request $request, Contrat $contrat)
    {
        $contrat->load($this->contratRelations());

        $contrat = $this->decorateContrat($contrat);
        $activePlancheId = $request->integer('planche') ?: ($contrat->planches->first()->id ?? null);

        return Inertia::render('Contrats/Show', [
            'contrat' => $contrat,
            'active_planche_id' => $activePlancheId,
            'epaisseurs' => Epaisseur::query()->orderBy('intitule')->get(['id', 'intitule', 'slug']),
            'suppliers' => Supplier::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'userRole' => auth()->user()->role,
        ]);
    }

    public function update(Request $request, Contrat $contrat)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'numero' => [
                'required',
                'string',
                'max:255',
                Rule::unique('contrats')->where(fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')))->ignore($contrat->id),
            ],
        ], [
            'supplier_id.required' => 'Veuillez selectionner un fournisseur.',
            'supplier_id.exists' => 'Le fournisseur selectionne est invalide.',
            'numero.required' => 'Veuillez renseigner le numero du contrat.',
            'numero.unique' => 'Ce numero de contrat existe deja pour ce fournisseur.',
        ]);

        $contrat->update($validated);
        $contrat->load($this->contratRelations());

        return response()->json([
            'message' => 'Contrat mis a jour avec succes.',
            'data' => [
                'redirect_to' => route('contrats.show', $contrat),
            ],
        ]);
    }

    public function checkNumero(Request $request)
    {
        $numero = $request->string('numero');
        
        if (!$numero || $numero->length() < 2) {
            return response()->json(['valid' => false]);
        }

        $exists = Contrat::where('numero', (string) $numero)->exists();
        
        return response()->json(['valid' => $exists]);
    }

    public function getContrats(Request $request)
    {
        $query = Contrat::query()
            ->with($this->contratRelations())
            ->latest();

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('numero_contrat')) {
            $query->where('numero', 'like', '%' . trim($request->string('numero_contrat')->toString()) . '%');
        }

        if ($request->filled('code_couleur')) {
            $codeCouleur = trim($request->string('code_couleur')->toString());

            $query->whereHas('planches.details.couleur', function ($couleurQuery) use ($codeCouleur) {
                $couleurQuery->where('code', 'like', '%' . $codeCouleur . '%');
            });
        }

        if ($request->filled('client_id')) {
            $query->whereHas('planches.details.bonLivraisonLignes.bonLivraison', function ($q) use ($request) {
                $q->where('client_id', $request->integer('client_id'));
            });
        }

        $contrats = $query->paginate(20);
        $contrats->setCollection(
            $contrats->getCollection()->map(fn (Contrat $item) => $this->decorateContrat($item))
        );

        return response()->json($contrats);
    }

    public function getBenefitHistory(Contrat $contrat)
    {
        $history = PlancheBenefitHistory::query()
            ->with([
                'user:id,name',
                'plancheDetail:id,planche_id,planche_couleur_id,categorie,epaisseur',
                'plancheDetail.couleur:id,code',
                'plancheBonLivraison:id,numero_bl',
            ])
            ->where(function ($query) use ($contrat) {
                $query->whereHas('plancheDetail.planche', fn ($query) => $query->where('contrat_id', $contrat->id))
                    ->orWhereHas('plancheBonLivraison.lignes.plancheDetail.planche', fn ($query) => $query->where('contrat_id', $contrat->id));
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function (PlancheBenefitHistory $entry) {
                return [
                    'id' => $entry->id,
                    'action' => $entry->action,
                    'user' => $entry->user?->name,
                    'created_at' => optional($entry->created_at)->format('Y-m-d H:i'),
                    'planche_detail' => $entry->plancheDetail ? [
                        'id' => $entry->plancheDetail->id,
                        'categorie' => $entry->plancheDetail->categorie,
                        'epaisseur' => $entry->plancheDetail->epaisseur,
                        'code_couleur' => $entry->plancheDetail->couleur?->code,
                    ] : null,
                    'bon_livraison' => $entry->plancheBonLivraison ? [
                        'id' => $entry->plancheBonLivraison->id,
                        'numero_bl' => $entry->plancheBonLivraison->numero_bl,
                    ] : null,
                    'old_data' => $entry->old_data,
                    'new_data' => $entry->new_data,
                    'notes' => $entry->notes,
                ];
            });

        return response()->json($history);
    }

    private function contratRelations(): array
    {
        return [
            'supplier:id,name,address,phone,email',
            'planches' => function ($plancheQuery) {
                $plancheQuery
                    ->select('id', 'contrat_id', 'created_at')
                    ->with([
                        'details' => function ($detailQuery) {
                            $detailQuery
                                ->select('id', 'planche_id', 'planche_couleur_id', 'categorie', 'epaisseur', 'quantite_prevue', 'prix_de_revient')
                                ->with(['couleur:id,code,image_path'])
                                ->withSum('bonLivraisonLignes as total_quantite_livree', 'quantite_livree')
                                ->withSum('bonLivraisonLignes as total_prix_total', 'prix_total')
                                ->orderBy('epaisseur');
                        },
                    ])
                    ->latest();
            },
        ];
    }

    private function decorateContrat(Contrat $contrat): Contrat
    {
        $planches = $contrat->planches
            ->map(fn (Planche $planche) => $this->decoratePlanche($planche))
            ->sortBy(fn (Planche $planche) => ($planche->code_couleur ?? '') . '|' . ($planche->categorie ?? ''))
            ->values();

        $contrat->setRelation('planches', $planches);
        $contrat->setAttribute('total_planches', $planches->count());
        $contrat->setAttribute('total_details', $planches->sum(fn (Planche $planche) => $planche->details->count()));
        $contrat->setAttribute('total_quantite_prevue', $planches->sum(fn (Planche $planche) => (int) ($planche->total_quantite_prevue ?? 0)));
        $contrat->setAttribute('total_quantite_livree', $planches->sum(fn (Planche $planche) => (int) ($planche->total_quantite_livree ?? 0)));
        $contrat->setAttribute('total_quantite_disponible', $planches->sum(fn (Planche $planche) => (int) ($planche->total_quantite_disponible ?? 0)));

        return $contrat;
    }

    private function decoratePlanche(Planche $planche): Planche
    {
        $details = $planche->details
            ->map(fn (PlancheDetail $detail) => $this->decorateDetail($detail))
            ->sortBy(fn (PlancheDetail $detail) => (float) ($detail->epaisseur ?? 0))
            ->values();

        $firstDetail = $details->first();
        $couleur = $firstDetail?->couleur;
        $categorie = $firstDetail?->categorie;

        $planche->setRelation('details', $details);
        $planche->setRelation('couleur', $couleur);
        $planche->setAttribute('code_couleur', $this->extractCouleurCode($couleur));
        $planche->setAttribute('categorie', $categorie);
        $planche->setAttribute('total_quantite_prevue', $details->sum(fn (PlancheDetail $detail) => (int) $detail->quantite_prevue));
        $planche->setAttribute('total_quantite_livree', $details->sum(fn (PlancheDetail $detail) => (int) ($detail->total_quantite_livree ?? 0)));
        $planche->setAttribute('total_quantite_disponible', $details->sum(fn (PlancheDetail $detail) => (int) ($detail->quantite_disponible ?? 0)));

        return $planche;
    }

    private function decorateDetail(PlancheDetail $detail): PlancheDetail
    {
        $quantiteLivree = (int) ($detail->total_quantite_livree ?? 0);
        $totalPrixTotal = (float) ($detail->total_prix_total ?? 0);

        $detail->setAttribute('total_quantite_livree', $quantiteLivree);
        $detail->setAttribute('quantite_disponible', max((int) $detail->quantite_prevue - $quantiteLivree, 0));
        $detail->setAttribute('total_prix_total', $totalPrixTotal);
        $detail->setAttribute('cout_total', $detail->prix_de_revient !== null ? (float) $detail->prix_de_revient * $quantiteLivree : null);
        $detail->setAttribute('profit_total', $detail->prix_de_revient !== null ? $totalPrixTotal - ((float) $detail->prix_de_revient * $quantiteLivree) : null);

        return $detail;
    }

    private function extractCouleurCode($couleur): ?string
    {
        if (! $couleur) {
            return null;
        }

        $code = $couleur->getAttribute('code') ?: $couleur->getAttribute('code_couleur');

        return $code ? trim((string) $code) : null;
    }
}
