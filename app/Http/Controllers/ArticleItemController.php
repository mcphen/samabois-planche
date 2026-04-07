<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ArticleItemController extends Controller
{
    public function listeItems(Request $request)
    {

        // dd("fetch");
        $articles = ArticleItem::where('article_id',$request['articleId'])->paginate(500);
        return response()->json($articles);
    }

    public function update($articleItemId, Request $request)
    {
        $articleItem = ArticleItem::findOrFail($articleItemId);

        // Interdire la modification d'un article indisponible
        if ((int) $articleItem->indisponible === 1) {
            return response()->json([
                'message' => "Cet article est indisponible et ne peut pas être modifié."
            ], 403);
        }

        $articleItem->update($request->all());

        return response()->json($articleItem);
    }

    public function destroy($articleItemId){
        $articleItem = ArticleItem::findOrFail($articleItemId);
        // Interdire la suppression d'un article indisponible
        if ((int) $articleItem->indisponible === 1) {
            return response()->json([
                'message' => "Cet article est indisponible et ne peut pas être supprimé."
            ], 403);
        }
        $articleItem->delete();
        return response()->json($articleItemId);
    }

    public function search(Request $request)
    {
        $query = ArticleItem::query();

        if ($request->filled('contract_number')) {
            $query->whereHas('article', function ($q) use ($request) {
                $q->where('contract_number', 'like', '%' . $request->contract_number . '%');
            });
        }

        if ($request->filled('numero_colis')) {
            $query->where('numero_colis', 'like', '%' . $request->numero_colis . '%');
        }

        if ($request->filled('essence')) {
            $query->whereHas('article', function ($q) use ($request) {
                $q->where('essence', $request->essence);
            });
        }

        if ($request->filled('article_id')) {
            $query->whereHas('article', function ($q) use ($request) {
                $q->where('id', $request->article_id);
            });
        }

        if ($request->filled('supplier_id')) {
            $query->whereHas('article', function ($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }

        // Gestion flexible des longueurs
        if ($request->filled('longueur_min') && $request->filled('longueur_max')) {
            $query->whereBetween('longueur', [$request->longueur_min, $request->longueur_max]);
        } elseif ($request->filled('longueur_min')) {
            $query->where('longueur', '>=', $request->longueur_min);
        } elseif ($request->filled('longueur_max')) {
            $query->where('longueur', '<=', $request->longueur_max);
        }

        // Gestion flexible des épaisseurs
        if ($request->filled('epaisseur_min') && $request->filled('epaisseur_max')) {
            $query->whereBetween('epaisseur', [$request->epaisseur_min, $request->epaisseur_max]);
        } elseif ($request->filled('epaisseur_min')) {
            $query->where('epaisseur', '=', $request->epaisseur_min);
        } elseif ($request->filled('epaisseur_max')) {
            $query->where('epaisseur', '=', $request->epaisseur_max);
        }

        // Gestion flexible des volumes
        if ($request->filled('volume_min') && $request->filled('volume_max')) {
            $query->whereBetween('volume', [$request->volume_min, $request->volume_max]);
        } elseif ($request->filled('volume_min')) {
            $query->where('volume', '>=', $request->volume_min);
        } elseif ($request->filled('volume_max')) {
            $query->where('volume', '<=', $request->volume_max);
        }


        // Filtrage disponibilité: par défaut on affiche les disponibles (indisponible = 0)
        // valeurs possibles: 0 (disponible), 1 (indisponible), 'all' (tout voir)
        if ($request->filled('indisponible')) {
            $indisponible = $request->indisponible;
            if ($indisponible !== 'all' && $indisponible !== null && $indisponible !== '') {
                $query->where('indisponible', (int) $indisponible);
            }
        } else {
            $query->where('indisponible', 0);
        }

        $query = $query->with('article')
            ->orderBy('longueur','asc')
            ->orderBy('epaisseur','asc')
            ->get();

        return response()->json(['data' => $query]);
    }

    public function generatePDF(Request $request)
    {
        $article=null;
        $query = ArticleItem::query();

        if ($request->filled('contract_number')) {
            $query->whereHas('article', function ($q) use ($request) {
                $q->where('contract_number', 'like', '%' . $request->contract_number . '%');
            });
        }

        if ($request->filled('numero_colis')) {
            $query->where('numero_colis', 'like', '%' . $request->numero_colis . '%');
        }

        if ($request->filled('essence')) {
            $query->whereHas('article', function ($q) use ($request) {
                $q->where('essence', $request->essence);
            });

        }

        if ($request->filled('article_id')) {
            $query->whereHas('article', function ($q) use ($request) {
                $q->where('id', $request->article_id);
            });
            $article = Article::find($request['article_id'])->load('supplier');
        }

        // Gestion flexible des longueurs
        if ($request->filled('longueur_min') && $request->filled('longueur_max')) {
            $query->whereBetween('longueur', [$request->longueur_min, $request->longueur_max]);
        } elseif ($request->filled('longueur_min')) {
            $query->where('longueur', '>=', $request->longueur_min);
        } elseif ($request->filled('longueur_max')) {
            $query->where('longueur', '<=', $request->longueur_max);
        }

        // Gestion flexible des épaisseurs
        if ($request->filled('epaisseur_min') && $request->filled('epaisseur_max')) {
            $query->whereBetween('epaisseur', [$request->epaisseur_min, $request->epaisseur_max]);
        } elseif ($request->filled('epaisseur_min')) {
            $query->where('epaisseur', '=', $request->epaisseur_min);
        } elseif ($request->filled('epaisseur_max')) {
            $query->where('epaisseur', '=', $request->epaisseur_max);
        }

        // Gestion flexible des volumes
        if ($request->filled('volume_min') && $request->filled('volume_max')) {
            $query->whereBetween('volume', [$request->volume_min, $request->volume_max]);
        } elseif ($request->filled('volume_min')) {
            $query->where('volume', '>=', $request->volume_min);
        } elseif ($request->filled('volume_max')) {
            $query->where('volume', '<=', $request->volume_max);
        }


        // Filtrage disponibilité pour PDF: même logique que la recherche JSON
        if ($request->filled('indisponible')) {
            $indisponible = $request->indisponible;
            if ($indisponible !== 'all' && $indisponible !== null && $indisponible !== '') {
                $query->where('indisponible', (int) $indisponible);
            }
        } else {
            $query->where('indisponible', 0);
        }

        $queries = $query
            ->orderBy('longueur', 'asc')
            ->orderBy('epaisseur', 'asc')
            ->get();

        // Générer le PDF
        $pdf = Pdf::loadView('pdf.liste-article', compact('queries','article'));

        return $pdf->stream('liste_article.pdf');
    }
}
