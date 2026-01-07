<?php

namespace Goopil\OtlpMetrics\Tests\Feature;

use Goopil\OtlpMetrics\Facades\OtlpMetrics;
use Goopil\OtlpMetrics\Services\MetricsService;
use Goopil\OtlpMetrics\Tests\TestCase;
use OpenTelemetry\API\Metrics\CounterInterface;

class MetricsIntegrationTest extends TestCase
{
    public function test_facade_works(): void
    {
        $counter = OtlpMetrics::counter('facade_test', 'Test via facade');
        $counter->add(1);

        $this->assertInstanceOf(CounterInterface::class, $counter);
    }

    public function test_service_provider_registers_service(): void
    {
        $service = app(MetricsService::class);

        $this->assertInstanceOf(MetricsService::class, $service);
    }

    public function test_can_record_metrics(): void
    {
        $counter = OtlpMetrics::counter('test_requests', 'Test requests');
        $counter->add(5, ['endpoint' => '/test']);

        $histogram = OtlpMetrics::histogram('test_duration', 'Test duration', 'ms');
        $histogram->record(100.5, ['endpoint' => '/test']);

        $gauge = OtlpMetrics::gauge('test_active_connections', 'Active connections');
        $gauge->record(42, ['type' => 'database']);

        $this->assertTrue(true); // If we get here, metrics were created successfully
    }
}
