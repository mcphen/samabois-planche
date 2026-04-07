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
                ->orderBy('name')
                ->get(['id', 'name', 'address', 'phone', 'email'])
        );
    }

    public function store(StoreSupplierBatchRequest $request): JsonResponse
    {
        $created = DB::transaction(function () use ($request) {
            return collect($request->validated('rows'))
                ->map(function (array $row) {
                    return Supplier::query()->create([
                        'name' => $row['name'],
                        'slug_name' => $row['slug_name'],
                    ])->only(['id', 'name', 'address', 'phone', 'email']);
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
            'data' => $supplier->fresh()->only(['id', 'name', 'address', 'phone', 'email']),
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json([
            'message' => 'Fournisseur supprime avec succes.',
        ]);
    }
}
