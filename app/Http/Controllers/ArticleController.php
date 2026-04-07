<?php

namespace App\Http\Controllers;

use App\Imports\ArticleItemImport;
use App\Models\Article;
use App\Models\ArticleItem;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ArticleController extends Controller
{
    public function index()
    {

        return Inertia::render('Articles/Index');
    }

    public function vueGlobal()
    {
        return Inertia::render('Articles/GlobalComponent');

    }

    public function create()
    {
        return Inertia::render('Articles/Create');
    }

    public function show($id)
    {
        // 1) Récupération
        $article = Article::findOrFail($id);

       // dd($article->load('supplier', 'items'));
        return Inertia::render('Articles/Show', [
            'articleId'=> $article->id,
            'article' => $article->load('supplier', 'items')
        ]);
    }

    public function testQuery()
    {
        return 1;
    }

    public function fetch()
    {
        // Récupération des articles avec leurs fournisseurs et items
        // Trier numériquement sur contract_number (stocké en VARCHAR) en le CASTant en entier
        $articles = Article::with('supplier', 'items')
            ->orderByRaw('CAST(contract_number AS UNSIGNED) DESC')
            ->get();

        // Ajouter les informations financières (CA, volume vendu, coût de base, bénéfice)
        $articles->transform(function ($article) {
            // 1️⃣ Chiffre d’affaires (CA) = Somme des prix des invoice_items
            $chiffreAffaire = InvoiceItem::join('article_items', 'invoice_items.article_item_id', '=', 'article_items.id')
                ->where('article_items.article_id', $article->id)
                ->sum('invoice_items.total_price_item');

            // 2️⃣ Volume total vendu de cet article
            $totalVolumeVendu = InvoiceItem::join('article_items', 'invoice_items.article_item_id', '=', 'article_items.id')
                ->where('article_items.article_id', $article->id)
                ->sum('article_items.volume');

            // 3️⃣ Coût de base = Volume total vendu * Prix par m³
            $coutBase = $totalVolumeVendu * ($article->price_per_m3 ?? 0);

            // 4️⃣ Bénéfice = Chiffre d’affaires - Coût de base
            $benefice = $chiffreAffaire - $coutBase;

            // Ajouter ces données à chaque article
            $article->total_revenue = $chiffreAffaire;
            $article->total_volume = $totalVolumeVendu;
            $article->cost_base = $coutBase;
            $article->profit = $benefice;

            return $article;
        });

        return response()->json(['data' => $articles]);
    }

    public function exportListPDF()
    {
        // Récupérer les mêmes données que la liste affichée
        $articles = Article::with('supplier', 'items')
            ->orderByRaw('CAST(contract_number AS UNSIGNED) DESC')
            ->get();

        $articles->transform(function ($article) {
            $chiffreAffaire = InvoiceItem::join('article_items', 'invoice_items.article_item_id', '=', 'article_items.id')
                ->where('article_items.article_id', $article->id)
                ->sum('invoice_items.total_price_item');

            $totalVolumeVendu = InvoiceItem::join('article_items', 'invoice_items.article_item_id', '=', 'article_items.id')
                ->where('article_items.article_id', $article->id)
                ->sum('article_items.volume');

            $coutBase = $totalVolumeVendu * ($article->price_per_m3 ?? 0);
            $benefice = $chiffreAffaire - $coutBase;

            $article->total_revenue = $chiffreAffaire;
            $article->total_volume = $totalVolumeVendu;
            $article->cost_base = $coutBase;
            $article->profit = $benefice;

            return $article;
        });

        $pdf = Pdf::loadView('pdf.liste-stocks', ['articles' => $articles]);

        return $pdf->download('liste_stocks.pdf');
    }



    public function addColis(Article $article, Request $request){


        foreach ($request['data'] as $row) {
            $decodedRow = json_decode($row, true);
            if (!empty($decodedRow)) {
                // Nettoyage de chaque valeur
                foreach ($decodedRow as $key => $value) {
                    $value = trim($value);
                    // Remplacer la virgule par un point pour le format numérique
                    $value = str_replace(',', '.', $value);
                    // Supprimer les espaces insécables ou autres si présents
                    $value = preg_replace('/\s+/', '', $value);

                    if (is_numeric($value)) {
                        $decodedRow[$key] = $value;
                    } else {
                        $decodedRow[$key] = trim($value);
                    }
                }
                ArticleItem::updateOrCreate([
                    'article_id' => $article->id,
                    'numero_colis' => $decodedRow[0]
                ], [
                    'longueur' => $decodedRow[1] ?? null,
                    'epaisseur' => $decodedRow[2] ?? null,
                    'largeur' => $decodedRow[3] ?? null,
                    'nombre_piece' => $decodedRow[4] ?? null,
                    'volume' => $decodedRow[5] ?? null
                ]);
            }
        }

        return Inertia::render('Articles/Show', [
            'article' => $article->load('supplier', 'items')
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'essence' => 'required',
            'supplier_id' => 'required|exists:suppliers,id',
            'contract_number' => 'required',

        ]);

        //dd($request->all());



        $article = Article::withTrashed()->firstOrCreate(
            $request->only(['essence', 'supplier_id', 'contract_number'])
        );

        if ($article->trashed()) {
            // Restaurer l'article s'il est supprimé
            $article->restore();
        }
        foreach ($request['data'] as $row) {
            $decodedRow = json_decode($row, true);
            if (!empty($decodedRow)) {
                // Nettoyage de chaque valeur
                foreach ($decodedRow as $key => $value) {
                    $value = trim($value);
                    // Remplacer la virgule par un point pour le format numérique
                    $value = str_replace(',', '.', $value);
                    // Supprimer les espaces insécables ou autres si présents
                    $value = preg_replace('/\s+/', '', $value);

                    if (is_numeric($value)) {
                        $decodedRow[$key] = $value;
                    } else {
                        $decodedRow[$key] = trim($value);
                    }
                }
                ArticleItem::updateOrCreate([
                    'article_id' => $article->id,
                    'numero_colis' => $decodedRow[0]
                ], [
                    'longueur' => $decodedRow[1] ?? null,
                    'epaisseur' => $decodedRow[2] ?? null,
                    'largeur' => $decodedRow[3] ?? null,
                    'nombre_piece' => $decodedRow[4] ?? null,
                    'volume' => $decodedRow[5] ?? null
                ]);

            }
        }

        // Importation des ArticleItems à partir du fichier Excel
        //Excel::import(new ArticleItemImport($article->id), $request->file('file'));

        return Inertia::render('Articles/Show', [
            'article' => $article->load('supplier', 'items')
        ]);
    }

    public function update(Article $article, Request $request)
    {
        //dd($request->all());
        //'essence', 'supplier_id', 'contract_number'
        $article->essence = $request['essence'];
        $article->supplier_id = $request['supplier_id'];
        $article->contract_number = $request['contract_number'];

        $article->save();

    }
    public function destroy(Article $article)
    {

        ArticleItem::where('article_id',$article->id)->delete();
        $article->delete();

        return response()->json(['message' => 'Article supprimé avec succès.']);
    }

    public function nettoyerArticlesSupprimes()
    {
        // On récupère uniquement les articles qui sont softDeleted
        // (c'est-à-dire avec deleted_at non null)
        $articlesSupprimes = Article::onlyTrashed()->get();

        // On parcourt chacun de ces articles
        foreach ($articlesSupprimes as $article) {
            // On récupère les ArticleItem liés à cet Article
            // puis on les supprime (soft delete)
            $article->items()->delete();
        }

        return response()->json([
            'message' => 'Les ArticleItem des articles supprimés ont bien été soft-deleted.'
        ]);
    }

    public function updatePricePerM3(Request $request, $articleId)
    {
        $request->validate([
            'price_per_m3' => 'required|numeric|min:0',
        ]);

        $article = Article::findOrFail($articleId);
        $article->update(['price_per_m3' => $request->price_per_m3]);

        return response()->json(['message' => 'Prix par mètre cube mis à jour avec succès.']);
    }

    public function getDetailsParArticle($articleId)
    {
        $article = Article::findOrFail($articleId);

        // 1️⃣ Chiffre d’affaires (CA) = Somme des prix des invoice_items

        $chiffreAffaire = InvoiceItem::join('article_items', 'invoice_items.article_item_id', '=', 'article_items.id')
            ->where('article_items.article_id', $articleId)
            ->select(DB::raw('SUM(invoice_items.total_price_item) as total_revenue'))
            ->first();

        // 2️⃣ Volume total des `article_items` liés à cet article
        $totalVolumeVendu = InvoiceItem::join('article_items', 'invoice_items.article_item_id', '=', 'article_items.id')
            ->where('article_items.article_id', $articleId)
            ->select(DB::raw('SUM(article_items.volume) as volume_total'))
            ->first();

        // 3️⃣ Coût de base = Volume total * Prix par m³
        $coutBase = $totalVolumeVendu->volume_total * ($article->price_per_m3 ?? 0);

        // 4️⃣ Bénéfice = Chiffre d’affaires - Coût de base
        $benefice = ($chiffreAffaire->total_revenue ?? 0) - $coutBase;

        return response()->json([
            'article_id' => $articleId,
            'article_name' => $article->name,
            'price_per_m3' => $article->price_per_m3,
            'total_volume' => $totalVolumeVendu->volume_total,
            'total_revenue' => $chiffreAffaire->total_revenue ?? 0,
            'cost_base' => $coutBase,
            'profit' => $benefice,
        ]);
    }


}



