<?php

namespace Goopil\OtlpMetrics\Support\Metrics;

use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\Context\ContextInterface;

class CachedHistogram implements HistogramInterface
{
    use HasInstrumentAttributes;

    public function __construct(
        protected HistogramInterface $instrument,
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
