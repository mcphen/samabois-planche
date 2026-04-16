<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePlancheLineRequest;
use App\Http\Requests\StorePlancheCouleurRequest;
use App\Http\Requests\StorePlancheRequest;
use App\Models\Contrat;
use App\Models\Epaisseur;
use App\Models\Planche;
use App\Models\PlancheBenefitHistory;
use App\Models\PlancheCouleur;
use App\Models\PlancheDetail;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class PlancheController extends Controller
{
    private ?bool $legacyPlancheCodeColumnExists = null;
    private ?bool $legacyPlancheCouleurCodeColumnExists = null;

    public function index()
    {
        return redirect()->route('contrats.index');
    }

    public function create()
    {
        return Inertia::render('Planches/Create', [
            'suppliers' => Supplier::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'epaisseurs' => Epaisseur::query()
                ->orderBy('intitule')
                ->get(['id', 'intitule', 'slug']),
        ]);
    }

    public function searchCouleurs(Request $request)
    {
        $query = trim($request->string('q')->toString());

        $couleurs = PlancheCouleur::query()
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where('code', 'like', '%' . $query . '%');
            })
            ->orderBy('code')
            ->limit(10)
            ->get(['id', 'code', 'image_path']);

        return response()->json($couleurs);
    }

    public function storeCouleur(StorePlancheCouleurRequest $request)
    {
        $code = $this->normalizeCodeCouleur($request->string('code')->toString());

        $couleur = $this->findOrCreateCouleur($code);

        $this->syncCouleurImage($couleur, $request->file('image'));

        return response()->json([
            'message' => 'Couleur enregistree avec succes.',
            'data'    => $couleur->fresh(),
        ], $couleur->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Planche $planche)
    {
        $planche->load([
            'contrat:id,supplier_id,numero',
            'contrat.supplier:id,name,address,phone,email',
            'details:id,planche_id,planche_couleur_id,categorie,epaisseur,quantite_prevue',
            'details.couleur:id,code,image_path',
        ]);
        $this->decoratePlanche($planche);

        $autresPlanches = Planche::query()
            ->where('contrat_id', $planche->contrat_id)
            ->whereKeyNot($planche->id)
            ->with([
                'details:id,planche_id,planche_couleur_id,categorie,epaisseur,quantite_prevue',
                'details.couleur:id,code,image_path',
            ])
            ->select('id', 'contrat_id')
            ->get()
            ->map(fn (Planche $item) => $this->decoratePlanche($item))
            ->sortBy(fn (Planche $item) => $item->code_couleur . '|' . $item->categorie)
            ->values();

        $contratPlanches = Planche::query()
            ->where('contrat_id', $planche->contrat_id)
            ->with([
                'details:id,planche_id,planche_couleur_id,categorie,epaisseur,quantite_prevue',
                'details.couleur:id,code,image_path',
            ])
            ->select('id', 'contrat_id')
            ->get()
            ->map(fn (Planche $item) => $this->decoratePlanche($item))
            ->sortBy(fn (Planche $item) => $item->code_couleur . '|' . $item->categorie)
            ->values();

        return Inertia::render('Planches/Show', [
            'planche'         => $planche,
            'autres_planches' => $autresPlanches,
            'contrat_planches' => $contratPlanches,
        ]);
    }

    public function getPlanches(Request $request)
    {
        $query = Contrat::query()
            ->with([
                'supplier:id,name',
                'planches' => function ($plancheQuery) {
                    $plancheQuery
                        ->select('id', 'contrat_id', 'created_at')
                        ->with([
                            'details:id,planche_id,planche_couleur_id,categorie,epaisseur,quantite_prevue',
                            'details.couleur:id,code,image_path',
                        ])
                        ->withSum('details as total_quantite_prevue', 'quantite_prevue')
                        ->latest();
                },
            ])
            ->latest();

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('numero_contrat')) {
            $numeroContrat = $request->string('numero_contrat')->toString();

            $query->where('numero', 'like', '%' . $numeroContrat . '%');
        }

        if ($request->filled('code_couleur')) {
            $codeCouleur = $request->string('code_couleur')->toString();

            $query->whereHas('planches.details.couleur', function ($couleurQuery) use ($codeCouleur) {
                $couleurQuery->where('code', 'like', '%' . trim($codeCouleur) . '%');
            });
        }

        $contrats = $query->paginate(20);
        $contrats->setCollection(
            $contrats->getCollection()->map(fn (Contrat $item) => $this->decorateContrat($item))
        );

        return response()->json($contrats);
    }

    public function store(StorePlancheRequest $request)
    {
        try {
            $result = DB::transaction(function () use ($request) {
                $contrat = Contrat::query()->firstOrCreate([
                    'supplier_id' => $request->integer('supplier_id'),
                    'numero'      => trim($request->string('numero_contrat')->toString()),
                ]);

                $groupes = collect($request->validated('groupes'))
                    ->map(fn (array $groupe) => [
                        'code_couleur' => $this->normalizeCodeCouleur($groupe['code_couleur']),
                        'categorie'    => $groupe['categorie'],
                        'epaisseurs'   => $groupe['epaisseurs'],
                    ]);

                // Resolve existing couleur records
                $codes = $groupes->pluck('code_couleur')
                    ->map(fn ($code) => $this->normalizeCodeCouleur((string) $code))
                    ->unique()
                    ->values();

                $couleurs = $this->findCouleursByCodes($codes);

                $missingErrors = [];

                foreach ($groupes as $index => $groupe) {
                    $code = $groupe['code_couleur'];

                    if (! $couleurs->has($code)) {
                        $missingErrors["groupes.{$index}.code_couleur"] = "Le code couleur {$code} n'existe pas.";
                    }
                }

                if (! empty($missingErrors)) {
                    throw ValidationException::withMessages($missingErrors);
                }

                // Check for duplicate (color + category) already in this contract
                $existants = [];
                foreach ($groupes as $groupe) {
                    $code     = $groupe['code_couleur'];
                    $cat      = $groupe['categorie'];
                    $couleur  = $couleurs->get($code);

                    if ($couleur && $this->findPlancheForContratCouleurCategorie($contrat->id, $couleur->id, $cat)) {
                        $existants[] = "$code / " . $this->categorieLabel($cat);
                    }
                }

                if (!empty($existants)) {
                    throw ValidationException::withMessages([
                        'groupes' => 'Les combinaisons suivantes existent déjà pour ce contrat : ' . implode(', ', $existants) . '.',
                    ]);
                }

                $planches = collect();

                foreach ($groupes as $groupe) {
                    $code     = $groupe['code_couleur'];
                    $cat      = $groupe['categorie'];
                    $couleur  = $couleurs[$code];

                    $couleurs[$code] = $couleur;

                    $planche = $this->createPlancheForContrat($contrat->id, $code);

                    $planche->details()->createMany(
                        collect($groupe['epaisseurs'])->map(fn (array $ep) => [
                            'planche_couleur_id' => $couleur->id,
                            'categorie'          => $cat,
                            'epaisseur'          => $ep['epaisseur'],
                            'quantite_prevue'    => $ep['quantite_prevue'],
                            'prix_de_revient'    => isset($ep['prix_de_revient']) && $ep['prix_de_revient'] !== '' ? (float) $ep['prix_de_revient'] : null,
                        ])->values()->all()
                    );

                    $planches->push($planche);
                }

                return [
                    'contrat' => $contrat,
                    'planches' => $planches,
                ];
            });

            return response()->json([
                'message' => 'Planches enregistrées avec succès.',
                'data'    => [
                    'planches' => $result['planches'],
                    'redirect_to' => route('contrats.show', [
                        'contrat' => $result['contrat']->id,
                        'planche' => $result['planches']->first()?->id,
                    ]),
                ],
            ], 201);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            return $this->databaseErrorResponse(
                'Impossible d\'enregistrer la planche.',
                $exception,
                'planche.store',
                [
                    'supplier_id' => $request->input('supplier_id'),
                    'numero_contrat' => $request->input('numero_contrat'),
                    'groupes_count' => count($request->input('groupes', [])),
                ]
            );
        } catch (Throwable $exception) {
            return $this->unexpectedErrorResponse(
                'Impossible d\'enregistrer la planche.',
                $exception,
                'planche.store',
                [
                    'supplier_id' => $request->input('supplier_id'),
                    'numero_contrat' => $request->input('numero_contrat'),
                    'groupes_count' => count($request->input('groupes', [])),
                ]
            );
        }
    }

    public function destroy(Planche $planche)
    {
        $planche->delete();

        return response()->json([
            'message' => 'Planche supprimée avec succès.',
        ]);
    }

    public function storeLine(SavePlancheLineRequest $request, Planche $planche)
    {
        try {
            $result = DB::transaction(function () use ($request, $planche) {
                $codeCouleur    = trim($request->string('code_couleur')->toString());
                $categorie      = $request->input('categorie');
                $epaisseur      = (float) $request->input('epaisseur');
                $quantitePrevue = (int) $request->input('quantite_prevue');
                $couleur        = $this->findExistingCouleurOrFail($codeCouleur);

                $targetPlanche = $this->findPlancheForContratCouleurCategorie($planche->contrat_id, $couleur->id, $categorie)
                    ?? $this->createPlancheForContrat($planche->contrat_id, $codeCouleur);

                if ($targetPlanche->details()
                    ->where('planche_couleur_id', $couleur->id)
                    ->where('categorie', $categorie)
                    ->where('epaisseur', $epaisseur)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'epaisseur' => 'Cette épaisseur existe déjà pour ce code couleur et cette catégorie.',
                    ]);
                }

                $prixDeRevientRaw = $request->input('prix_de_revient');
                $prixDeRevient    = ($prixDeRevientRaw !== null && $prixDeRevientRaw !== '') ? (float) $prixDeRevientRaw : null;

                $detail = $targetPlanche->details()->create([
                    'planche_couleur_id' => $couleur->id,
                    'categorie'          => $categorie,
                    'epaisseur'          => $epaisseur,
                    'quantite_prevue'    => $quantitePrevue,
                    'prix_de_revient'    => $prixDeRevient,
                ]);

                if ($prixDeRevient !== null) {
                    PlancheBenefitHistory::create([
                        'user_id' => auth()->id(),
                        'planche_detail_id' => $detail->id,
                        'action' => 'detail_prix_de_revient_set',
                        'new_data' => [
                            'prix_de_revient' => $prixDeRevient,
                        ],
                    ]);
                }

                return [
                    'detail_id'  => $detail->id,
                    'planche_id' => $targetPlanche->id,
                ];
            });

            return response()->json([
                'message' => 'Ligne ajoutée avec succès.',
                'data'    => $result,
            ], 201);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            return $this->databaseErrorResponse(
                'Impossible d\'ajouter cette ligne.',
                $exception,
                'planche.line.store',
                [
                    'planche_id' => $planche->id,
                    'contrat_id' => $planche->contrat_id,
                ]
            );
        } catch (Throwable $exception) {
            return $this->unexpectedErrorResponse(
                'Impossible d\'ajouter cette ligne.',
                $exception,
                'planche.line.store',
                [
                    'planche_id' => $planche->id,
                    'contrat_id' => $planche->contrat_id,
                ]
            );
        }
    }

    public function updateLine(SavePlancheLineRequest $request, Planche $planche, PlancheDetail $detail)
    {
        $this->ensureDetailBelongsToContract($planche, $detail);

        try {
            $result = DB::transaction(function () use ($request, $planche, $detail) {
                $anciennePlanche = $detail->planche;
                $codeCouleur     = trim($request->string('code_couleur')->toString());
                $categorie       = $request->input('categorie');
                $epaisseur       = (float) $request->input('epaisseur');
                $quantitePrevue  = (int) $request->input('quantite_prevue');
                $couleur         = $this->findExistingCouleurOrFail($codeCouleur);

                $targetPlanche = $this->findPlancheForContratCouleurCategorie($planche->contrat_id, $couleur->id, $categorie)
                    ?? $this->createPlancheForContrat($planche->contrat_id, $codeCouleur);

                $duplicate = $targetPlanche->details()
                    ->where('planche_couleur_id', $couleur->id)
                    ->where('categorie', $categorie)
                    ->where('epaisseur', $epaisseur)
                    ->whereKeyNot($detail->id)
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'epaisseur' => 'Cette épaisseur existe déjà pour ce code couleur et cette catégorie.',
                    ]);
                }

                $prixDeRevientRaw = $request->input('prix_de_revient');
                $prixDeRevient    = ($prixDeRevientRaw !== null && $prixDeRevientRaw !== '') ? (float) $prixDeRevientRaw : null;
                $oldPrixDeRevient = $detail->prix_de_revient;
                $oldQuantitePrevue = $detail->quantite_prevue;

                if ($oldPrixDeRevient !== $prixDeRevient) {
                    $quantiteLivree = $detail->bonLivraisonLignes()->sum('quantite_livree');
                    $totalPrixTotal = $detail->bonLivraisonLignes()->sum('prix_total');

                    PlancheBenefitHistory::create([
                        'user_id' => auth()->id(),
                        'planche_detail_id' => $detail->id,
                        'action' => 'detail_prix_de_revient_changed',
                        'old_data' => [
                            'prix_de_revient' => $oldPrixDeRevient,
                            'quantite_livree' => $quantiteLivree,
                            'total_prix_total' => $totalPrixTotal,
                            'profit' => $oldPrixDeRevient !== null ? $totalPrixTotal - ($quantiteLivree * $oldPrixDeRevient) : null,
                        ],
                        'new_data' => [
                            'prix_de_revient' => $prixDeRevient,
                            'quantite_livree' => $quantiteLivree,
                            'total_prix_total' => $totalPrixTotal,
                            'profit' => $prixDeRevient !== null ? $totalPrixTotal - ($quantiteLivree * $prixDeRevient) : null,
                        ],
                    ]);
                }

                if ($oldQuantitePrevue !== $quantitePrevue) {
                    PlancheBenefitHistory::create([
                        'user_id' => auth()->id(),
                        'planche_detail_id' => $detail->id,
                        'action' => 'detail_quantite_prevue_changed',
                        'old_data' => [
                            'quantite_prevue' => $oldQuantitePrevue,
                        ],
                        'new_data' => [
                            'quantite_prevue' => $quantitePrevue,
                        ],
                    ]);
                }

                $detail->update([
                    'planche_id'         => $targetPlanche->id,
                    'planche_couleur_id' => $couleur->id,
                    'categorie'          => $categorie,
                    'epaisseur'          => $epaisseur,
                    'quantite_prevue'    => $quantitePrevue,
                    'prix_de_revient'    => $prixDeRevient,
                ]);

                $redirectTo = null;

                if ($anciennePlanche->id !== $targetPlanche->id) {
                    $redirectTo = $this->deletePlancheIfEmpty($anciennePlanche, $planche->id, $planche->contrat_id);
                }

                return [
                    'redirect_to' => $redirectTo,
                    'planche_id'  => $targetPlanche->id,
                ];
            });

            return response()->json([
                'message' => 'Ligne modifiée avec succès.',
                'data'    => $result,
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            return $this->databaseErrorResponse(
                'Impossible de modifier cette ligne.',
                $exception,
                'planche.line.update',
                [
                    'planche_id' => $planche->id,
                    'detail_id' => $detail->id,
                    'contrat_id' => $planche->contrat_id,
                ]
            );
        } catch (Throwable $exception) {
            return $this->unexpectedErrorResponse(
                'Impossible de modifier cette ligne.',
                $exception,
                'planche.line.update',
                [
                    'planche_id' => $planche->id,
                    'detail_id' => $detail->id,
                    'contrat_id' => $planche->contrat_id,
                ]
            );
        }
    }

    public function destroyLine(Planche $planche, PlancheDetail $detail)
    {
        $this->ensureDetailBelongsToContract($planche, $detail);

        if ($detail->bonLivraisonLignes()->exists()) {
            return response()->json([
                'message' => 'Suppression impossible : ce detail est deja present dans un bon de livraison.',
            ], 422);
        }

        $result = DB::transaction(function () use ($planche, $detail) {
            $plancheDetail = $detail->planche;
            $detail->delete();

            return [
                'redirect_to' => $this->deletePlancheIfEmpty($plancheDetail, $planche->id, $planche->contrat_id),
            ];
        });

        return response()->json([
            'message' => 'Ligne supprimée avec succès.',
            'data'    => $result,
        ]);
    }

    private function ensureDetailBelongsToContract(Planche $planche, PlancheDetail $detail): void
    {
        $detail->loadMissing('planche');

        if ($detail->planche?->contrat_id !== $planche->contrat_id) {
            abort(404);
        }
    }

    private function deletePlancheIfEmpty(Planche $planche, int $currentPlancheId, int $contratId): ?string
    {
        if ($planche->details()->exists()) {
            return null;
        }

        $deletedPlancheId = $planche->id;
        $planche->delete();

        if ($deletedPlancheId !== $currentPlancheId) {
            return null;
        }

        $nextPlanche = Planche::query()
            ->where('contrat_id', $contratId)
            ->with([
                'details:id,planche_id,planche_couleur_id,categorie',
                'details.couleur:id,code,image_path',
            ])
            ->get()
            ->map(fn (Planche $item) => $this->decoratePlanche($item))
            ->sortBy(fn (Planche $item) => $item->code_couleur . '|' . $item->categorie)
            ->first();

        return $nextPlanche
            ? route('planches.show', $nextPlanche)
            : route('contrats.show', $contratId);
    }

    private function findOrCreateCouleur(string $codeCouleur): PlancheCouleur
    {
        $codeCouleur = $this->normalizeCodeCouleur($codeCouleur);

        $couleur = $this->findCouleursByCodes(collect([$codeCouleur]))->get($codeCouleur);

        return $couleur ?? $this->createCouleur($codeCouleur);
    }

    private function findExistingCouleurOrFail(string $codeCouleur): PlancheCouleur
    {
        $codeCouleur = $this->normalizeCodeCouleur($codeCouleur);
        $couleur = $this->findCouleursByCodes(collect([$codeCouleur]))->get($codeCouleur);

        if ($couleur) {
            return $couleur;
        }

        throw ValidationException::withMessages([
            'code_couleur' => "Le code couleur {$codeCouleur} n'existe pas.",
        ]);
    }

    private function createCouleur(string $codeCouleur): PlancheCouleur
    {
        $couleur = new PlancheCouleur();
        $couleur->forceFill($this->couleurAttributes($codeCouleur));
        $couleur->save();

        return $couleur;
    }

    private function findCouleursByCodes($codes)
    {
        $codes = collect($codes)
            ->map(fn ($code) => $this->normalizeCodeCouleur((string) $code))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return collect();
        }

        $query = PlancheCouleur::query();

        if ($this->hasLegacyPlancheCouleurCodeColumn()) {
            $query->where(function ($builder) use ($codes) {
                $builder->whereIn('code', $codes)
                    ->orWhereIn('code_couleur', $codes);
            });
        } else {
            $query->whereIn('code', $codes);
        }

        return $query->get()->mapWithKeys(function (PlancheCouleur $couleur) {
            return [$this->extractCouleurCode($couleur) => $couleur];
        });
    }

    private function couleurAttributes(string $codeCouleur): array
    {
        $attributes = [
            'code' => $this->normalizeCodeCouleur($codeCouleur),
        ];

        if ($this->hasLegacyPlancheCouleurCodeColumn()) {
            $attributes['code_couleur'] = $attributes['code'];
        }

        return $attributes;
    }

    private function createPlancheForContrat(int $contratId, string $codeCouleur): Planche
    {
        $planche = new Planche();
        $planche->forceFill($this->plancheAttributes($contratId, $codeCouleur));
        $planche->save();

        return $planche;
    }

    private function plancheAttributes(int $contratId, string $codeCouleur): array
    {
        $attributes = [
            'contrat_id' => $contratId,
        ];

        if ($this->hasLegacyPlancheCodeColumn()) {
            $attributes['code_couleur'] = $this->normalizeCodeCouleur($codeCouleur);
        }

        return $attributes;
    }

    private function extractCouleurCode(PlancheCouleur $couleur): string
    {
        return $this->normalizeCodeCouleur(
            (string) ($couleur->getAttribute('code') ?: $couleur->getAttribute('code_couleur') ?: '')
        );
    }

    private function hasLegacyPlancheCouleurCodeColumn(): bool
    {
        return $this->legacyPlancheCouleurCodeColumnExists
            ??= Schema::hasColumn('planche_couleurs', 'code_couleur');
    }

    private function hasLegacyPlancheCodeColumn(): bool
    {
        return $this->legacyPlancheCodeColumnExists
            ??= Schema::hasColumn('planches', 'code_couleur');
    }

    private function normalizeCodeCouleur(string $codeCouleur): string
    {
        return trim($codeCouleur);
    }

    private function syncCouleurImage(PlancheCouleur $couleur, ?UploadedFile $image = null): void
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

    private function findPlancheForContratCouleurCategorie(int $contratId, int $couleurId, string $categorie): ?Planche
    {
        return Planche::query()
            ->where('contrat_id', $contratId)
            ->whereHas('details', function ($detailQuery) use ($couleurId, $categorie) {
                $detailQuery->where('planche_couleur_id', $couleurId)
                            ->where('categorie', $categorie);
            })
            ->first();
    }

    private function decoratePlanche(Planche $planche): Planche
    {
        $firstDetail = $planche->details->first();
        $couleur     = $firstDetail?->couleur;
        $categorie   = $firstDetail?->categorie;

        $planche->setRelation('couleur', $couleur);
        $planche->setAttribute('code_couleur', $couleur ? $this->extractCouleurCode($couleur) : null);
        $planche->setAttribute('categorie', $categorie);

        return $planche;
    }

    private function decorateContrat(Contrat $contrat): Contrat
    {
        $planches = $contrat->planches
            ->map(fn (Planche $item) => $this->decoratePlanche($item))
            ->sortBy(fn (Planche $item) => ($item->code_couleur ?? '') . '|' . ($item->categorie ?? ''))
            ->values();

        $contrat->setRelation('planches', $planches);
        $contrat->setAttribute('total_planches', $planches->count());
        $contrat->setAttribute('total_details', $planches->sum(fn (Planche $item) => $item->details->count()));
        $contrat->setAttribute('total_quantite_prevue', $planches->sum(fn (Planche $item) => (int) ($item->total_quantite_prevue ?? 0)));

        return $contrat;
    }

    private function categorieLabel(string $categorie): string
    {
        return match ($categorie) {
            'mate'          => 'Mate',
            'semi_brillant' => 'Semi-brillant',
            'brillant'      => 'Brillant',
            default         => $categorie,
        };
    }

    private function databaseErrorResponse(
        string $message,
        QueryException $exception,
        string $operation,
        array $context = []
    ) {
        $this->reportPlancheException($operation, $exception, $context);

        return response()->json([
            'message' => $message,
            'detail' => $this->describeDatabaseError($exception),
            'error_code' => $exception->errorInfo[0] ?? (string) $exception->getCode(),
        ], 500);
    }

    private function unexpectedErrorResponse(
        string $message,
        Throwable $exception,
        string $operation,
        array $context = []
    ) {
        $this->reportPlancheException($operation, $exception, $context);

        return response()->json([
            'message' => $message,
            'detail' => app()->hasDebugModeEnabled()
                ? $exception->getMessage()
                : 'Une erreur inattendue est survenue. Consultez les logs du serveur.',
        ], 500);
    }

    private function reportPlancheException(string $operation, Throwable $exception, array $context = []): void
    {
        Log::error("Planche operation failed: {$operation}", array_merge($context, [
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'trace' => app()->hasDebugModeEnabled() ? $exception->getTraceAsString() : null,
        ], $exception instanceof QueryException ? [
            'sql' => $exception->getSql(),
            'bindings' => $exception->getBindings(),
            'error_info' => $exception->errorInfo,
        ] : []));

        report($exception);
    }

    private function describeDatabaseError(QueryException $exception): string
    {
        $sqlState = $exception->errorInfo[0] ?? (string) $exception->getCode();
        $driverMessage = $exception->errorInfo[2] ?? $exception->getMessage();
        $sql = $exception->getSql();

        if (str_contains($driverMessage, "Field 'code_couleur' doesn't have a default value")) {
            if (str_contains($sql, '`planches`')) {
                return 'La table planches utilise encore un ancien champ obligatoire code_couleur. Le correctif applicatif est pret, mais il faut redployer ce code sur le serveur.';
            }

            if (str_contains($sql, '`planche_couleurs`')) {
                return 'La table planche_couleurs utilise encore un ancien champ obligatoire code_couleur. Le correctif applicatif est pret, mais il faut redployer ce code sur le serveur.';
            }

            return 'Un ancien champ obligatoire code_couleur existe encore en base. Le correctif applicatif est pret, mais il faut redployer ce code sur le serveur.';
        }

        if ($sqlState === '42S22' || str_contains($driverMessage, 'Unknown column')) {
            return 'Le schema de la base ne semble pas a jour. Verifiez les migrations des tables planches, planche_details et planche_couleurs.';
        }

        if ($sqlState === '42S02' || str_contains($driverMessage, 'Base table or view not found')) {
            return 'Une table requise est absente en base. Verifiez que toutes les migrations des planches ont bien ete executees.';
        }

        if ($sqlState === '23000' || str_contains($driverMessage, 'Integrity constraint violation')) {
            return 'Une contrainte de la base a bloque l enregistrement. Verifiez les doublons, les relations et les index des tables planches.';
        }

        if (app()->hasDebugModeEnabled()) {
            return $driverMessage;
        }

        return 'Le detail technique a ete envoye aux logs du serveur.';
    }
}
