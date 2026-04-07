<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncService
{
    protected $remoteUrl;
    protected $apiToken;

    public function __construct()
    {
        $this->remoteUrl = config('services.sync.url', env('SYNC_REMOTE_URL'));
        $this->apiToken = config('services.sync.token', env('SYNC_API_TOKEN'));
    }

    /**
     * Envoie les données locales vers le serveur.
     */
    public function push($modelClass)
    {
        $items = $modelClass::whereNull('last_synced_at')
                            ->orWhereColumn('updated_at', '>', 'last_synced_at')
                            ->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $tableName = (new $modelClass)->getTable();

        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(60) // Augmenter le timeout à 60 secondes
                ->connectTimeout(10) // Timeout de connexion court
                ->post($this->remoteUrl . "/api/sync/{$tableName}", [
                    'data' => $items->toArray()
                ]);

            if ($response->successful()) {
                $results = $response->json();
                foreach ($items as $item) {
                    $remoteData = collect($results['synced'] ?? [])->firstWhere('uuid', $item->uuid);
                    if ($remoteData) {
                        // Utiliser forceFill car last_synced_at et remote_id ne sont pas toujours dans $fillable
                        $item->forceFill([
                            'remote_id' => $remoteData['id'],
                            'last_synced_at' => now(),
                        ])->save();
                    }
                }
                return count($items);
            }
        } catch (\Exception $e) {
            Log::error("Erreur de synchronisation (Push) pour {$tableName}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Récupère les données du serveur vers le local.
     */
    public function pull($modelClass)
    {
        $tableName = (new $modelClass)->getTable();
        $lastSync = $modelClass::max('last_synced_at');

        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(60)
                ->connectTimeout(10)
                ->get($this->remoteUrl . "/api/sync/{$tableName}", [
                    'since' => $lastSync
                ]);

            if ($response->successful()) {
                $remoteItems = $response->json('data');
                foreach ($remoteItems as $data) {
                    $modelClass::updateOrCreate(
                        ['uuid' => $data['uuid']],
                        array_merge($data, [
                            'remote_id' => $data['id'],
                            'last_synced_at' => now()
                        ])
                    );
                }
                return count($remoteItems);
            }
        } catch (\Exception $e) {
            Log::error("Erreur de synchronisation (Pull) pour {$tableName}: " . $e->getMessage());
        }

        return false;
    }
}
