<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePlancheLineRequest;
use App\Http\Requests\StorePlancheCouleurRequest;
use App\Http\Requests\StorePlancheRequest;
use App\Models\Contrat;
use App\Models\Epaisseur;
use App\Models\Planche;
use App\Models\PlancheCouleur;
use App\Models\PlancheDetail;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class PlancheController extends Controller
{
    public function index()
    {
        return Inertia::render('Planches/Index', [
            'suppliers' => Supplier::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
        ]);
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

        $couleur = PlancheCouleur::query()->firstOrCreate([
            'code' => $code,
        ]);

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
        $query = Planche::query()
            ->with([
                'contrat:id,supplier_id,numero',
                'contrat.supplier:id,name',
                'details:id,planche_id,planche_couleur_id,categorie,epaisseur,quantite_prevue',
                'details.couleur:id,code,image_path',
            ])
            ->withSum('details as total_quantite_prevue', 'quantite_prevue')
            ->latest();

        if ($request->filled('supplier_id')) {
            $query->whereHas('contrat', function ($contratQuery) use ($request) {
                $contratQuery->where('supplier_id', $request->integer('supplier_id'));
            });
        }

        if ($request->filled('numero_contrat')) {
            $numeroContrat = $request->string('numero_contrat')->toString();

            $query->whereHas('contrat', function ($contratQuery) use ($numeroContrat) {
                $contratQuery->where('numero', 'like', '%' . $numeroContrat . '%');
            });
        }

        if ($request->filled('code_couleur')) {
            $codeCouleur = $request->string('code_couleur')->toString();
            $query->whereHas('details.couleur', function ($couleurQuery) use ($codeCouleur) {
                $couleurQuery->where('code', 'like', '%' . trim($codeCouleur) . '%');
            });
        }

        $planches = $query->paginate(20);
        $planches->setCollection(
            $planches->getCollection()->map(fn (Planche $item) => $this->decoratePlanche($item))
        );

        return response()->json($planches);
    }

    public function store(StorePlancheRequest $request)
    {
        try {
            $planches = DB::transaction(function () use ($request) {
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
                $couleurs = PlancheCouleur::query()
                    ->whereIn('code', $groupes->pluck('code_couleur')->unique())
                    ->get()
                    ->keyBy('code');

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
                    $couleur  = $couleurs[$code] ?? PlancheCouleur::query()->create(['code' => $code]);

                    $couleurs[$code] = $couleur;

                    $planche = $contrat->planches()->create();

                    $planche->details()->createMany(
                        collect($groupe['epaisseurs'])->map(fn (array $ep) => [
                            'planche_couleur_id' => $couleur->id,
                            'categorie'          => $cat,
                            'epaisseur'          => $ep['epaisseur'],
                            'quantite_prevue'    => $ep['quantite_prevue'],
                        ])->values()->all()
                    );

                    $planches->push($planche);
                }

                return $planches;
            });

            return response()->json([
                'message' => 'Planches enregistrées avec succès.',
                'data'    => $planches,
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
                $couleur        = $this->findOrCreateCouleur($codeCouleur);

                $targetPlanche = $this->findPlancheForContratCouleurCategorie($planche->contrat_id, $couleur->id, $categorie)
                    ?? Planche::query()->create(['contrat_id' => $planche->contrat_id]);

                if ($targetPlanche->details()
                    ->where('planche_couleur_id', $couleur->id)
                    ->where('categorie', $categorie)
                    ->where('epaisseur', $epaisseur)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'epaisseur' => 'Cette épaisseur existe déjà pour ce code couleur et cette catégorie.',
                    ]);
                }

                $detail = $targetPlanche->details()->create([
                    'planche_couleur_id' => $couleur->id,
                    'categorie'          => $categorie,
                    'epaisseur'          => $epaisseur,
                    'quantite_prevue'    => $quantitePrevue,
                ]);

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
                $couleur         = $this->findOrCreateCouleur($codeCouleur);

                $targetPlanche = $this->findPlancheForContratCouleurCategorie($planche->contrat_id, $couleur->id, $categorie)
                    ?? Planche::query()->create(['contrat_id' => $planche->contrat_id]);

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

                $detail->update([
                    'planche_id'         => $targetPlanche->id,
                    'planche_couleur_id' => $couleur->id,
                    'categorie'          => $categorie,
                    'epaisseur'          => $epaisseur,
                    'quantite_prevue'    => $quantitePrevue,
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
            : route('planches.index');
    }

    private function findOrCreateCouleur(string $codeCouleur): PlancheCouleur
    {
        return PlancheCouleur::query()->firstOrCreate([
            'code' => $this->normalizeCodeCouleur($codeCouleur),
        ]);
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
        $planche->setAttribute('code_couleur', $couleur?->code);
        $planche->setAttribute('categorie', $categorie);

        return $planche;
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
