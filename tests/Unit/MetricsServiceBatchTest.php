<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Configs\ApiConfig;
use Goopil\OtlpMetrics\Configs\ContextConfig;
use Goopil\OtlpMetrics\Services\MetricsService;
use Goopil\OtlpMetrics\Support\Metrics\AttributeService;
use Goopil\OtlpMetrics\Support\Metrics\InstrumentRegistry;
use Goopil\OtlpMetrics\Support\Metrics\MetricsFactory;
use Goopil\OtlpMetrics\Tests\TestCase;
use Mockery;
use OpenTelemetry\SemConv\ResourceAttributes;

class MetricsServiceBatchTest extends TestCase
{
    public function test_export_if_ready_calls_export_when_interval_passed(): void
    {
        $contextConfig = ContextConfig::fromArray([
            'attributes' => [
                ResourceAttributes::SERVICE_NAME => 'test-service',
                ResourceAttributes::SERVICE_NAMESPACE => 'test-namespace',
                ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => 'testing',
            ],
        ]);
        $config = ApiConfig::fromArray([
            'endpoint' => null,
            'protocol' => 'http/protobuf',
            'headers' => [],
        ]);

        /** @var MetricsService|Mockery\MockInterface $service */
        $service = Mockery::mock(MetricsService::class, [
            Mockery::mock(MetricsFactory::class),
            Mockery::mock(AttributeService::class),
            Mockery::mock(InstrumentRegistry::class),
            $config,
            $contextConfig->common,
            $this->app->make(\Psr\Log\LoggerInterface::class),
            [],
        ])->makePartial();
        $service->shouldAllowMockingProtectedMethods();

        // Mock export to update lastExportAt
        $service->shouldReceive('export')->andReturnUsing(function () use ($service) {
            $reflection = new \ReflectionClass(MetricsService::class);
            $property = $reflection->getProperty('lastExportAt');
            $property->setAccessible(true);
            $property->setValue($service, time());
        });

        // First call should trigger export because lastExportAt is 0
        $service->exportIfReady(10);

        // Second call immediately after should NOT trigger export
        $service->exportIfReady(10);

        // Manually set lastExportAt to something old
        $reflection = new \ReflectionClass(MetricsService::class);
        $property = $reflection->getProperty('lastExportAt');
        $property->setAccessible(true);
        $property->setValue($service, time() - 20);

        // Third call should trigger export because 20s have passed
        $service->exportIfReady(10);

        $service->shouldHaveReceived('export')->twice();
    }
}
