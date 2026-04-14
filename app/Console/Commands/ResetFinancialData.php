<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetFinancialData extends Command
{
    protected $signature   = 'finance:reset';
    protected $description = 'Supprime tous les paiements, entrées et sorties de caisse, et remet les soldes à zéro.';

    public function handle(): int
    {
        // Identifier la caisse principale (la plus ancienne — id le plus petit)
        $caissePrincipale = DB::table('caisses')->orderBy('id')->first();
        $caissePrincipaleId = $caissePrincipale?->id;
        $caissePrincipaleName = $caissePrincipale?->name ?? '—';

        $nbCaissesASupprimer = DB::table('caisses')
            ->when($caissePrincipaleId, fn ($q) => $q->where('id', '!=', $caissePrincipaleId))
            ->count();

        $this->warn('⚠️  ATTENTION — opération irréversible !');
        $this->line('');
        $this->line('Les données suivantes seront <fg=red>définitivement supprimées</> :');
        $this->line('  • Corrections financières      : ' . DB::table('finance_corrections')->count());
        $this->line('  • Transferts entre caisses      : ' . DB::table('caisse_transfers')->count());
        $this->line('  • Transactions de caisse        : ' . DB::table('caisse_transactions')->count());
        $this->line('  • Clôtures de caisse            : ' . DB::table('caisse_closures')->count());
        $this->line('  • Caisses supplémentaires       : ' . $nbCaissesASupprimer . ' (conservée : "' . $caissePrincipaleName . '")');
        $this->line('  • Paiements clients (pivot)     : ' . DB::table('invoice_payment')->count());
        $this->line('  • Paiements clients             : ' . DB::table('payments')->count());
        $this->line('  • Transactions compta (payment) : ' . DB::table('transactions')->where('type', 'payment')->count());
        $this->line('  • Historiques soldes clients    : ' . DB::table('historique_client_soldes')->count());
        $this->line('  • Historiques comptabilité      : ' . DB::table('accounting_histories')->count());
        $this->line('');
        $this->line('Les données suivantes seront <fg=yellow>réinitialisées</> :');
        $this->line('  • Soldes clients (amount_payment, amount_solde → 0)');
        $this->line('  • Factures non annulées         → montant_solde = 0, status = pending');
        $this->line('');

        if (! $this->confirm('Confirmer la suppression ?', false)) {
            $this->info('Opération annulée.');
            return self::SUCCESS;
        }

        $this->line('');
        $this->info('Suppression en cours...');

        DB::transaction(function () use ($caissePrincipaleId, $caissePrincipaleName) {
            // 1. Corrections financières (références sur les autres tables)
            DB::table('finance_corrections')->delete();
            $this->line('  ✓ finance_corrections vidée');

            // 2. Transferts inter-caisses (référencent caisse_transactions)
            DB::table('caisse_transfers')->delete();
            $this->line('  ✓ caisse_transfers vidée');

            // 3. Transactions de caisse (entrées / sorties)
            DB::table('caisse_transactions')->delete();
            $this->line('  ✓ caisse_transactions vidée');

            // 4. Clôtures de caisse
            DB::table('caisse_closures')->delete();
            $this->line('  ✓ caisse_closures vidée');

            // 4b. Caisses supplémentaires (garder uniquement la caisse principale)
            if ($caissePrincipaleId) {
                DB::table('caisses')->where('id', '!=', $caissePrincipaleId)->delete();
            } else {
                DB::table('caisses')->delete();
            }
            $this->line('  ✓ caisses supplémentaires supprimées (conservée : "' . $caissePrincipaleName . '")');

            // 5. Table pivot invoice_payment
            DB::table('invoice_payment')->delete();
            $this->line('  ✓ invoice_payment (pivot) vidée');

            // 6. Paiements clients
            DB::table('payments')->delete();
            $this->line('  ✓ payments vidée');

            // 7. Transactions comptables de type payment
            DB::table('transactions')->where('type', 'payment')->delete();
            $this->line('  ✓ transactions (type=payment) supprimées');

            // 8. Historiques des soldes clients
            DB::table('historique_client_soldes')->delete();
            $this->line('  ✓ historique_client_soldes vidée');

            // 9. Historiques comptabilité
            DB::table('accounting_histories')->delete();
            $this->line('  ✓ accounting_histories vidée');

            // 10. Réinitialisation des soldes clients
            DB::table('clients')->update([
                'amount_payment' => 0,
                'amount_solde'   => DB::raw('amount_due'),
            ]);
            $this->line('  ✓ clients : amount_payment = 0, amount_solde = amount_due');

            // 11. Réinitialisation des factures non annulées
            DB::table('invoices')
                ->where('status', '!=', 'canceled')
                ->update([
                    'montant_solde' => 0,
                    'status'        => 'pending',
                ]);
            $this->line('  ✓ invoices (non annulées) : montant_solde = 0, status = pending');
        });

        $this->line('');
        $this->info('✅  Réinitialisation terminée avec succès.');

        return self::SUCCESS;
    }
}
