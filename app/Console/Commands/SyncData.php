<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\SyncService;
use App\Models\Article;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Supplier;

class SyncData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les données locales avec le serveur distant';

    /**
     * Execute the console command.
     */
    public function handle(SyncService $syncService)
    {
        $models = [
            Supplier::class,
            Article::class,
            Client::class,
            Invoice::class,
        ];

        $this->info("Début de la synchronisation...");

        foreach ($models as $model) {
            $name = class_basename($model);
            $this->comment("Synchro {$name}...");

            // Push
            $pushed = $syncService->push($model);
            if ($pushed !== false) {
                $this->info("  - {$pushed} {$name} envoyés.");
            } else {
                $this->error("  - Erreur lors de l'envoi de {$name}.");
            }

            // Pull
            $pulled = $syncService->pull($model);
            if ($pulled !== false) {
                $this->info("  - {$pulled} {$name} récupérés.");
            } else {
                $this->error("  - Erreur lors de la récupération de {$name}.");
            }
        }

        $this->info("Synchronisation terminée.");
    }
}
