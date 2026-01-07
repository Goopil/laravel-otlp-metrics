<?php

namespace Goopil\OtlpMetrics\Contracts;

use Goopil\OtlpMetrics\Support\Metrics\AttributeService;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\ObservableCounterInterface;
use OpenTelemetry\API\Metrics\ObservableGaugeInterface;

interface MetricsServiceInterface
{
    /**
     * Create or retrieve a counter metric
     */
    public function counter(string $name, ?string $description = null, ?string $unit = null, array $tags = []): CounterInterface;

    /**
     * Create or retrieve an observable counter metric
     */
    public function observableCounter(string $name, callable $callback, ?string $description = null, ?string $unit = null, array $tags = []): ObservableCounterInterface;

    /**
     * Create or retrieve a gauge metric
     */
    public function gauge(string $name, ?string $description = null, ?string $unit = null, array $tags = []): GaugeInterface;

    /**
     * Create or retrieve an observable gauge metric
     */
    public function observableGauge(string $name, callable $callback, ?string $description = null, ?string $unit = null, array $tags = []): ObservableGaugeInterface;

    /**
     * Create or retrieve a histogram metric
     */
    public function histogram(string $name, ?string $description = null, ?string $unit = null, array $tags = []): HistogramInterface;

    /**
     * Force export of metrics
     */
    public function export(): void;

    /**
     * Export metrics if the provided interval has passed since last export
     */
    public function exportIfReady(int $interval): void;

    /**
     * Shutdown the meter provider
     */
    public function shutdown(): void;

    /**
     * Get the meter instance
     */
    public function getMeter(): MeterInterface;

    /**
     * Get the attribute manager instance
     */
    public function getAttributeManager(): AttributeService;
}
