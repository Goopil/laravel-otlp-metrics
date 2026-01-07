<?php

namespace Goopil\OtlpMetrics\Facades;

use Goopil\OtlpMetrics\Services\MetricsService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \OpenTelemetry\API\Metrics\CounterInterface counter(string $name, ?string $description = null, ?string $unit = null)
 * @method static \OpenTelemetry\API\Metrics\ObservableCounterInterface observableCounter(string $name, callable $callback, ?string $description = null, ?string $unit = null)
 * @method static \OpenTelemetry\API\Metrics\GaugeInterface gauge(string $name, ?string $description = null, ?string $unit = null)
 * @method static \OpenTelemetry\API\Metrics\ObservableGaugeInterface observableGauge(string $name, callable $callback, ?string $description = null, ?string $unit = null)
 * @method static \OpenTelemetry\API\Metrics\HistogramInterface histogram(string $name, ?string $description = null, ?string $unit = null)
 * @method static void export()
 * @method static void shutdown()
 * @method static \OpenTelemetry\API\Metrics\MeterInterface getMeter()
 *
 * @see \Goopil\OtlpMetrics\Services\MetricsService
 */
class OtlpMetrics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MetricsService::class;
    }
}
