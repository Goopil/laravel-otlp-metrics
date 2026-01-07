<?php

namespace Goopil\OtlpMetrics\Providers;

use Goopil\OtlpMetrics\Configs\ContextConfig;
use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Support\Macros\SchedulerMacros;
use Goopil\OtlpMetrics\Trackers\CronJob\CronJobTracker;
use Goopil\OtlpMetrics\Trackers\CronJob\Storage\CronjobStorage;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CronJobServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $config = $this->app->make(ContextConfig::class);

        if ($this->shouldTrackCronJobs($config)) {
            $this->app->singleton(CronjobStorage::class);
            $this->app->singleton(CronJobTracker::class);
        }

        $this->app->when($this->provides())
            ->needs('$logger')
            ->give(fn ($app) => $app->make('log')->channel($app->make(ContextConfig::class)->cronjob->logChannel));
    }

    public function provides(): array
    {
        return [
            CronjobStorage::class,
            CronJobTracker::class,
        ];
    }

    public function boot(): void
    {
        if ($this->app->bound(CronJobTracker::class)) {
            $this->app->make(CronJobTracker::class)->register();
        }

        $config = $this->app->make(ContextConfig::class);
        if ($this->shouldRegisterSchedulerMacros($config)) {
            SchedulerMacros::register($this->app->make(MetricsServiceInterface::class));
        }
    }

    protected function shouldTrackCronJobs(ContextConfig $config): bool
    {
        return $config->cronjob->enabled &&
            $config->cronjob->trackScheduledTasks;
    }

    protected function shouldRegisterSchedulerMacros(ContextConfig $config): bool
    {
        return $config->cronjob->enabled &&
            $config->cronjob->trackScheduledTasks;
    }
}
