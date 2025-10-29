<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UnarchiveAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Restaurer quotidiennement les comptes dont la date de déblocage est échue
        $service = app(\App\Services\CompteBlockageService::class);
        try {
            Log::info('UnarchiveAccountsJob: démarrage du déblocage des comptes échus');
            $service->debloquerComptesEchus();
            Log::info('UnarchiveAccountsJob: déblocage terminé');
        } catch (\Throwable $e) {
            // Ne pas laisser une exception non catchée casser la queue sans logging
            Log::error('UnarchiveAccountsJob: erreur lors du déblocage des comptes - ' . $e->getMessage(), [
                'exception' => $e
            ]);
            // Rejeter l'exception pour la politique de retry de la queue si nécessaire
            throw $e;
        }
    }
}
