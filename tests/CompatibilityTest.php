<?php

namespace Goopil\OtlpMetrics\Tests;

use Goopil\OtlpMetrics\Facades\OtlpContext;
use Goopil\OtlpMetrics\Services\MetricsService;
use Goopil\OtlpMetrics\Support\Metrics\AttributeService;

class CompatibilityTest extends TestCase
{
    /**
     * Test that the package works with PHP 8.1+
     */
    public function test_php_version_compatibility(): void
    {
        $phpVersion = PHP_VERSION;
        $this->assertTrue(
            version_compare($phpVersion, '8.1.0', '>='),
            "PHP version {$phpVersion} is not supported. Minimum required: 8.1.0"
        );
    }

    /**
     * Test that all services can be instantiated
     */
    public function test_services_can_be_instantiated(): void
    {
        // Test MetricsService
        $service = app(MetricsService::class);
        $this->assertInstanceOf(MetricsService::class, $service);

        // Test AttributeManager
        $attributeManager = app(AttributeService::class);
        $this->assertInstanceOf(AttributeService::class, $attributeManager);

        // Test ContextDetector
        $context = OtlpContext::detect();
        $this->assertContains($context, ['api', 'worker', 'cronjob']);
    }

    /**
     * Test that metrics can be created with all PHP 8.1+ features
     */
    public function test_metrics_creation_with_union_types(): void
    {
        $service = app(MetricsService::class);

        // Test counter with union types (int|float)
        $counter = $service->counter('test_counter', 'Test counter');
        $counter->add(1); // int
        $counter->add(1.5); // float

        // Test histogram with union types
        $histogram = $service->histogram('test_histogram', 'Test histogram', 'ms');
        $histogram->record(100); // int
        $histogram->record(150.5); // float

        $this->assertTrue(true); // If we get here, union types work
    }

    /**
     * Test that match expressions work (PHP 8.0+)
     */
    public function test_match_expressions_compatibility(): void
    {
        $protocol = 'http/protobuf';
        $result = match (strtolower($protocol)) {
            'grpc' => 'grpc',
            'http/protobuf', 'http/json' => 'http',
            default => 'unknown',
        };

        $this->assertEquals('http', $result);
    }

    /**
     * Test that nullable types work correctly
     */
    public function test_nullable_types_compatibility(): void
    {
        $service = app(MetricsService::class);

        // Test with nullable parameters
        $counter = $service->counter('test', null, null);
        $this->assertInstanceOf(\OpenTelemetry\API\Metrics\CounterInterface::class, $counter);
    }

    /**
     * Test that constructor property promotion works
     */
    public function test_constructor_property_promotion(): void
    {
        $attributeManager = new AttributeService();
        $attributeManager->addAttribute('test', 'value');

        $attributes = $attributeManager->getAttributes();
        $this->assertArrayHasKey('test', $attributes);
        $this->assertEquals('value', $attributes['test']);
    }
}
