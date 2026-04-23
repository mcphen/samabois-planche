<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Epaisseur;
use App\Models\PlancheDetail;
use App\Models\PlancheCouleur;
use App\Models\Supplier;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Configuration/Index', [
            'userRole' => auth()->user()->role,
            'epaisseurs' => Epaisseur::query()
                ->orderBy('intitule')
                ->get(['id', 'intitule', 'slug'])
                ->map(function (Epaisseur $epaisseur) {
                    $usageCount = $this->countEpaisseurUsage($epaisseur);

                    return [
                        'id' => $epaisseur->id,
                        'intitule' => $epaisseur->intitule,
                        'slug' => $epaisseur->slug,
                        'usage_count' => $usageCount,
                        'can_delete' => $usageCount === 0,
                    ];
                }),
            'plancheCouleurs' => PlancheCouleur::query()
                ->withCount('details')
                ->orderBy('code')
                ->get(['id', 'code', 'image_path'])
                ->map(fn (PlancheCouleur $couleur) => [
                    'id' => $couleur->id,
                    'code' => $couleur->code,
                    'image_path' => $couleur->image_path,
                    'image_url' => $couleur->image_url,
                    'usage_count' => $couleur->details_count,
                    'can_delete' => $couleur->details_count === 0,
                ]),
            'suppliers' => Supplier::query()
                ->withCount('contrats')
                ->orderBy('name')
                ->get(['id', 'name', 'address', 'phone', 'email'])
                ->map(fn (Supplier $supplier) => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'address' => $supplier->address,
                    'phone' => $supplier->phone,
                    'email' => $supplier->email,
                    'contrats_count' => $supplier->contrats_count,
                    'can_delete' => $supplier->contrats_count === 0,
                ]),
            'clients' => Client::query()
                ->orderBy('name')
                ->get(['id', 'name', 'address', 'phone', 'email']),
            'userRole' => auth()->user()->role,
        ]);
    }

    private function countEpaisseurUsage(Epaisseur $epaisseur): int
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
