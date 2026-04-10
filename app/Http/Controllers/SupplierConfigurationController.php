<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierBatchRequest;
use App\Http\Requests\UpdateConfigurationSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SupplierConfigurationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Supplier::query()
                ->withCount('contrats')
                ->orderBy('name')
                ->get(['id', 'name', 'address', 'phone', 'email'])
                ->map(fn (Supplier $supplier) => $this->formatSupplier($supplier))
        );
    }

    public function store(StoreSupplierBatchRequest $request): JsonResponse
    {
        $created = DB::transaction(function () use ($request) {
            return collect($request->validated('rows'))
                ->map(function (array $row) {
                    $supplier = Supplier::query()->create([
                        'name' => $row['name'],
                        'slug_name' => $row['slug_name'],
                    ]);

                    return $this->formatSupplier($supplier);
                })
                ->values();
        });

        return response()->json([
            'message' => 'Les fournisseurs ont ete enregistres avec succes.',
            'data' => $created,
        ], 201);
    }

    public function update(UpdateConfigurationSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier->update([
            'name' => $request->validated('name'),
            'slug_name' => $request->validated('slug_name'),
            'address' => $request->validated('address'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
        ]);

        return response()->json([
            'message' => 'Fournisseur mis a jour avec succes.',
            'data' => $this->formatSupplier($supplier->fresh()->loadCount('contrats')),
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        if ($supplier->contrats()->exists()) {
            return response()->json([
                'message' => 'Suppression impossible: ce fournisseur est deja utilise dans un contrat.',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'message' => 'Fournisseur supprime avec succes.',
        ]);
    }

    private function formatSupplier(Supplier $supplier): array
    {
        $contratsCount = (int) ($supplier->contrats_count ?? 0);

        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'address' => $supplier->address,
            'phone' => $supplier->phone,
            'email' => $supplier->email,
            'contrats_count' => $contratsCount,
            'can_delete' => $contratsCount === 0,
        ];
    }
}
