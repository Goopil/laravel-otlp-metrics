<?php

namespace Goopil\OtlpMetrics\Services;

use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Support\Metrics\AttributeService;
use Goopil\OtlpMetrics\Support\Metrics\InstrumentRegistry;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\Noop\NoopCounter;
use OpenTelemetry\API\Metrics\Noop\NoopGauge;
use OpenTelemetry\API\Metrics\Noop\NoopHistogram;
use OpenTelemetry\API\Metrics\Noop\NoopMeter;
use OpenTelemetry\API\Metrics\Noop\NoopObservableCounter;
use OpenTelemetry\API\Metrics\Noop\NoopObservableGauge;
use OpenTelemetry\API\Metrics\ObservableCounterInterface;
use OpenTelemetry\API\Metrics\ObservableGaugeInterface;

class NullMetricsService implements MetricsServiceInterface
{
    public function __construct(
        protected AttributeService $attributeManager,
        protected ?InstrumentRegistry $instrumentCache = null
    ) {}

    public function counter(string $name, ?string $description = null, ?string $unit = null, array $tags = []): CounterInterface
    {
        return new NoopCounter();
    }

    public function observableCounter(string $name, callable $callback, ?string $description = null, ?string $unit = null, array $tags = []): ObservableCounterInterface
    {
        return new NoopObservableCounter();
    }

    public function gauge(string $name, ?string $description = null, ?string $unit = null, array $tags = []): GaugeInterface
    {
        return new NoopGauge();
    }

    public function observableGauge(string $name, callable $callback, ?string $description = null, ?string $unit = null, array $tags = []): ObservableGaugeInterface
    {
        return new NoopObservableGauge();
    }

    public function histogram(string $name, ?string $description = null, ?string $unit = null, array $tags = []): HistogramInterface
    {
        return new NoopHistogram();
    }

    public function export(): void
    {
        // Do nothing
    }

    public function exportIfReady(int $interval): void
    {
        // Do nothing
    }

    public function shutdown(): void
    {
        // Do nothing
    }

    public function getMeter(): MeterInterface
    {
        return new NoopMeter();
    }

    public function getAttributeManager(): AttributeService
    {
        return $this->attributeManager;
    }
}
