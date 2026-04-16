<?php

namespace App\Http\Controllers;

use App\Models\Contrat;
use App\Models\Epaisseur;
use App\Models\PlancheBonLivraisonLigne;
use App\Models\PlancheTarif;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PlancheTarifController extends Controller
{
    public function index(): \Inertia\Response
    {
        $tarifs = PlancheTarif::query()
            ->with('contrat:id,numero')
            ->orderBy('categorie')
            ->orderBy('epaisseur')
            ->get();

        $epaisseurs = Epaisseur::query()
            ->orderBy('intitule')
            ->get(['id', 'intitule', 'slug']);

        $contrats = Contrat::query()
            ->orderBy('numero')
            ->get(['id', 'numero']);

        return Inertia::render('Configuration/PlancheTarifs', [
            'tarifs'    => $tarifs,
            'epaisseurs' => $epaisseurs,
            'contrats'  => $contrats,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categorie'  => ['required', 'in:mate,semi_brillant,brillant'],
            'epaisseur'  => ['required', 'numeric', 'min:0.01'],
            'prix'       => ['required', 'numeric', 'min:0'],
            'contrat_id' => ['nullable', 'integer', 'exists:contrats,id'],
        ]);

        try {
            $tarif = PlancheTarif::query()->create($validated);
            PlancheTarif::clearCache();
            $this->backfillLignes($tarif);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Un tarif existe déjà pour cette combinaison catégorie/épaisseur/contrat.',
                    'errors' => ['epaisseur' => ['Ce tarif existe déjà pour cette combinaison.']],
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Tarif créé avec succès.',
            'data'    => $tarif->load('contrat:id,numero'),
        ], 201);
    }

    public function update(Request $request, PlancheTarif $plancheTarif): JsonResponse
    {
        $validated = $request->validate([
            'categorie'    => ['required', 'in:mate,semi_brillant,brillant'],
            'epaisseur'    => ['required', 'numeric', 'min:0.01'],
            'prix'         => ['required', 'numeric', 'min:0'],
            'contrat_id'   => ['nullable', 'integer', 'exists:contrats,id'],
            'update_lignes' => ['boolean'],
        ]);

        $updateLignes = (bool) ($validated['update_lignes'] ?? false);
        unset($validated['update_lignes']);

        try {
            $plancheTarif->update($validated);
            PlancheTarif::clearCache();

            if ($updateLignes) {
                $this->overwriteAllLignes($plancheTarif);
            }
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Un tarif existe déjà pour cette combinaison catégorie/épaisseur/contrat.',
                    'errors' => ['epaisseur' => ['Ce tarif existe déjà pour cette combinaison.']],
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Tarif mis à jour avec succès.',
            'data'    => $plancheTarif->fresh(['contrat']),
        ]);
    }

    /**
     * Backfille le prix_de_revient sur les lignes BL existantes qui correspondent
     * à ce tarif et qui n'ont pas encore de prix_de_revient (null).
     */
    private function backfillLignes(PlancheTarif $tarif): void
    {
        $query = PlancheBonLivraisonLigne::query()
            ->whereNull('prix_de_revient')
            ->whereHas('plancheDetail', function ($q) use ($tarif) {
                $q->where('categorie', $tarif->categorie)
                  ->where('epaisseur', $tarif->epaisseur);
            });

        if ($tarif->contrat_id) {
            $query->where('contrat_id', $tarif->contrat_id);
        }

        $query->update(['prix_de_revient' => $tarif->prix]);
    }

    /**
     * Écrase le prix_de_revient sur TOUTES les lignes BL correspondant à ce tarif,
     * qu'elles aient déjà un prix ou non.
     */
    private function overwriteAllLignes(PlancheTarif $tarif): void
    {
        $query = PlancheBonLivraisonLigne::query()
            ->whereHas('plancheDetail', function ($q) use ($tarif) {
                $q->where('categorie', $tarif->categorie)
                  ->where('epaisseur', $tarif->epaisseur);
            });

        if ($tarif->contrat_id) {
            $query->where('contrat_id', $tarif->contrat_id);
        }

        $query->update(['prix_de_revient' => $tarif->prix]);
    }

    public function destroy(PlancheTarif $plancheTarif): JsonResponse
    {
        $plancheTarif->delete();
        PlancheTarif::clearCache();

        return response()->json([
            'message' => 'Tarif supprimé avec succès.',
        ]);
    }

    public function benefices(PlancheTarif $plancheTarif): JsonResponse
    {
        $lignesQuery = PlancheBonLivraisonLigne::query()
            ->whereHas('plancheDetail', function ($q) use ($plancheTarif) {
                $q->where('categorie', $plancheTarif->categorie)
                  ->where('epaisseur', $plancheTarif->epaisseur);
            });

        if ($plancheTarif->contrat_id) {
            $lignesQuery->where('contrat_id', $plancheTarif->contrat_id);
        }

        $lignes = $lignesQuery
            ->with([
                'bonLivraison:id,numero_bl,date_livraison,client_id',
                'bonLivraison.client:id,name',
                'plancheDetail:id,categorie,epaisseur',
                'plancheDetail.couleur:id,code',
                'plancheDetail.planche:id,contrat_id',
                'plancheDetail.planche.contrat:id,numero',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (PlancheBonLivraisonLigne $ligne) {
                $prixRevient      = $ligne->prix_de_revient !== null ? (float) $ligne->prix_de_revient : null;
                $beneficeUnitaire = ($prixRevient !== null && $ligne->prix_unitaire !== null)
                    ? round((float) $ligne->prix_unitaire - $prixRevient, 2)
                    : null;
                $beneficeTotal    = ($beneficeUnitaire !== null)
                    ? round($beneficeUnitaire * (int) $ligne->quantite_livree, 2)
                    : null;

                return [
                    'id'                => $ligne->id,
                    'numero_bl'         => $ligne->bonLivraison?->numero_bl,
                    'date_livraison'    => optional($ligne->bonLivraison?->date_livraison)->format('d/m/Y'),
                    'client'            => $ligne->bonLivraison?->client?->name,
                    'numero_contrat'    => $ligne->plancheDetail?->planche?->contrat?->numero,
                    'code_couleur'      => $ligne->plancheDetail?->couleur?->code,
                    'quantite_livree'   => (int) $ligne->quantite_livree,
                    'prix_unitaire'     => (float) $ligne->prix_unitaire,
                    'prix_total'        => (float) $ligne->prix_total,
                    'prix_de_revient'   => $prixRevient,
                    'benefice_unitaire' => $beneficeUnitaire,
                    'benefice_total'    => $beneficeTotal,
                ];
            });

        return response()->json([
            'tarif'  => $plancheTarif,
            'lignes' => $lignes,
            'totaux' => [
                'quantite_totale'  => $lignes->sum('quantite_livree'),
                'ca_total'         => round($lignes->sum('prix_total'), 2),
                'cout_total'       => round($lignes->sum(fn ($l) => ($l['prix_de_revient'] ?? 0) * $l['quantite_livree']), 2),
                'benefice_total'   => $lignes->every(fn ($l) => $l['benefice_total'] === null)
                    ? null
                    : round($lignes->sum(fn ($l) => $l['benefice_total'] ?? 0), 2),
            ],
        ]);
    }
}
