<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEpaisseurBatchRequest;
use App\Http\Requests\UpdateEpaisseurRequest;
use App\Models\Epaisseur;
use App\Models\PlancheDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EpaisseurController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Epaisseur::query()
                ->orderBy('intitule')
                ->get(['id', 'intitule', 'slug'])
                ->map(fn (Epaisseur $epaisseur) => $this->formatEpaisseur($epaisseur))
        );
    }

    public function store(StoreEpaisseurBatchRequest $request): JsonResponse
    {
        $created = DB::transaction(function () use ($request) {
            return collect($request->validated('rows'))
                ->map(function (array $row) {
                    $epaisseur = Epaisseur::query()->create($row);

                    return $this->formatEpaisseur($epaisseur);
                })
                ->values();
        });

        return response()->json([
            'message' => 'Les epaisseurs ont ete enregistrees avec succes.',
            'data' => $created,
        ], 201);
    }

    public function update(UpdateEpaisseurRequest $request, Epaisseur $epaisseur): JsonResponse
    {
        $epaisseur->update($request->validated());

        return response()->json([
            'message' => 'Epaisseur mise a jour avec succes.',
            'data' => $this->formatEpaisseur($epaisseur->fresh()),
        ]);
    }

    public function destroy(Epaisseur $epaisseur): JsonResponse
    {
        if ($this->countUsage($epaisseur) > 0) {
            return response()->json([
                'message' => 'Suppression impossible: cette epaisseur est deja utilisee dans des lignes de planche.',
            ], 422);
        }

        $epaisseur->delete();

        return response()->json([
            'message' => 'Epaisseur supprimee avec succes.',
        ]);
    }

    private function formatEpaisseur(Epaisseur $epaisseur): array
    {
        $usageCount = $this->countUsage($epaisseur);

        return [
            'id' => $epaisseur->id,
            'intitule' => $epaisseur->intitule,
            'slug' => $epaisseur->slug,
            'usage_count' => $usageCount,
            'can_delete' => $usageCount === 0,
        ];
    }

    private function countUsage(Epaisseur $epaisseur): int
    {
        $value = $this->extractEpaisseurValue($epaisseur);

        if ($value === null) {
            return 0;
        }

        return PlancheDetail::query()
            ->where('epaisseur', $value)
            ->count();
    }

    private function extractEpaisseurValue(Epaisseur $epaisseur): ?float
    {
        foreach ([$epaisseur->slug, $epaisseur->intitule] as $source) {
            if (! preg_match('/(\d+(?:[.,]\d+)?)/', (string) $source, $matches)) {
                continue;
            }

            return (float) str_replace(',', '.', $matches[1]);
        }

        return null;
    }
}
