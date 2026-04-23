<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContratController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlancheController;
use App\Http\Controllers\PlancheBonLivraisonController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EpaisseurController;
use App\Http\Controllers\PlancheCouleurController;
use App\Http\Controllers\PlancheTarifController;
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
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'comptable') {
            return redirect()->route('planches.index');
        }
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->middleware('role:admin')->name('admin.users.index');
        Route::post('/users', [UserController::class, 'store'])->middleware('role:admin')->name('admin.users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->middleware('role:admin')->name('admin.users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('role:admin')->name('admin.users.destroy');

        Route::middleware('role:admin')->group(function () {

            Route::prefix('/dashboard')->group(function () {
                Route::post('/sync', [DashboardController::class, 'sync'])->name('admin.sync');
                Route::get('/stats-general', [DashboardController::class, 'getStats'])->name('admin.stats-general');
                Route::get('/get-chiffres-affaires', [DashboardController::class, 'getChiffreAffaireBeneficeParMois'])->name('admin.stats-getChiffreAffaireBeneficeParMois');
                Route::get('/get-chiffres-affaires/pdf', [DashboardController::class, 'exportChiffreAffaireBeneficePDF'])->name('admin.stats-general-pdf');
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
                Route::post('/store', [ClientController::class, 'store'])->name('clients.store');
                Route::post('/{client}/update', [ClientController::class, 'update'])->name('clients.update');
                Route::delete('/destroy/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

                Route::get('/{client}/portefeuille', [ClientController::class, 'showPortefeuille'])->name('clients.portefeuille');
                Route::post('/{client}/paiement', [ClientController::class, 'enregistrerPaiement'])->name('clients.paiement');
                Route::post('/{client}/update-amount/{clientId}', [ClientController::class, 'updateAmountClient'])->name('clients.update-amount');
            });

        }); // fin middleware role:admin

        Route::middleware('role:admin,comptable')->group(function () {
            Route::get('/clients/liste-clients', [ClientController::class, 'getClients'])->name('clients.api');
            Route::post('/clients/store', [ClientController::class, 'store'])->name('clients.store.comptable');
            Route::post('/clients/{client}/update', [ClientController::class, 'update'])->name('clients.update.comptable');

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
        });

            Route::prefix('/configuration')->middleware('role:admin')->group(function () {
                Route::get('/planche-tarifs', [PlancheTarifController::class, 'index'])->name('configuration.planche-tarifs.index');
                Route::post('/planche-tarifs', [PlancheTarifController::class, 'store'])->name('configuration.planche-tarifs.store');
                Route::put('/planche-tarifs/{plancheTarif}', [PlancheTarifController::class, 'update'])->name('configuration.planche-tarifs.update');
                Route::delete('/planche-tarifs/{plancheTarif}', [PlancheTarifController::class, 'destroy'])->name('configuration.planche-tarifs.destroy');
                Route::get('/planche-tarifs/{plancheTarif}/benefices', [PlancheTarifController::class, 'benefices'])->name('configuration.planche-tarifs.benefices');
            });
        Route::prefix('/contrats')->group(function () {
            Route::get('/', [ContratController::class, 'index'])->name('contrats.index');
            Route::get('/validate', [ContratController::class, 'checkNumero'])->name('contrats.validate');
            Route::get('/listes', [ContratController::class, 'getContrats'])->name('contrats.list');
            Route::get('/{contrat}', [ContratController::class, 'show'])->name('contrats.show');
            Route::get('/{contrat}/benefit-history', [ContratController::class, 'getBenefitHistory'])->name('contrats.benefit-history');
            Route::middleware('role:admin')->group(function () {
                Route::put('/{contrat}', [ContratController::class, 'update'])->name('contrats.update');
                Route::post('/{contrat}/planche-tarifs/batch', [ContratController::class, 'storePlancheTarifsBatch'])->name('contrats.planche-tarifs.batch');
            });
        });

        Route::prefix('/planches')->group(function () {
            Route::get('/', [PlancheController::class, 'index'])->name('planches.index');
            Route::get('/create', [PlancheController::class, 'create'])->name('planches.create');
             Route::get('/details-global', [PlancheController::class, 'detailsGlobalIndex'])->name('planches.details-global.index');
            Route::get('/details-global/search', [PlancheController::class, 'getDetailsGlobal'])->name('planches.details-global.search');
           
            
        //    Route::get('/details-global', [PlancheController::class, 'detailsGlobal'])->name('planches.details-global');
         //   Route::get('/details-global/search', [PlancheController::class, 'getDetailsGlobal'])->name('planches.details-global.search');
            Route::get('/listes', [PlancheController::class, 'getPlanches'])->name('planches.list');
            Route::get('/couleurs', [PlancheController::class, 'searchCouleurs'])->name('planches.colors.search');
            Route::post('/couleurs', [PlancheController::class, 'storeCouleur'])->name('planches.colors.store');
            Route::get('/{planche}', [PlancheController::class, 'show'])->name('planches.show');
            Route::post('/store', [PlancheController::class, 'store'])->name('planches.store');
            Route::post('/{planche}/lignes', [PlancheController::class, 'storeLine'])->name('planches.lines.store');
            Route::middleware('role:admin')->group(function () {
                Route::put('/{planche}/lignes/{detail}', [PlancheController::class, 'updateLine'])->name('planches.lines.update');
                Route::delete('/{planche}/lignes/{detail}', [PlancheController::class, 'destroyLine'])->name('planches.lines.destroy');
                Route::delete('/{planche}/destroy', [PlancheController::class, 'destroy'])->name('planches.destroy');
            });
        });

        Route::prefix('/planche-bons-livraison')->group(function () {
            Route::get('/', [PlancheBonLivraisonController::class, 'index'])->name('planche-bons-livraison.index');
            Route::get('/create', [PlancheBonLivraisonController::class, 'create'])->name('planche-bons-livraison.create');
            Route::get('/listes', [PlancheBonLivraisonController::class, 'getBonsLivraison'])->name('planche-bons-livraison.list');
            Route::get('/details-disponibles', [PlancheBonLivraisonController::class, 'getAvailableDetails'])->name('planche-bons-livraison.available-details');
            Route::get('/{plancheBonLivraison}/edit', [PlancheBonLivraisonController::class, 'edit'])->name('planche-bons-livraison.edit');
            Route::get('/{plancheBonLivraison}', [PlancheBonLivraisonController::class, 'show'])->name('planche-bons-livraison.show');
            Route::get('/{plancheBonLivraison}/generate-pdf', [PlancheBonLivraisonController::class, 'generatePDF'])->name('planche-bons-livraison.generatePDF');
            Route::post('/store', [PlancheBonLivraisonController::class, 'store'])->name('planche-bons-livraison.store');
            Route::middleware('role:admin,comptable')->group(function () {
                Route::put('/{plancheBonLivraison}', [PlancheBonLivraisonController::class, 'update'])->name('planche-bons-livraison.update');
            });
            Route::middleware('role:admin')->group(function () {
                Route::delete('/{plancheBonLivraison}', [PlancheBonLivraisonController::class, 'destroy'])->name('planche-bons-livraison.destroy');
            });
        });

        Route::middleware('role:admin')->group(function () {

            Route::prefix('/finances')->group(function () {

                Route::prefix('/caisse')->group(function () {
                    Route::get('/', [CaisseController::class, 'historique_caisse']);
                    Route::post('/sortie', [CaisseController::class, 'storeSortie']);
                    Route::post('/entree-diverse', [CaisseController::class, 'storeEntreeDivers']);
                    Route::post('/transfer', [CaisseController::class, 'transfer']);
                    Route::get('/transfers', [CaisseController::class, 'transfersPage'])->name('caisse.transfers.page');
                    Route::get('/transfers/list', [CaisseController::class, 'listTransfers'])->name('caisse.transfers.list');
                    Route::get('/transfers/{transfer}', [CaisseController::class, 'showTransfer'])->name('caisse.transfers.show');
                    Route::post('/sortie/{caisse}/update', [CaisseController::class, 'updateSortie']);
                    Route::delete('/sortie/{caisse}', [CaisseController::class, 'destroySortie']);
                    Route::post('/entree/{caisse}/update', [CaisseController::class, 'updateEntree']);
                    Route::delete('/entree/{caisse}', [CaisseController::class, 'destroyEntree']);
                    Route::post('/payment/{transaction}/correct', [CaisseController::class, 'correctClientPaymentByTxn']);
                    Route::post('/transfer-entry/{transaction}/correct', [CaisseController::class, 'correctIncomingTransferByTxn']);
                    Route::delete('/payment/{transaction}', [CaisseController::class, 'deleteClientPaymentByTxn']);
                    Route::delete('/transfer-entry/{transaction}', [CaisseController::class, 'cancelIncomingTransfer']);
                    Route::get('/solde', [CaisseController::class, 'getSolde']);
                    Route::get('/fetch-caisse', [CaisseController::class, 'fetchCaisseTransactions']);
                    Route::get('/fetch-caisse-old', [CaisseController::class, 'fetchCaisseTransactionsOld']);
                    Route::get('/historique/pdf', [CaisseController::class, 'exportPDF'])->name('caisse.exportPDF');
                    Route::get('/cloture', [CaisseController::class, 'closurePage'])->name('caisse.closure.page');
                    Route::get('/clotures', [CaisseController::class, 'closuresHistoryPage'])->name('caisse.closures.history.page');
                    Route::get('/clotures/list', [CaisseController::class, 'listClosures'])->name('caisse.closures.list');
                    Route::post('/clotures/preview', [CaisseController::class, 'previewClosure'])->name('caisse.closures.preview');
                    Route::post('/clotures/create', [CaisseController::class, 'createClosure'])->name('caisse.closures.create');
                    Route::get('/clotures/{closure}', [CaisseController::class, 'showClosure'])->name('caisse.closures.show');
                    Route::get('/clotures/{closure}/download', [CaisseController::class, 'downloadClosurePdf'])->name('caisse.closures.download');
                });

                Route::get('/caisses', [CaisseController::class, 'index'])->name('caisses.index');

                Route::prefix('/caisses')->group(function () {
                    Route::get('/listes', [CaisseController::class, 'getCaisses']);
                    Route::post('/store', [CaisseController::class, 'storeCaisse']);
                    Route::get('/detail/{caisse}', [CaisseController::class, 'showCaisse']);
                    Route::post('/{caisse}/update', [CaisseController::class, 'updateCaisse']);
                    Route::delete('/{caisse}/destroy', [CaisseController::class, 'destroyCaisse']);
                });

                Route::get('/{caisse}/caisses', [CaisseController::class, 'show'])
                    ->whereNumber('caisse')
                    ->name('caisses.show');

                Route::prefix('/rapports')->group(function () {
                    Route::get('/', [ReportsController::class, 'index'])->name('finances.rapports.index');
                    Route::get('/data', [ReportsController::class, 'data'])->name('finances.rapports.data');
                    Route::get('/export/pdf', [ReportsController::class, 'exportPdf'])->name('finances.rapports.export.pdf');
                    Route::get('/export/excel', [ReportsController::class, 'exportExcel'])->name('finances.rapports.export.excel');
                });
            });

        }); // fin middleware role:admin (finances)

    });

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
