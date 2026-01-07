<?php

namespace Goopil\OtlpMetrics\Tests\Feature;

use Goopil\OtlpMetrics\Configs\ApiConfig;
use Goopil\OtlpMetrics\Exceptions\ProtocolException;
use Goopil\OtlpMetrics\Services\MetricsService;
use Goopil\OtlpMetrics\Support\Metrics\AttributeService;
use Goopil\OtlpMetrics\Support\Metrics\InstrumentRegistry;
use Goopil\OtlpMetrics\Support\Metrics\MetricsFactory;
use Goopil\OtlpMetrics\Tests\TestCase;

class ProtocolTest extends TestCase
{
    public function test_http_protocol_initialization(): void
    {
        config(['otl-metrics.api.protocol' => 'http/protobuf']);
        config(['otl-metrics.api.endpoint' => 'http://localhost:4318/v1/metrics']);

        $contextConfig = $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class);
        $service = new MetricsService(
            $this->app->make(MetricsFactory::class),
            $this->app->make(AttributeService::class),
            $this->app->make(InstrumentRegistry::class),
            ApiConfig::fromArray([
                'endpoint' => 'http://localhost:4318/v1/metrics',
                'protocol' => 'http/protobuf',
            ]),
            $contextConfig->common,
            $this->app->make(\Psr\Log\LoggerInterface::class)
        );

        $this->assertInstanceOf(MetricsService::class, $service);
    }

    public function test_grpc_protocol_initialization(): void
    {
        if (! class_exists(\OpenTelemetry\Contrib\Grpc\GrpcTransportFactory::class) || ! extension_loaded('grpc')) {
            $this->markTestSkipped('gRPC support is not installed or grpc extension is missing.');
        }

        $contextConfig = $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class);
        $service = new MetricsService(
            $this->app->make(MetricsFactory::class),
            $this->app->make(AttributeService::class),
            $this->app->make(InstrumentRegistry::class),
            ApiConfig::fromArray([
                'endpoint' => 'http://localhost:4317',
                'protocol' => 'grpc',
            ]),
            $contextConfig->common,
            $this->app->make(\Psr\Log\LoggerInterface::class)
        );

        $this->assertInstanceOf(MetricsService::class, $service);
    }

    public function test_invalid_protocol_throws_exception(): void
    {
        $this->expectException(ProtocolException::class);

        $contextConfig = $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class);
        new MetricsService(
            $this->app->make(MetricsFactory::class),
            $this->app->make(AttributeService::class),
            $this->app->make(InstrumentRegistry::class),
            ApiConfig::fromArray([
                'endpoint' => 'http://localhost:4318',
                'protocol' => 'invalid-protocol',
            ]),
            $contextConfig->common,
            $this->app->make(\Psr\Log\LoggerInterface::class)
        );
    }

    public function test_http_json_protocol_initialization(): void
    {
        $contextConfig = $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class);
        $service = new MetricsService(
            $this->app->make(MetricsFactory::class),
            $this->app->make(AttributeService::class),
            $this->app->make(InstrumentRegistry::class),
            ApiConfig::fromArray([
                'endpoint' => 'http://localhost:4318/v1/metrics',
                'protocol' => 'http/json',
            ]),
            $contextConfig->common,
            $this->app->make(\Psr\Log\LoggerInterface::class)
        );

        $this->assertInstanceOf(MetricsService::class, $service);
    }
}
