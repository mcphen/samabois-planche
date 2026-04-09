<?php

namespace App\Http\Controllers;

use App\Models\Contrat;
use App\Models\Epaisseur;
use App\Models\Planche;
use App\Models\PlancheDetail;
use App\Models\Supplier;
use Illuminate\Http\Request;
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
        ]);
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

        $contrats = $query->paginate(20);
        $contrats->setCollection(
            $contrats->getCollection()->map(fn (Contrat $item) => $this->decorateContrat($item))
        );

        return response()->json($contrats);
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
                                ->select('id', 'planche_id', 'planche_couleur_id', 'categorie', 'epaisseur', 'quantite_prevue')
                                ->with(['couleur:id,code,image_path'])
                                ->withSum('bonLivraisonLignes as total_quantite_livree', 'quantite_livree')
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

        $detail->setAttribute('total_quantite_livree', $quantiteLivree);
        $detail->setAttribute('quantite_disponible', max((int) $detail->quantite_prevue - $quantiteLivree, 0));

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
