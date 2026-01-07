<?php

namespace Goopil\OtlpMetrics\Support\Metrics;

use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\ObservableCounterInterface;
use OpenTelemetry\API\Metrics\ObservableGaugeInterface;

class InstrumentManager
{
    /**
     * Local cache for instrument wrappers to avoid re-creating them within the same request.
     * This is important because wrappers hold a reference to the request-scoped AttributeService.
     */
    protected array $wrappers = [];

    /**
     * Cache for observable instruments to avoid multiple registrations for the same instrument
     * during the same request/lifecycle.
     */
    protected array $observableInstruments = [];

    public function __construct(
        protected MeterInterface $meter,
        protected AttributeService $attributeManager,
        protected InstrumentRegistry $cache
    ) {}

    public function counter(string $name, ?string $description = null, ?string $unit = null, array $tags = []): CounterInterface
    {
        $cacheKey = $this->getCacheKey('counter', $name, $tags);

        if (isset($this->wrappers[$cacheKey])) {
            return $this->wrappers[$cacheKey];
        }

        $otelInstrument = $this->cache->get($cacheKey);

        if ($otelInstrument === null) {
            $otelInstrument = $this->meter->createCounter($name, $unit, $description);
            $this->cache->put($cacheKey, $otelInstrument);
        }

        return $this->wrappers[$cacheKey] = new CachedCounter(
            $otelInstrument,
            $this->attributeManager,
            $tags
        );
    }

    public function observableCounter(string $name, callable $callback, ?string $description = null, ?string $unit = null, array $tags = []): ObservableCounterInterface
    {
        $cacheKey = $this->getCacheKey('observable_counter', $name, $tags);

        if (isset($this->observableInstruments[$cacheKey])) {
            return $this->observableInstruments[$cacheKey];
        }

        return $this->observableInstruments[$cacheKey] = $this->meter->createObservableCounter($name, $unit, $description, $tags, $callback);
    }

    public function gauge(string $name, ?string $description = null, ?string $unit = null, array $tags = []): GaugeInterface
    {
        $cacheKey = $this->getCacheKey('gauge', $name, $tags);

        if (isset($this->wrappers[$cacheKey])) {
            return $this->wrappers[$cacheKey];
        }

        $otelInstrument = $this->cache->get($cacheKey);

        if ($otelInstrument === null) {
            $otelInstrument = $this->meter->createGauge($name, $unit, $description);
            $this->cache->put($cacheKey, $otelInstrument);
        }

        return $this->wrappers[$cacheKey] = new CachedGauge(
            $otelInstrument,
            $this->attributeManager,
            $tags
        );
    }

    public function observableGauge(string $name, callable $callback, ?string $description = null, ?string $unit = null, array $tags = []): ObservableGaugeInterface
    {
        $cacheKey = $this->getCacheKey('observable_gauge', $name, $tags);

        if (isset($this->observableInstruments[$cacheKey])) {
            return $this->observableInstruments[$cacheKey];
        }

        return $this->observableInstruments[$cacheKey] = $this->meter->createObservableGauge($name, $unit, $description, $tags, $callback);
    }

    public function histogram(string $name, ?string $description = null, ?string $unit = null, array $tags = []): HistogramInterface
    {
        $cacheKey = $this->getCacheKey('histogram', $name, $tags);

        if (isset($this->wrappers[$cacheKey])) {
            return $this->wrappers[$cacheKey];
        }

        $otelInstrument = $this->cache->get($cacheKey);

        if ($otelInstrument === null) {
            $otelInstrument = $this->meter->createHistogram($name, $unit, $description);
            $this->cache->put($cacheKey, $otelInstrument);
        }

        return $this->wrappers[$cacheKey] = new CachedHistogram(
            $otelInstrument,
            $this->attributeManager,
            $tags
        );
    }

    protected function getCacheKey(string $type, string $name, array $tags): string
    {
        if (empty($tags)) {
            return "{$type}.{$name}";
        }

        ksort($tags);

        return "{$type}.{$name}.".hash('xxh3', http_build_query($tags));
    }
}
