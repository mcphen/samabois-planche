<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContratController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ArticleItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlancheController;
use App\Http\Controllers\PlancheBonLivraisonController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EpaisseurController;
use App\Http\Controllers\PlancheCouleurController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SupplierConfigurationController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/nettoyer_articles', [ArticleController::class, 'nettoyerArticlesSupprimes']);
Route::get('/recuperationCompte', [ClientController::class, 'recuperationCompte']);
Route::get('/miseAjourCaisseTransaction', [PaymentController::class, 'miseAjourCaisseTransaction']);

Route::get('/updateIndisponible',[InvoiceController::class,'updateIndisponible']);
Route::get('/', function () {
    // Vérifie si l'utilisateur est connecté
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    // Sinon, redirige vers la page de connexion
    return redirect()->route('login');
});

// Dashboard protégé par l'authentification
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/dashboard/stock', function () {
        return Inertia::render('DashboardStock');
    })->name('dashboard.stock');

    Route::get('/dashboard/clients', function () {
        return Inertia::render('DashboardClients');
    })->name('dashboard.clients');

    Route::get('/dashboard/caisse', function () {
        return Inertia::render('DashboardCaisse');
    })->name('dashboard.caisse');

    Route::get('/admin/articles/fetch', [ArticleController::class, 'fetch'])->name('articles.test');

    Route::prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');

        Route::prefix('/dashboard')->group(function () {
            Route::post('/sync', [DashboardController::class, 'sync'])->name('admin.sync');
            Route::get('/stats-general',[DashboardController::class, 'getStats'])->name('admin.stats-general');
            Route::get('/get-chiffres-affaires',[DashboardController::class, 'getChiffreAffaireBeneficeParMois'])->name('admin.stats-getChiffreAffaireBeneficeParMois');
            Route::get('/get-chiffres-affaires/pdf',[DashboardController::class, 'exportChiffreAffaireBeneficePDF'])->name('admin.stats-general-pdf');
            Route::get('/evolution-ca', [DashboardController::class, 'getEvolutionMensuelleCA']);
            Route::get('/top-clients', [DashboardController::class, 'getTopClients']);
            Route::get('/stock-stats', [DashboardController::class, 'getStockStats']);
            Route::get('/clients-stats', [DashboardController::class, 'getClientsStats']);
            Route::get('/caisse-stats', [DashboardController::class, 'getCaisseStats']);
        });

        Route::prefix('/clients')->group(function () {
            Route::get('/', [ClientController::class, 'index'])->name('clients.index');
            Route::get('/comptes', [ClientController::class, 'comptes'])->name('clients.comptes');
            Route::get('/liste-comptes', [ClientController::class, 'getClientAccounts'])->name('clients.accounts');
            Route::get('/liste-comptes-soldes', [ClientController::class, 'getSettledClientAccounts'])->name('clients.settled-accounts');
            Route::get('/historique-comptabilite', [ClientController::class, 'accountingHistory'])->name('clients.accounting-history');
            Route::get('/generate-pdf-account', [ClientController::class, 'generatePDF']);
            Route::get('/{client}/consultation', [ClientController::class, 'show'])->name('clients.show');
            Route::get('/{client}/invoices', [ClientController::class, 'fetchInvoices']);
            Route::get('/{client}/invoices-paiements', [ClientController::class, 'fetchInvoicesPaiement']);
            Route::get('/{client}/transaction-paiements', [ClientController::class, 'fetchTransactionPaiement']);
            Route::get('/{client}/historique-solde', [ClientController::class, 'getHistoriqueClientSolde']);
            Route::get('/{client}/historique-comptabilite', [ClientController::class, 'getClientAccountingHistory']);
            Route::get('/{client}/generate-pdf-facture-paiement', [ClientController::class, 'geratePDF']);
            Route::post('/{client}/payments', [PaymentController::class, 'store']);
            Route::post('/payments/{transaction}/update-payments', [PaymentController::class, 'updatePayment']);
            Route::delete('/payments/{transaction}/delete-payments', [PaymentController::class, 'deletePayment']);
            Route::post('/{client}/old-payments', [PaymentController::class, 'storeOldPaiement']);
            Route::post('/{client}/cancel-solde', [PaymentController::class, 'cancelSolde']);

            // Routes API

            Route::get('/liste-clients', [ClientController::class, 'getClients'])->name('clients.api');
            Route::post('/store', [ClientController::class, 'store'])->name('clients.store');
            Route::post('/{client}/update', [ClientController::class, 'update'])->name('clients.update');
            Route::delete('/destroy/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');


            Route::get('/{client}/portefeuille', [ClientController::class, 'showPortefeuille'])->name('clients.portefeuille');
            Route::post('/{client}/paiement', [ClientController::class, 'enregistrerPaiement'])->name('clients.paiement');
            Route::post('/{client}/update-amount/{clientId}', [ClientController::class, 'updateAmountClient'])->name('clients.update-amount');
        });

        Route::prefix('/suppliers')->group(function () {
            Route::get('/', [SupplierController::class, 'index'])->name('supplier.index');

            // Routes API
            Route::get('/liste-suppliers', [SupplierController::class, 'getSuppliers'])->name('supplier.api');
            Route::post('/store', [SupplierController::class, 'store'])->name('supplier.store');
          //  Route::delete('/destroy/{supplier}', [SupplierController::class, 'destroy'])->name('supplier.destroy');

        });


        Route::prefix('/articles')->group(function () {

            Route::get('/', [ArticleController::class, 'index'])->name('articles.index');
            Route::get('/vue-globale', [ArticleController::class, 'vueGlobal'])->name('articles.vueGlobal');
            Route::get('/create', [ArticleController::class, 'create'])->name('articles.create');
            Route::get('/liste-pdf', [ArticleController::class, 'exportListPDF'])->name('articles.liste-pdf');
            Route::get('/{article}', [ArticleController::class, 'show'])->name('articles.show');
            Route::get('/{article}/details-price', [ArticleController::class, 'getDetailsParArticle']);
            Route::post('/{id}/update-price', [ArticleController::class, 'updatePricePerM3']);


            Route::post('/store', [ArticleController::class, 'store'])->name('articles.store');
            Route::post('/{article}/add-colis', [ArticleController::class, 'addColis']);
            Route::put('/{article}/update', [ArticleController::class, 'update'])->name('articles.update');
            Route::delete('/{article}/destroy', [ArticleController::class, 'destroy'])->name('articles.destroy');

        });

        Route::prefix('/configuration')->group(function () {
            Route::get('/', [ConfigurationController::class, 'index'])->name('configuration.index');
            Route::get('/epaisseurs', [EpaisseurController::class, 'index'])->name('configuration.epaisseurs.index');
            Route::post('/epaisseurs', [EpaisseurController::class, 'store'])->name('configuration.epaisseurs.store');
            Route::put('/epaisseurs/{epaisseur}', [EpaisseurController::class, 'update'])->name('configuration.epaisseurs.update');
            Route::delete('/epaisseurs/{epaisseur}', [EpaisseurController::class, 'destroy'])->name('configuration.epaisseurs.destroy');
            Route::get('/planche-couleurs', [PlancheCouleurController::class, 'index'])->name('configuration.planche-couleurs.index');
            Route::post('/planche-couleurs', [PlancheCouleurController::class, 'store'])->name('configuration.planche-couleurs.store');
            Route::post('/planche-couleurs/{plancheCouleur}', [PlancheCouleurController::class, 'update'])->name('configuration.planche-couleurs.update');
            Route::delete('/planche-couleurs/{plancheCouleur}', [PlancheCouleurController::class, 'destroy'])->name('configuration.planche-couleurs.destroy');
            Route::get('/suppliers', [SupplierConfigurationController::class, 'index'])->name('configuration.suppliers.index');
            Route::post('/suppliers', [SupplierConfigurationController::class, 'store'])->name('configuration.suppliers.store');
            Route::put('/suppliers/{supplier}', [SupplierConfigurationController::class, 'update'])->name('configuration.suppliers.update');
            Route::delete('/suppliers/{supplier}', [SupplierConfigurationController::class, 'destroy'])->name('configuration.suppliers.destroy');
        });

        Route::prefix('/contrats')->group(function () {
            Route::get('/', [ContratController::class, 'index'])->name('contrats.index');
            Route::get('/listes', [ContratController::class, 'getContrats'])->name('contrats.list');
            Route::get('/{contrat}', [ContratController::class, 'show'])->name('contrats.show');
            Route::put('/{contrat}', [ContratController::class, 'update'])->name('contrats.update');
        });

        Route::prefix('/planches')->group(function () {
            Route::get('/', [PlancheController::class, 'index'])->name('planches.index');
            Route::get('/create', [PlancheController::class, 'create'])->name('planches.create');
            Route::get('/listes', [PlancheController::class, 'getPlanches'])->name('planches.list');
            Route::get('/couleurs', [PlancheController::class, 'searchCouleurs'])->name('planches.colors.search');
            Route::post('/couleurs', [PlancheController::class, 'storeCouleur'])->name('planches.colors.store');
            Route::get('/{planche}', [PlancheController::class, 'show'])->name('planches.show');
            Route::post('/store', [PlancheController::class, 'store'])->name('planches.store');
            Route::post('/{planche}/lignes', [PlancheController::class, 'storeLine'])->name('planches.lines.store');
            Route::put('/{planche}/lignes/{detail}', [PlancheController::class, 'updateLine'])->name('planches.lines.update');
            Route::delete('/{planche}/lignes/{detail}', [PlancheController::class, 'destroyLine'])->name('planches.lines.destroy');
            Route::delete('/{planche}/destroy', [PlancheController::class, 'destroy'])->name('planches.destroy');
        });

        Route::prefix('/planche-bons-livraison')->group(function () {
            Route::get('/', [PlancheBonLivraisonController::class, 'index'])->name('planche-bons-livraison.index');
            Route::get('/create', [PlancheBonLivraisonController::class, 'create'])->name('planche-bons-livraison.create');
            Route::get('/listes', [PlancheBonLivraisonController::class, 'getBonsLivraison'])->name('planche-bons-livraison.list');
            Route::get('/details-disponibles', [PlancheBonLivraisonController::class, 'getAvailableDetails'])->name('planche-bons-livraison.available-details');
            Route::get('/{plancheBonLivraison}', [PlancheBonLivraisonController::class, 'show'])->name('planche-bons-livraison.show');
            Route::get('/{plancheBonLivraison}/generate-pdf', [PlancheBonLivraisonController::class, 'generatePDF'])->name('planche-bons-livraison.generatePDF');
            Route::post('/store', [PlancheBonLivraisonController::class, 'store'])->name('planche-bons-livraison.store');
            Route::put('/{plancheBonLivraison}', [PlancheBonLivraisonController::class, 'update'])->name('planche-bons-livraison.update');
            Route::delete('/{plancheBonLivraison}', [PlancheBonLivraisonController::class, 'destroy'])->name('planche-bons-livraison.destroy');
        });

        Route::prefix('article-items')->group(function () {
            Route::get('/listes', [ArticleItemController::class, 'listeItems'])->name('article-items.index');
            Route::get('/search', [ArticleItemController::class, 'search'])->name('articles.search');
            Route::get('/generate-pdf-article', [ArticleItemController::class, 'generatePDF'])->name('articles.generatePDF');
            Route::post('/{articleItem}/update', [ArticleItemController::class, 'update'])->name('articles-items.update');
            Route::delete('/{articleItem}/destroy', [ArticleItemController::class, 'destroy'])->name('articles-items.destroy');

        });

        Route::prefix('/invoices')->group(function () {

            Route::get('/', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/listes', [InvoiceController::class, 'getPaginatedInvoices']);
            Route::get('/create', [InvoiceController::class, 'create'])->name('invoices.create');
            Route::post('/store', [InvoiceController::class, 'store'])->name('invoices.store');
            Route::post('/{invoice}/update', [InvoiceController::class, 'update'])->name('invoices.add_article');
            Route::post('/{invoice}/add-article-items', [InvoiceController::class, 'addArticleItems'])->name('invoices.addArticleItems');
            Route::post('/{invoice}/update-item', [InvoiceController::class, 'updateArticleItems'])->name('invoices.updateArticleItems');
            Route::post('/{invoice}/remove-article-item', [InvoiceController::class, 'removeArticleItem'])->name('invoices.removeArticleItem');

            Route::get('/{invoice}/select-price', [InvoiceController::class, 'selectPrice'])->name('invoices.selectPrice');
            Route::post('/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
            Route::get('/{invoice}/consultation', [InvoiceController::class, 'show'])->name('invoices.show');

            Route::get('/{invoice}/generate-pdf', [InvoiceController::class, 'generatePDF'])->name('invoices.generatePDF');
            Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');


            Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
            Route::post('/{invoice}/remove-item', [InvoiceController::class, 'removeItem'])->name('invoices.removeItem');

            Route::post('/{invoice}/add-item', [InvoiceController::class, 'addArticleItem']);


        });



        Route::prefix('/finances')->group(function () {

            Route::prefix('/caisse')->group(function () {
                Route::get('/', [CaisseController::class, 'historique_caisse']);
                Route::post('/sortie', [CaisseController::class, 'storeSortie']);
                Route::post('/entree-diverse', [CaisseController::class, 'storeEntreeDivers']);
                Route::post('/transfer', [CaisseController::class, 'transfer']);
                // Transferts entre caisses — pages et APIs
                Route::get('/transfers', [CaisseController::class, 'transfersPage'])->name('caisse.transfers.page');
                Route::get('/transfers/list', [CaisseController::class, 'listTransfers'])->name('caisse.transfers.list');
                Route::get('/transfers/{transfer}', [CaisseController::class, 'showTransfer'])->name('caisse.transfers.show');
                Route::post('/sortie/{caisse}/update', [CaisseController::class, 'updateSortie']);
                Route::delete('/sortie/{caisse}', [CaisseController::class, 'destroySortie']);
                // Entrées diverses: édition & suppression
                Route::post('/entree/{caisse}/update', [CaisseController::class, 'updateEntree']);
                Route::delete('/entree/{caisse}', [CaisseController::class, 'destroyEntree']);
                // Corrections: paiements clients et transferts entrants (à partir de l'ID de la transaction de caisse)
                Route::post('/payment/{transaction}/correct', [CaisseController::class, 'correctClientPaymentByTxn']);
                Route::post('/transfer-entry/{transaction}/correct', [CaisseController::class, 'correctIncomingTransferByTxn']);
                // Annulation: transfert entrant (via contre-écriture)
                Route::delete('/payment/{transaction}', [CaisseController::class, 'deleteClientPaymentByTxn']);
                Route::delete('/transfer-entry/{transaction}', [CaisseController::class, 'cancelIncomingTransfer']);
                Route::get('/solde', [CaisseController::class, 'getSolde']);
                Route::get('/fetch-caisse', [CaisseController::class, 'fetchCaisseTransactions']);
                Route::get('/fetch-caisse-old', [CaisseController::class, 'fetchCaisseTransactionsOld']);
                Route::get('/historique/pdf', [CaisseController::class, 'exportPDF'])->name('caisse.exportPDF');

                // Clôtures de caisse — pages et APIs
                Route::get('/cloture', [CaisseController::class, 'closurePage'])->name('caisse.closure.page');
                Route::get('/clotures', [CaisseController::class, 'closuresHistoryPage'])->name('caisse.closures.history.page');
                Route::get('/clotures/list', [CaisseController::class, 'listClosures'])->name('caisse.closures.list');
                Route::post('/clotures/preview', [CaisseController::class, 'previewClosure'])->name('caisse.closures.preview');
                Route::post('/clotures/create', [CaisseController::class, 'createClosure'])->name('caisse.closures.create');
                Route::get('/clotures/{closure}', [CaisseController::class, 'showClosure'])->name('caisse.closures.show');
                Route::get('/clotures/{closure}/download', [CaisseController::class, 'downloadClosurePdf'])->name('caisse.closures.download');


            });

            // Vue Inertia pour la gestion des caisses (liste + CRUD via axios)
            Route::get('/caisses', [CaisseController::class, 'index'])->name('caisses.index');

            // CRUD JSON pour les caisses (utilisé par le front via axios)
            // IMPORTANT: déclarer d'abord les routes statiques/sous-chemins avant la route paramétrée
            Route::prefix('/caisses')->group(function () {
                Route::get('/listes', [CaisseController::class, 'getCaisses']);
                Route::post('/store', [CaisseController::class, 'storeCaisse']);
                Route::get('/detail/{caisse}', [CaisseController::class, 'showCaisse']);
                Route::post('/{caisse}/update', [CaisseController::class, 'updateCaisse']);
                Route::delete('/{caisse}/destroy', [CaisseController::class, 'destroyCaisse']);
            });

            // Route paramétrée pour afficher une caisse spécifique (contrainte numérique pour éviter les collisions)
            Route::get('/{caisse}/caisses', [CaisseController::class, 'show'])
                ->whereNumber('caisse')
                ->name('caisses.show');

            // Rapports & clôtures
            Route::prefix('/rapports')->group(function () {
                Route::get('/', [ReportsController::class, 'index'])->name('finances.rapports.index');
                Route::get('/data', [ReportsController::class, 'data'])->name('finances.rapports.data');
                Route::get('/export/pdf', [ReportsController::class, 'exportPdf'])->name('finances.rapports.export.pdf');
                Route::get('/export/excel', [ReportsController::class, 'exportExcel'])->name('finances.rapports.export.excel');
            });
        });

    });


});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

