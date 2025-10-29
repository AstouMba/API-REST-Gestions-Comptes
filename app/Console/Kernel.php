<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Vérifier et bloquer les comptes dont la date de blocage est échue (toutes les heures)
        $schedule->job(new \App\Jobs\ProcessScheduledAccountBlockingJob)->hourly();

        // Vérifier les comptes à archiver quotidiennement à minuit
        $schedule->job(new \App\Jobs\ArchiveBlockedAccountsJob)->dailyAt('00:00');
        
        // Vérifier les comptes à désarchiver quotidiennement à 00:30
        $schedule->job(new \App\Jobs\UnarchiveAccountsJob)->dailyAt('00:30');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
