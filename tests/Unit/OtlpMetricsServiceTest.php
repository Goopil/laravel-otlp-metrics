<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Services\MetricsService;
use Goopil\OtlpMetrics\Tests\TestCase;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\ObservableCounterInterface;
use OpenTelemetry\API\Metrics\ObservableGaugeInterface;

class OtlpMetricsServiceTest extends TestCase
{
    public function test_can_create_counter(): void
    {
        $service = $this->app->make(MetricsService::class);
        $counter = $service->counter('test_counter', 'Test counter', '1');

        $this->assertInstanceOf(CounterInterface::class, $counter);
    }

    public function test_can_create_gauge(): void
    {
        $service = $this->app->make(MetricsService::class);
        $gauge = $service->gauge('test_gauge', 'Test gauge', '1');

        $this->assertInstanceOf(GaugeInterface::class, $gauge);
    }

    public function test_can_create_histogram(): void
    {
        $service = $this->app->make(MetricsService::class);
        $histogram = $service->histogram('test_histogram', 'Test histogram', 'ms');

        $this->assertInstanceOf(HistogramInterface::class, $histogram);
    }

    public function test_can_create_observable_counter(): void
    {
        $service = $this->app->make(MetricsService::class);
        $observableCounter = $service->observableCounter(
            'test_observable_counter',
            fn () => 1,
            'Test observable counter',
            '1'
        );

        $this->assertInstanceOf(ObservableCounterInterface::class, $observableCounter);
    }

    public function test_can_create_observable_gauge(): void
    {
        $service = $this->app->make(MetricsService::class);
        $observableGauge = $service->observableGauge(
            'test_observable_gauge',
            fn () => 1.0,
            'Test observable gauge',
            '1'
        );

        $this->assertInstanceOf(ObservableGaugeInterface::class, $observableGauge);
    }
}
