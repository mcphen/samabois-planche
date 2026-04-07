<?php

namespace App\Http\Controllers;

use App\Models\Epaisseur;
use App\Models\PlancheCouleur;
use App\Models\Supplier;
use Inertia\Inertia;
use Inertia\Response;

class ConfigurationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Configuration/Index', [
            'epaisseurs' => Epaisseur::query()
                ->orderBy('intitule')
                ->get(['id', 'intitule', 'slug']),
            'plancheCouleurs' => PlancheCouleur::query()
                ->orderBy('code')
                ->get(['id', 'code', 'image_path']),
            'suppliers' => Supplier::query()
                ->orderBy('name')
                ->get(['id', 'name', 'address', 'phone', 'email']),
        ]);
    }
}
