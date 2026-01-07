<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->afterResolving(\Illuminate\Console\Scheduling\Schedule::class, function (\Illuminate\Console\Scheduling\Schedule $schedule) {
            $schedule->command('test:fast')->everyMinute();
            $schedule->command('test:slow')->everyMinute();
            $schedule->command('test:fail')->everyMinute();
        });

        if (! $this->app->runningInConsole()) {
            $this->app->make(\Illuminate\Contracts\Http\Kernel::class)
                ->prependMiddleware(\Goopil\OtlpMetrics\Http\Middleware\MetricsMiddleware::class);
        }
    }
}
