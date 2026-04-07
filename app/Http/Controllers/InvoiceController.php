<?php

namespace App\Http\Controllers;

use App\Models\ArticleItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf as PDF;


class InvoiceController extends Controller
{
    public function index()
    {
        return Inertia::render('Invoices/Index');
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['client', 'items.articleItem']);

        // Récupérer tous les clients disponibles
        $clients = Client::all();

        // Récupérer les essences possibles
        //$essences = ['Ayous', 'Frake', 'Dibetou', 'Bois Rouge'];

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'clients' => $clients,
            'articleItems' => $invoice->items->map(function ($item) {
                return [
                    'id' => $item->articleItem->id,
                    'essence' => $item->articleItem->article->essence,
                    'numero_colis' => $item->articleItem->numero_colis,
                    'longueur' => $item->articleItem->longueur,
                    'largeur' => $item->articleItem->largeur,
                    'epaisseur' => $item->articleItem->epaisseur,
                    'volume' => $item->articleItem->volume,
                    'price' => $item->price
                ];
            }),
            //'essences' => $essences
        ]);
    }


    public function getPaginatedInvoices(Request $request)
    {
        $numeroColis = $request->get('numero_colis');
        $essence     = $request->get('essence');

        return Invoice::with('client')
            ->withSum('items', 'price') // Calcul du total des prix
            ->where('status', '!=', 'canceled')
            // Filtre avancé: rechercher les factures contenant un ArticleItem par numéro de colis
            ->when($numeroColis, function ($query) use ($numeroColis) {
                $query->whereHas('items.articleItem', function ($q) use ($numeroColis) {
                    $q->where('numero_colis', 'like', "%{$numeroColis}%");
                });
            })
            // Filtre avancé: rechercher par essence de l'article
            ->when($essence, function ($query) use ($essence) {
                $query->whereHas('items.articleItem.article', function ($q) use ($essence) {
                    $q->where('essence', 'like', "%{$essence}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(500); // Permet de choisir le nombre d'éléments par page
    }

    public function searchInvoices(Request $request)
    {
        $criteria = $request->only([
            'numero_colis',
            'longueur',
            'largeur',
            // autres champs de ArticleItem
        ]);

        $invoices = Invoice::withArticleItemCriteria($criteria)
            ->with('items.articleItem') // Eager loading pour optimiser les performances
            ->orderBy('created_at', 'desc')
            ->paginate(500);

        return response()->json($invoices);
    }


    public function create()
    {
        return Inertia::render('Invoices/Create', [
            'clients' => Client::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'date' => 'required|date',
            'article_items' => 'required|array',
            'article_items.*' => 'exists:article_items,id',
        ]);

        $invoice = Invoice::create([
            'client_id' => $request->client_id,
            'date' => $request->date
        ]);

        // Stocker les articles sélectionnés dans la session
        session(['selected_article_items' => $request->article_items]);

        return redirect()->route('invoices.selectPrice', ['invoice' => $invoice->id]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'date' => 'required|date',
        ]);

        $invoice->update([
            'client_id' => $request->client_id,
            'date' => $request->date
        ]);

        Transaction::where('invoice_id',$invoice->id)->update([
            'client_id'        => $request->client_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Facture mise à jour avec succès.']);
    }


    public function addArticleItems(Request $request, Invoice $invoice)
    {
        $request->validate([
            'article_items' => 'required|array',
            'article_items.*' => 'exists:article_items,id',
        ]);

        // Récupérer les articles déjà stockés en session
        $sessionItems = session('selected_article_items', []);

        // Fusionner les nouveaux avec ceux existants
        $updatedItems = array_unique(array_merge($sessionItems, $request->article_items));

        // Mettre à jour la session
        session(['selected_article_items' => $updatedItems]);

        //dd($updatedItems);

        // Retourner les nouveaux articles ajoutés
        $newArticleItems = ArticleItem::whereIn('id', $request->article_items)->get();

        return response()->json(['success' => true, 'newArticleItems' => $newArticleItems]);
    }

    public function updateArticleItems(Request $request, Invoice $invoice)
    {
        $articleItem = ArticleItem::query()->find($request['id']);
        $invoiceItems = InvoiceItem::where([
            'invoice_id'=>$invoice->id,
            'article_item_id'=>$request['id']
        ])->first();
        $total_price = $request['volume'] * $request['price'];
        $articleItem->update([
            'numero_colis' => $request['numero_colis'],
            'longueur' => $request['longueur'],
            'largeur' => $request['largeur'],
            'epaisseur' => $request['epaisseur'],
            'volume' => $request['volume'],
        ]);
        $invoiceItems->update([
            'total_price_item' => $total_price,
            'price'=>$request['price'],
        ]);

        // Mettre à jour le prix total de la facture
        $invoice->updateTotalPrice();

        return response(['message'=>'Colis modifié avec success'],201);

    }

    public function addArticleItem(Request $request, Invoice $invoice)
    {
        $request->validate([
            'article_item_id' => 'required|exists:article_items,id',
            'price' => 'required|numeric|min:0',
        ]);

        // Vérifier si l'article existe déjà dans la facture
        $existingItem = $invoice->items()->where('article_item_id', $request->article_item_id)->first();
        if ($existingItem) {
            return response()->json(['message' => 'Cet article est déjà ajouté à la facture.'], 422);
        }

        ArticleItem::where('id', $request->article_item_id)->update([
            'indisponible' => 1,
            'numero_colis' => $request['numero_colis'],
            'longueur' => $request['longueur'],
            'largeur' => $request['largeur'],
            'epaisseur' => $request['epaisseur'],
            'volume' => $request['volume'],
        ]);

        // Ajouter l'article à la facture
        $invoice->items()->create([
            'article_item_id' => $request->article_item_id,
            'price' => $request->price, // Prix unitaire
            'total_price_item' => $request->price * ArticleItem::find($request->article_item_id)->volume, // Prix total
        ]);

        // Mettre à jour le total de la facture
        $invoice->updateTotalPrice();


        return response()->json([
            'message' => 'Article ajouté avec succès à la facture.',
            'total_price' => $invoice->total_price,
            'newArticleItem' => $invoice->items()->with('articleItem')->latest()->first()
        ]);
    }


    public function removeArticleItem(Request $request, Invoice $invoice)
    {
        $request->validate([
            'article_item_id' => 'required|exists:article_items,id',
        ]);

        // Récupérer les articles en session
        $sessionItems = session('selected_article_items', []);

        // Supprimer l'article de la session
        $updatedItems = array_filter($sessionItems, fn($id) => $id != $request->article_item_id);

        // Mettre à jour la session
        session(['selected_article_items' => array_values($updatedItems)]);

        return response()->json(['success' => true]);
    }


    public function selectPrice(Invoice $invoice, Request $request)
    {
        // Récupérer les articles stockés en session
        $articleItemsIds = session('selected_article_items', []);

        // Si la session est vide, rediriger vers la sélection
        if (empty($articleItemsIds)) {
            return redirect()->route('invoices.create')->with('error', 'Aucun article sélectionné.');
        }

        $articleItems = ArticleItem::whereIn('id', $articleItemsIds)->get();

        return Inertia::render('Invoices/SelectPrice', [
            'invoice' => $invoice->load('client'),
            'articleItems' => $articleItems
        ]);
    }

    public function finalize(Request $request, Invoice $invoice)
    {
        $request->validate([
            'prices' => 'required|array',
            'prices.*.article_item_id' => 'exists:article_items,id',
            'prices.*.price' => 'required|numeric|min:0'
        ]);

        foreach ($request->prices as $priceData) {
            $articleItem = ArticleItem::find($priceData['article_item_id']);
            $total_price = $articleItem->volume * $priceData['price'];
            $invoice->items()->firstOrCreate([
                'article_item_id' => $priceData['article_item_id'],
                'price' => $priceData['price'],
                'total_price_item' => $total_price
            ]);

            ArticleItem::where('id', $priceData['article_item_id'])->update([
                'indisponible' => 1
            ]);
        }

        // Mettre à jour le prix total de la facture
        $invoice->updateTotalPrice();


        // Supprimer les `article_items` de la session
        session()->forget('selected_article_items');

        return redirect()->route('invoices.show', $invoice->id);
    }



    public function show(Invoice $invoice)
    {
        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice->load(['client', 'items.articleItem.article'])
        ]);
    }

    public function generatePDF(Invoice $invoice)
    {
        $invoice->load(['client', 'items.articleItem.article']);
        $pdf = PDF::loadView('invoices.pdf-original', ['invoice' => $invoice]);

        return $pdf->stream("facture_{$invoice->id}.pdf");
    }

    public function cancel(Invoice $invoice)
    {
        // Vérifier si la facture est annulable
        if ($invoice->status === 'canceled') {
            return response()->json(['error' => 'Cette facture est déjà annulée.'], 400);
        }

        $montantSolde = $invoice->montant_solde;
        $client = $invoice->client;

        // Annuler la facture
        $invoice->update([
            'status' => 'canceled',
            'montant_solde' => 0
        ]);

        // Remettre les articles en disponible
        foreach ($invoice->items as $item) {
            $item->articleItem->update(['indisponible' => 0]);
            $item->delete();
        }

        // Vérifier si d'autres factures sont en attente
        $facturesNonSoldees = $client->invoices()->where('status', 'pending')->orderBy('date')->get();

        if ($facturesNonSoldees->isNotEmpty()) {
            foreach ($facturesNonSoldees as $facture) {
                $resteAFacturer = $facture->total_price - $facture->montant_solde;

                if ($montantSolde <= 0) {
                    break;
                }

                if ($montantSolde >= $resteAFacturer) {
                    $facture->update([
                        'montant_solde' => $facture->total_price,
                        'status' => 'validated'
                    ]);
                    $montantSolde -= $resteAFacturer;
                } else {
                    $facture->update([
                        'montant_solde' => $facture->montant_solde + $montantSolde
                    ]);
                    $montantSolde = 0;
                }
            }
        }

        // Si après redistribution, il reste de l'argent, on le met en crédit client
        if ($montantSolde > 0) {
            $client->update([
                'credit_disponible' => $client->credit_disponible + $montantSolde
            ]);
        }

        Transaction::where(['invoice_id'=>$invoice->id])->delete();

        $invoice->delete();
        return response()->json(['success' => true, 'message' => 'Facture annulée et montant redistribué.']);
    }


    public function removeItem(Request $request, Invoice $invoice)
    {
        $request->validate([
            'article_item_id' => 'required|exists:article_items,id',
        ]);

        // Trouver l'élément à supprimer
        $invoiceItem = $invoice->items()->where('article_item_id', $request->article_item_id)->first();

        if ($invoiceItem) {
            // return response()->json(['error' => 'Article non trouvé dans cette facture.'], 404);

            // Supprimer l'élément de la facture
            $invoiceItem->delete();

            // Remettre l'article en disponible
            $invoiceItem->articleItem->update(['indisponible' => 0]);

            // Mettre à jour le total de la facture
            $invoice->updateTotalPrice();
        }


        return response()->json(['success' => true, 'message' => 'Article supprimé avec succès.']);
    }



    public function updateIndisponible()
    {
        $invoiceItem = InvoiceItem::all();

        foreach ($invoiceItem as $item) {
            ArticleItem::where('id', $item->article_item_id)->update([
                'indisponible' => 1
            ]);
        }
    }

}




