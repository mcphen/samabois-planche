<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConfigurationPlancheCouleurBatchRequest;
use App\Http\Requests\UpdatePlancheCouleurRequest;
use App\Models\PlancheCouleur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlancheCouleurController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            PlancheCouleur::query()
                ->orderBy('code')
                ->get(['id', 'code', 'image_path'])
        );
    }

    public function store(StoreConfigurationPlancheCouleurBatchRequest $request): JsonResponse
    {
        $created = DB::transaction(function () use ($request) {
            return collect($request->validated('rows'))
                ->map(fn (array $row) => PlancheCouleur::query()->create([
                    'code' => $row['code'],
                ]))
                ->values();
        });

        return response()->json([
            'message' => 'Les couleurs ont ete enregistrees avec succes.',
            'data' => $created,
        ], 201);
    }

    public function update(UpdatePlancheCouleurRequest $request, PlancheCouleur $plancheCouleur): JsonResponse
    {
        $plancheCouleur->update([
            'code' => trim($request->string('code')->toString()),
        ]);

        $this->syncImage($plancheCouleur, $request->file('image'));

        return response()->json([
            'message' => 'Couleur mise a jour avec succes.',
            'data' => $plancheCouleur->fresh(),
        ]);
    }

    public function destroy(PlancheCouleur $plancheCouleur): JsonResponse
    {
        if ($plancheCouleur->image_path) {
            Storage::disk('public')->delete($plancheCouleur->image_path);
        }

        $plancheCouleur->delete();

        return response()->json([
            'message' => 'Couleur supprimee avec succes.',
        ]);
    }

    private function syncImage(PlancheCouleur $couleur, ?UploadedFile $image = null): void
    {
        if (!$image) {
            return;
        }

        if ($couleur->image_path) {
            Storage::disk('public')->delete($couleur->image_path);
        }

        $couleur->forceFill([
            'image_path' => $image->store('planches/couleurs', 'public'),
        ])->save();
    }
}
