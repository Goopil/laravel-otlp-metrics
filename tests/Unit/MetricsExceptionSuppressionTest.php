<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Exceptions\MetricsExportException;
use Goopil\OtlpMetrics\Exceptions\MetricsInitializationException;
use Goopil\OtlpMetrics\Services\MetricsService;
use Goopil\OtlpMetrics\Support\Metrics\MetricsFactory;
use Goopil\OtlpMetrics\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Mockery;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;

class MetricsExceptionSuppressionTest extends TestCase
{
    public function test_it_suppresses_initialization_exceptions_when_configured(): void
    {
        $this->app->instance(MetricsFactory::class, new MetricsFactory());
        $this->app['config']->set('otlp-metrics.common.suppress_exceptions', true);

        // Refresh ContextConfig in container
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);

        // Mock factory to throw exception during initialization
        $factory = Mockery::mock(MetricsFactory::class);
        $factory->shouldReceive('createMeterProvider')
            ->andThrow(new \Exception('Transport error'));

        $this->app->instance(MetricsFactory::class, $factory);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'OTLP Metrics Initialization failed: Transport error')
                    && $context['exception'] instanceof MetricsInitializationException;
            });

        $contextConfig = $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class);
        $service = new MetricsService(
            $factory,
            $this->app->make(\Goopil\OtlpMetrics\Support\Metrics\AttributeService::class),
            $this->app->make(\Goopil\OtlpMetrics\Support\Metrics\InstrumentRegistry::class),
            $this->app->make(\Goopil\OtlpMetrics\Services\ContextService::class)->getActiveContextConfig(),
            $contextConfig->common,
            $this->app->make(\Psr\Log\LoggerInterface::class)
        );

        // Force initialization
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('ensureInitialized');
        $method->setAccessible(true);
        $method->invoke($service);

        // Should not throw exception when creating a metric
        $counter = $service->counter('test');

        $this->assertInstanceOf(\OpenTelemetry\API\Metrics\CounterInterface::class, $counter);
        $this->assertEquals('OpenTelemetry\API\Metrics\Noop\NoopCounter', get_class($counter));
    }

    public function test_it_suppresses_exceptions_when_configured(): void
    {
        $this->app['config']->set('otlp-metrics.common.suppress_exceptions', true);

        // Refresh instances
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Contracts\MetricsServiceInterface::class);

        $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->app->instance(\Psr\Log\LoggerInterface::class, $logger);

        $service = $this->app->make(\Goopil\OtlpMetrics\Contracts\MetricsServiceInterface::class);

        // Use reflection to set a mocked reader that throws an exception
        $reader = Mockery::mock(MetricReaderInterface::class);
        $reader->shouldReceive('collect')->andThrow(new \Exception('Collector connection failed'));

        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('reader');
        $property->setAccessible(true);
        $property->setValue($service, $reader);

        $logger->shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'OTLP Metrics Export failed: Collector connection failed')
                    && $context['exception'] instanceof MetricsExportException;
            });

        // Should not throw exception
        $service->export();

        $this->assertTrue(true); // If we reached here, no exception was thrown
    }

    public function test_it_throws_export_exception_when_configured_to_not_suppress(): void
    {
        $this->app['config']->set('otlp-metrics.common.suppress_exceptions', false);

        // Refresh instances
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Contracts\MetricsServiceInterface::class);

        $service = $this->app->make(\Goopil\OtlpMetrics\Contracts\MetricsServiceInterface::class);

        $reader = Mockery::mock(MetricReaderInterface::class);
        $reader->shouldReceive('collect')->andThrow(new \Exception('Collector connection failed'));

        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('reader');
        $property->setAccessible(true);
        $property->setValue($service, $reader);

        $this->expectException(MetricsExportException::class);
        $this->expectExceptionMessage('OTLP Metrics Export failed: Collector connection failed');

        $service->export();
    }

    public function test_it_throws_initialization_exception_when_configured_to_not_suppress(): void
    {
        $this->app->instance(MetricsFactory::class, new MetricsFactory());
        $this->app['config']->set('otlp-metrics.common.suppress_exceptions', false);

        // Refresh ContextConfig in container
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);

        $factory = Mockery::mock(MetricsFactory::class);
        $factory->shouldReceive('createMeterProvider')
            ->andThrow(new \Exception('Transport error'));

        $this->app->instance(MetricsFactory::class, $factory);

        $contextConfig = $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class);
        $service = new MetricsService(
            $factory,
            $this->app->make(\Goopil\OtlpMetrics\Support\Metrics\AttributeService::class),
            $this->app->make(\Goopil\OtlpMetrics\Support\Metrics\InstrumentRegistry::class),
            $this->app->make(\Goopil\OtlpMetrics\Services\ContextService::class)->getActiveContextConfig(),
            $contextConfig->common,
            $this->app->make(\Psr\Log\LoggerInterface::class)
        );

        $this->expectException(MetricsInitializationException::class);
        $this->expectExceptionMessage('OTLP Metrics Initialization failed: Transport error');

        // Force initialization
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('ensureInitialized');
        $method->setAccessible(true);
        $method->invoke($service);
    }
}
