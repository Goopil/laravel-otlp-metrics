<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Support\Metrics\InstrumentRegistry;
use Goopil\OtlpMetrics\Tests\TestCase;

class OctaneCompatibilityTest extends TestCase
{
    public function test_instruments_cache_persists_across_requests(): void
    {
        $service1 = app(MetricsServiceInterface::class);

        $counter1 = $service1->counter('test_counter');
        $registry1 = app(InstrumentRegistry::class);

        $this->assertTrue($registry1->has('counter.test_counter'));

        // Simulate request end
        $this->app->forgetScopedInstances();

        $service2 = app(MetricsServiceInterface::class);
        $registry2 = app(InstrumentRegistry::class);
        $this->assertSame($registry1, $registry2);
        $this->assertTrue($registry2->has('counter.test_counter'));

        $counter2 = $service2->counter('test_counter');
        $this->assertNotSame($counter1, $counter2); // Wrappers should be different for Octane safety

        // But they should wrap the same underlying OTEL instrument
        $reflection = new \ReflectionClass($counter1);
        $property = $reflection->getProperty('instrument');
        $property->setAccessible(true);

        $this->assertSame($property->getValue($counter1), $property->getValue($counter2));
    }

    public function test_meter_provider_persists_across_requests(): void
    {
        $service1 = app(MetricsServiceInterface::class);
        $meter1 = $service1->getMeter();

        $this->app->forgetScopedInstances();

        $service2 = app(MetricsServiceInterface::class);
        $this->assertNotSame($service1, $service2); // MetricsService is now scoped

        $meter2 = $service2->getMeter();
        $this->assertSame($meter1, $meter2); // But Meter Provider persists!
    }
}
