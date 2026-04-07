<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    /**
     * Reçoit les données envoyées par le client (Push).
     */
    public function push(Request $request, $tableName)
    {
        $data = $request->input('data', []);
        $synced = [];

        if (empty($data)) {
            return response()->json(['message' => 'Aucune donnée envoyée', 'synced' => []]);
        }

        foreach ($data as $itemData) {
            // Nettoyer les données pour éviter les conflits d'ID
            $uuid = $itemData['uuid'];
            unset($itemData['id']); // On laisse la base distante gérer son propre ID
            unset($itemData['remote_id']); // On ne touche pas au remote_id du local (c'est nous)

            // Mise à jour ou création basée sur l'UUID
            $updateData = array_merge($itemData, ['updated_at' => now()]);

            // Gérer le slug pour les tables qui en ont un (ex: clients)
            if (isset($updateData['name']) && !isset($updateData['slug'])) {
                $updateData['slug'] = Str::slug($updateData['name']);
            }

            // Convertir les dates ISO en format SQL si nécessaire
            foreach ($updateData as $key => $value) {
                if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
                    try {
                        $updateData[$key] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        // Conserver la valeur originale si le parsing échoue
                    }
                }
            }

            try {
                DB::table($tableName)->updateOrInsert(
                    ['uuid' => $uuid],
                    $updateData
                );
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Si une contrainte d'unicité (autre que l'UUID) est violée, on tente de forcer la mise à jour
                // car cela peut arriver avec les slugs par exemple.
                unset($updateData['slug']);
                DB::table($tableName)->where('uuid', $uuid)->update($updateData);
            }

            // Récupérer l'ID généré pour le renvoyer au local
            $record = DB::table($tableName)->where('uuid', $uuid)->first();

            $synced[] = [
                'uuid' => $uuid,
                'id' => $record->id
            ];
        }

        return response()->json([
            'message' => 'Synchronisation réussie',
            'synced' => $synced
        ]);
    }

    /**
     * Envoie les nouveautés au client (Pull).
     */
    public function pull(Request $request, $tableName)
    {
        $since = $request->query('since');

        $query = DB::table($tableName);

        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        $data = $query->get();

        return response()->json([
            'data' => $data
        ]);
    }
}
