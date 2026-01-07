<?php

namespace Goopil\OtlpMetrics\Support\Metrics;

use Goopil\OtlpMetrics\Enums\Protocol;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;

class MetricsFactory
{
    /**
     * @var array{0: MeterProviderInterface, 1: ExportingReader, 2: MeterInterface}|null
     */
    protected ?array $cache = null;

    /**
     * Create a MeterProvider and its associated Reader
     *
     * @return array{0: MeterProviderInterface, 1: ExportingReader, 2: MeterInterface}
     */
    public function createMeterProvider(string $endpoint, Protocol $protocol, array $headers, array $attributes, ?int $timeout = null): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $resource = ResourceInfoFactory::emptyResource()
            ->merge(ResourceInfo::create(Attributes::create($attributes)));

        $transport = $protocol->createTransport($endpoint, $headers, $timeout);
        $exporter = new MetricExporter($transport);
        $reader = new ExportingReader($exporter);

        $meterProvider = MeterProvider::builder()
            ->setResource($resource)
            ->addReader($reader)
            ->build();

        $meter = $meterProvider->getMeter('laravel-otlp-metrics');

        return $this->cache = [$meterProvider, $reader, $meter];
    }
}
