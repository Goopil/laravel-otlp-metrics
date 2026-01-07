<?php

namespace Goopil\OtlpMetrics\Providers;

use Goopil\OtlpMetrics\Configs\ContextConfig;
use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Http\Middleware\MetricsMiddleware;
use Goopil\OtlpMetrics\Support\Macros\SchedulerMacros;
use Goopil\OtlpMetrics\Trackers\Http\HttpTracker;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(HttpTracker::class);
        $this->app->scoped(MetricsMiddleware::class);

        $this->app->when($this->provides())
            ->needs('$logger')
            ->give(fn ($app) => $app->make('log')->channel($app->make(ContextConfig::class)->api->logChannel));
    }

    public function boot(): void
    {
        $config = $this->app->make(ContextConfig::class);

        if ($this->shouldRegisterSchedulerMacros($config)) {
            SchedulerMacros::register($this->app->make(MetricsServiceInterface::class));
        }
    }

    public function provides(): array
    {
        return [
            HttpTracker::class,
            MetricsMiddleware::class,
        ];
    }

    protected function shouldRegisterSchedulerMacros(ContextConfig $config): bool
    {
        return $config->cronjob->enabled &&
            $config->cronjob->trackScheduledTasks;
    }
}
