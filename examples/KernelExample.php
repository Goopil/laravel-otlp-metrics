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
        // Exemple 1 : Utiliser la macro withMetrics sur une commande spécifique
        $schedule->command('backup:database')
            ->daily()
            ->withMetrics('database_backup', 'Database backup scheduled task');

        // Exemple 2 : Utiliser la macro trackMetrics pour tracker toutes les tâches
        $schedule->trackMetrics('scheduled_task_executions');

        // Exemple 3 : Combinaison avec d'autres méthodes Laravel
        $schedule->command('clean:cache')
            ->hourly()
            ->withMetrics('cache_cleanup', 'Cache cleanup task')
            ->onSuccess(function () {
                // Votre logique ici
            });

        // Exemple 4 : Tâche avec métriques personnalisées
        $schedule->call(function () {
            // Votre logique ici
        })
            ->everyMinute()
            ->withMetrics('custom_task', 'Custom scheduled task');
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
