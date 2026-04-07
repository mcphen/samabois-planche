<?php

namespace App\Services;

use App\Models\CaisseTransfer;
use App\Models\CaisseTransaction;
use App\Models\FinanceCorrection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class CaisseTransferService
{
    /**
     * Annuler un transfert de caisse via une contre-écriture.
     */
    public function cancelTransfer(CaisseTransfer $transfer, string $reason): CaisseTransfer
    {
        if ($transfer->status === 'annulé') {
            throw new Exception("Ce transfert est déjà annulé.");
        }

        return DB::transaction(function () use ($transfer, $reason) {
            // 1. Créer le transfert inverse (contre-écriture)
            $reverseTransfer = CaisseTransfer::create([
                'source_caisse_id'      => $transfer->destination_caisse_id,
                'destination_caisse_id' => $transfer->source_caisse_id,
                'amount_source'         => $transfer->amount_destination,
                'amount_destination'    => $transfer->amount_source,
                'exchange_rate'         => $transfer->exchange_rate != 0 ? 1 / $transfer->exchange_rate : 0,
                'description'           => "Annulation du transfert #{$transfer->id} : {$reason}",
                'transfer_date'         => now(),
                'status'                => 'validé',
            ]);

            // 2. Créer les transactions associées au transfert inverse
            // Sortie de l'ancienne destination
            $srcTxn = CaisseTransaction::create([
                'caisse_id'          => $reverseTransfer->source_caisse_id,
                'type'               => 'sortie',
                'movement_type'      => CaisseTransaction::MOV_TRANSFERT_SORTANT,
                'amount'             => -$reverseTransfer->amount_source,
                'objet'              => "Contre-écriture transfert #{$transfer->id}",
                'description'        => $reverseTransfer->description,
                'date'               => $reverseTransfer->transfer_date,
                'caisse_transfer_id' => $reverseTransfer->id,
                'user_id'            => Auth::id(),
            ]);

            // Entrée dans l'ancienne source
            $destTxn = CaisseTransaction::create([
                'caisse_id'          => $reverseTransfer->destination_caisse_id,
                'type'               => 'entree',
                'movement_type'      => CaisseTransaction::MOV_TRANSFERT_ENTRANT,
                'amount'             => $reverseTransfer->amount_destination,
                'objet'              => "Contre-écriture transfert #{$transfer->id}",
                'description'        => $reverseTransfer->description,
                'date'               => $reverseTransfer->transfer_date,
                'caisse_transfer_id' => $reverseTransfer->id,
                'user_id'            => Auth::id(),
            ]);

            $reverseTransfer->update([
                'source_caisse_transaction_id'      => $srcTxn->id,
                'destination_caisse_transaction_id' => $destTxn->id,
            ]);

            // 3. Marquer l'ancien transfert comme annulé
            $transfer->update([
                'status'                => 'annulé',
                'corrected_transfer_id' => $reverseTransfer->id,
            ]);

            // 4. Journaliser dans FinanceCorrection
            FinanceCorrection::create([
                'correction_type'        => 'transfer_cancellation',
                'caisse_transfer_id'     => $transfer->id,
                'old_amount_source'      => $transfer->amount_source,
                'old_amount_destination' => $transfer->amount_destination,
                'reason'                 => $reason,
                'user_id'                => Auth::id(),
                'meta'                   => [
                    'reverse_transfer_id' => $reverseTransfer->id,
                ],
            ]);

            return $reverseTransfer;
        });
    }

    /**
     * Corriger un transfert erroné en l'annulant et en créant un nouveau.
     */
    public function correctTransfer(CaisseTransfer $transfer, array $newData, string $reason): CaisseTransfer
    {
        return DB::transaction(function () use ($transfer, $newData, $reason) {
            // 1. Annuler le transfert actuel
            $this->cancelTransfer($transfer, "Correction vers nouveau transfert : {$reason}");

            // 2. Créer le nouveau transfert correct
            $newTransfer = CaisseTransfer::create([
                'source_caisse_id'      => $newData['source_caisse_id'] ?? $transfer->source_caisse_id,
                'destination_caisse_id' => $newData['destination_caisse_id'],
                'amount_source'         => $newData['amount_source'],
                'amount_destination'    => $newData['amount_destination'] ?? $newData['amount_source'],
                'exchange_rate'         => $newData['exchange_rate'] ?? 1,
                'description'           => $newData['description'] ?? "Correction du transfert #{$transfer->id}",
                'transfer_date'         => $newData['transfer_date'] ?? now(),
                'status'                => 'validé',
            ]);

            // 3. Créer les transactions pour le nouveau transfert
            $srcTxn = CaisseTransaction::create([
                'caisse_id'          => $newTransfer->source_caisse_id,
                'type'               => 'sortie',
                'movement_type'      => CaisseTransaction::MOV_TRANSFERT_SORTANT,
                'amount'             => -$newTransfer->amount_source,
                'objet'              => "Transfert (Correction #{$transfer->id})",
                'description'        => $newTransfer->description,
                'date'               => $newTransfer->transfer_date,
                'caisse_transfer_id' => $newTransfer->id,
                'user_id'            => Auth::id(),
            ]);

            $destTxn = CaisseTransaction::create([
                'caisse_id'          => $newTransfer->destination_caisse_id,
                'type'               => 'entree',
                'movement_type'      => CaisseTransaction::MOV_TRANSFERT_ENTRANT,
                'amount'             => $newTransfer->amount_destination,
                'objet'              => "Transfert (Correction #{$transfer->id})",
                'description'        => $newTransfer->description,
                'date'               => $newTransfer->transfer_date,
                'caisse_transfer_id' => $newTransfer->id,
                'user_id'            => Auth::id(),
            ]);

            $newTransfer->update([
                'source_caisse_transaction_id'      => $srcTxn->id,
                'destination_caisse_transaction_id' => $destTxn->id,
            ]);

            // 4. Mettre à jour le statut du transfert initial à 'corrigé'
            $transfer->update(['status' => 'corrigé']);

            return $newTransfer;
        });
    }
}
