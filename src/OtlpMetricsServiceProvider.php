<?php

namespace Goopil\OtlpMetrics;

use Goopil\OtlpMetrics\Configs\ContextConfig;
use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Providers\ApiServiceProvider;
use Goopil\OtlpMetrics\Providers\CronJobServiceProvider;
use Goopil\OtlpMetrics\Providers\WorkerServiceProvider;
use Goopil\OtlpMetrics\Services\ContextService;
use Goopil\OtlpMetrics\Services\MetricsService;
use Goopil\OtlpMetrics\Services\NullMetricsService;
use Goopil\OtlpMetrics\Support\Metrics\AttributeService;
use Goopil\OtlpMetrics\Support\Metrics\InstrumentRegistry;
use Goopil\OtlpMetrics\Support\Metrics\MetricsFactory;
use Goopil\OtlpMetrics\Trackers\CronJob\CronJobTracker;
use Goopil\OtlpMetrics\Trackers\Http\HttpTracker;
use Goopil\OtlpMetrics\Trackers\Queue\QueueTracker;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class OtlpMetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/otlp-metrics.php',
            'otlp-metrics'
        );

        $this->app->singleton(ContextService::class, function ($app) {
            return new ContextService(
                $app->make(ContextConfig::class),
                $app->runningInConsole()
            );
        });
        $this->app->singleton(MetricsFactory::class);

        $this->app->singleton(
            ContextConfig::class,
            fn ($app) => ContextConfig::fromArray($app['config']->get('otlp-metrics', []))
        );

        $this->app->singleton(InstrumentRegistry::class, function ($app) {
            $config = $app->make(ContextConfig::class);

            return new InstrumentRegistry([], $config->common->instrumentCacheLimit);
        });
        $this->app->scoped(AttributeService::class, function ($app) {
            $detector = $app->make(ContextService::class);

            $service = new AttributeService();
            $service->addAttribute('service.context', $detector->detect());

            return $service;
        });

        $this->app->scoped(MetricsServiceInterface::class, function ($app) {
            $detector = $app->make(ContextService::class);

            if (! $detector->isEnabled()) {
                return new NullMetricsService($app->make(AttributeService::class));
            }

            $config = $app->make(ContextConfig::class);
            $attributes = $config->common->attributes;
            $attributes['service.context'] = $detector->detect();
            $contextConfig = $detector->getActiveContextConfig();

            $logger = $app->make(LoggerInterface::class);

            if ($contextConfig->logChannel && method_exists($logger, 'channel')) {
                $logger = $logger->channel($contextConfig->logChannel);
            }

            return new MetricsService(
                $app->make(MetricsFactory::class),
                $app->make(AttributeService::class),
                $app->make(InstrumentRegistry::class),
                $contextConfig,
                $config->common,
                $logger,
                $attributes
            );
        });

        $this->app->alias(MetricsServiceInterface::class, MetricsService::class);

        $this->app->register(ApiServiceProvider::class);
        $this->app->register(WorkerServiceProvider::class);
        $this->app->register(CronJobServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/otlp-metrics.php' => config_path('otlp-metrics.php'),
            ], 'otlp-metrics-config');
        }

        $detector = $this->app->make(ContextService::class);

        if ($detector->isEnabled()) {
            match ($detector->detect()) {
                'worker' => $this->app->make(QueueTracker::class),
                'cronjob' => $this->app->make(CronJobTracker::class),
                'api' => $this->app->make(HttpTracker::class),
                default => null,
            };
        }

        $this->app->terminating(function () {
            if ($this->app->resolved(MetricsServiceInterface::class)) {
                $service = $this->app->make(MetricsServiceInterface::class);

                if ($this->app->bound('octane')) {
                    $config = $this->app->make(ContextConfig::class);
                    $service->exportIfReady($config->common->batchExportInterval);
                } else {
                    $service->shutdown();
                }
            }
        });
    }
}
