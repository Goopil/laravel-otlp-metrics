<?php

namespace Goopil\OtlpMetrics\Services;

use Goopil\OtlpMetrics\Configs\CommonConfig;
use Goopil\OtlpMetrics\Contracts\MetricsContextConfigInterface;
use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Enums\Protocol;
use Goopil\OtlpMetrics\Exceptions\MetricsExportException;
use Goopil\OtlpMetrics\Exceptions\MetricsInitializationException;
use Goopil\OtlpMetrics\Support\Metrics\AttributeService;
use Goopil\OtlpMetrics\Support\Metrics\InstrumentManager;
use Goopil\OtlpMetrics\Support\Metrics\InstrumentRegistry;
use Goopil\OtlpMetrics\Support\Metrics\MetricsFactory;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use OpenTelemetry\API\Metrics\ObservableCounterInterface;
use OpenTelemetry\API\Metrics\ObservableGaugeInterface;
use OpenTelemetry\SDK\Metrics\MetricReaderInterface;
use Psr\Log\LoggerInterface;

class MetricsService implements MetricsServiceInterface
{
    protected ?MeterProviderInterface $meterProvider = null;

    protected ?MeterInterface $meter = null;

    protected ?MetricReaderInterface $reader = null;

    protected bool $initialized = false;

    protected int $lastExportAt = 0;

    protected Protocol $protocol;

    protected ?InstrumentManager $instruments = null;

    public function __construct(
        protected MetricsFactory $factory,
        protected AttributeService $attributeManager,
        protected InstrumentRegistry $instrumentCache,
        protected MetricsContextConfigInterface $config,
        protected CommonConfig $commonConfig,
        protected LoggerInterface $logger,
        protected array $attributes = []
    ) {
        $this->protocol = Protocol::fromString($this->config->getProtocol());
    }

    /**
     * Lazy initialization
     */
    protected function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initializeMeterProvider();
    }

    protected function initializeMeterProvider(): void
    {
        try {
            [$this->meterProvider, $this->reader, $this->meter] = $this->factory->createMeterProvider(
                $this->config->getEndpoint(),
                $this->protocol,
                $this->config->getHeaders(),
                $this->attributes,
                $this->commonConfig->timeout
            );

            $this->instruments = new InstrumentManager(
                $this->meter,
                $this->attributeManager,
                $this->instrumentCache
            );
        } catch (\Throwable $e) {
            $initializationException = new MetricsInitializationException(
                'OTLP Metrics Initialization failed: '.$e->getMessage(),
                0,
                $e
            );

            if (! $this->commonConfig->suppressExceptions) {
                throw $initializationException;
            }

            $this->logger->error($initializationException->getMessage(), [
                'exception' => $initializationException,
            ]);

            // Fallback to null-like behavior for instruments if initialization failed
            // We don't want to crash the whole app if metrics fail to initialize
        }

        $this->initialized = true;
    }

    public function counter(string $name, ?string $description = null, ?string $unit = null, array $tags = []): CounterInterface
    {
        $this->ensureInitialized();

        if ($this->instruments === null) {
            return new \OpenTelemetry\API\Metrics\Noop\NoopCounter();
        }

        return $this->instruments->counter($name, $description, $unit, $tags);
    }

    /**
     * Create an observable counter metric
     */
    public function observableCounter(string $name, callable $callback, ?string $description = null, ?string $unit = null, array $tags = []): ObservableCounterInterface
    {
        $this->ensureInitialized();

        if ($this->instruments === null) {
            return new \OpenTelemetry\API\Metrics\Noop\NoopObservableCounter();
        }

        return $this->instruments->observableCounter($name, $callback, $description, $unit, $tags);
    }

    public function gauge(string $name, ?string $description = null, ?string $unit = null, array $tags = []): GaugeInterface
    {
        $this->ensureInitialized();

        if ($this->instruments === null) {
            return new \OpenTelemetry\API\Metrics\Noop\NoopGauge();
        }

        return $this->instruments->gauge($name, $description, $unit, $tags);
    }

    /**
     * Create an observable gauge metric
     */
    public function observableGauge(string $name, callable $callback, ?string $description = null, ?string $unit = null, array $tags = []): ObservableGaugeInterface
    {
        $this->ensureInitialized();

        if ($this->instruments === null) {
            return new \OpenTelemetry\API\Metrics\Noop\NoopObservableGauge();
        }

        return $this->instruments->observableGauge($name, $callback, $description, $unit, $tags);
    }

    public function histogram(string $name, ?string $description = null, ?string $unit = null, array $tags = []): HistogramInterface
    {
        $this->ensureInitialized();

        if ($this->instruments === null) {
            return new \OpenTelemetry\API\Metrics\Noop\NoopHistogram();
        }

        return $this->instruments->histogram($name, $description, $unit, $tags);
    }

    /**
     * Force export of metrics
     */
    public function export(): void
    {
        if ($this->reader === null) {
            return;
        }

        try {
            $this->reader->collect();
        } catch (\Throwable $e) {
            $exportException = new MetricsExportException(
                'OTLP Metrics Export failed: '.$e->getMessage(),
                0,
                $e
            );

            if (! $this->commonConfig->suppressExceptions) {
                throw $exportException;
            }

            $this->logger->error($exportException->getMessage(), [
                'exception' => $exportException,
            ]);
        }

        $this->lastExportAt = time();
    }

    /**
     * Export metrics if the provided interval has passed since last export
     */
    public function exportIfReady(int $interval): void
    {
        if (time() - $this->lastExportAt >= $interval) {
            $this->export();
        }
    }

    /**
     * Shutdown the meter provider
     */
    public function shutdown(): void
    {
        if ($this->meterProvider !== null) {
            $this->meterProvider->shutdown();
        }
    }

    /**
     * Get the meter instance
     */
    public function getMeter(): MeterInterface
    {
        $this->ensureInitialized();

        return $this->meter;
    }

    /**
     * Get the attribute manager instance
     */
    public function getAttributeManager(): AttributeService
    {
        return $this->attributeManager;
    }

    /**
     * Get the instrument cache instance
     */
    protected function getInstrumentCache(): InstrumentRegistry
    {
        return $this->instrumentCache;
    }
}
