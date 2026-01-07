<?php

namespace Goopil\OtlpMetrics\Providers;

use Goopil\OtlpMetrics\Configs\ContextConfig;
use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Services\ContextService;
use Goopil\OtlpMetrics\Trackers\Queue\HorizonTracker;
use Goopil\OtlpMetrics\Trackers\Queue\QueueTracker;
use Goopil\OtlpMetrics\Trackers\Queue\Storage\QueueStorage;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Events\MasterSupervisorLooped;

class WorkerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $config = $this->app->make(ContextConfig::class);
        $context = $this->app->make(ContextService::class);

        if ($this->shouldTrackQueueJobs($config)) {
            $this->app->singleton(QueueStorage::class);
            $this->app->singleton(QueueTracker::class);
        }

        if ($this->shouldRegisterHorizon($config, $context)) {
            $this->app->singleton(HorizonTracker::class);
        }

        $this->app->when($this->provides())
            ->needs('$logger')
            ->give(fn ($app) => $app->make('log')->channel($app->make(ContextConfig::class)->worker->logChannel));
    }

    public function provides(): array
    {
        return [
            QueueStorage::class,
            QueueTracker::class,
            HorizonTracker::class,
        ];
    }

    public function boot(): void
    {
        if ($this->app->bound(QueueTracker::class)) {
            $this->app->make(QueueTracker::class)->register();
            $this->registerWorkerPeriodicExport();
        }

        if ($this->app->bound(HorizonTracker::class)) {
            $this->app->make(HorizonTracker::class)->register();
            $this->registerHorizonPeriodicExport();
        }
    }

    protected function registerWorkerPeriodicExport(): void
    {
        $this->registerPeriodicExport(
            Looping::class,
            $this->app->make(ContextConfig::class)->common->batchExportInterval
        );
    }

    protected function registerHorizonPeriodicExport(): void
    {
        $this->registerPeriodicExport(
            MasterSupervisorLooped::class,
            $this->app->make(ContextConfig::class)->worker->horizonCollectionInterval
        );
    }

    /**
     * Register a periodic export listener for a specific event
     */
    protected function registerPeriodicExport(string $eventClass, int $interval): void
    {
        $this->app->afterResolving(MetricsServiceInterface::class, function (MetricsServiceInterface $service) use ($eventClass, $interval) {
            $this->app['events']->listen($eventClass, static function () use ($service, $interval) {
                $service->exportIfReady($interval);
            });
        });
    }

    protected function shouldTrackQueueJobs(ContextConfig $config): bool
    {
        return $config->worker->enabled &&
            $config->worker->trackJobs;
    }

    protected function shouldRegisterHorizon(ContextConfig $config, ContextService $detector): bool
    {
        return
            HorizonTracker::isAvailable($this->app->bound(\Laravel\Horizon\Contracts\MetricsRepository::class)) &&
            $detector->isHorizon() &&
            $config->worker->horizonEnabled;
    }
}
