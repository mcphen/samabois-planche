<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEpaisseurBatchRequest;
use App\Http\Requests\UpdateEpaisseurRequest;
use App\Models\Epaisseur;
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
        );
    }

    public function store(StoreEpaisseurBatchRequest $request): JsonResponse
    {
        $created = DB::transaction(function () use ($request) {
            return collect($request->validated('rows'))
                ->map(fn (array $row) => Epaisseur::query()->create($row)->only(['id', 'intitule', 'slug']))
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
            'data' => $epaisseur->fresh()->only(['id', 'intitule', 'slug']),
        ]);
    }

    public function destroy(Epaisseur $epaisseur): JsonResponse
    {
        $epaisseur->delete();

        return response()->json([
            'message' => 'Epaisseur supprimee avec succes.',
        ]);
    }
}
