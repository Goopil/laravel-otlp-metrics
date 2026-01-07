<?php

namespace Goopil\OtlpMetrics\Tests;

use Goopil\OtlpMetrics\OtlpMetricsServiceProvider;
use OpenTelemetry\SemConv\ResourceAttributes;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            OtlpMetricsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('otlp-metrics.api.endpoint', 'http://localhost:4318/v1/metrics');
        $app['config']->set('otlp-metrics.api.protocol', 'http/protobuf');
        $app['config']->set('otlp-metrics.attributes', [
            ResourceAttributes::SERVICE_NAME => 'test-service',
            ResourceAttributes::SERVICE_NAMESPACE => 'test-namespace',
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => 'testing',
        ]);
    }
}
