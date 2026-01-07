<?php

namespace Goopil\OtlpMetrics\Support\Metrics;

use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\Context\ContextInterface;

class CachedGauge implements GaugeInterface
{
    use HasInstrumentAttributes;

    public function __construct(
        protected GaugeInterface $instrument,
        AttributeService $attributeManager,
        array $defaultTags = []
    ) {
        $this->attributeManager = $attributeManager;
        $this->defaultTags = $defaultTags;
    }

    public function record(float|int $amount, iterable $attributes = [], ContextInterface|false|null $context = null): void
    {
        $this->instrument->record($amount, $this->resolveAttributes($attributes), $context);
    }
}
