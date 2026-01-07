<?php

namespace Goopil\OtlpMetrics\Support\Metrics;

use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\Context\ContextInterface;

class CachedCounter implements CounterInterface
{
    use HasInstrumentAttributes;

    public function __construct(
        protected CounterInterface $instrument,
        AttributeService $attributeManager,
        array $defaultTags = []
    ) {
        $this->attributeManager = $attributeManager;
        $this->defaultTags = $defaultTags;
    }

    public function add(float|int $amount, iterable $attributes = [], ContextInterface|false|null $context = null): void
    {
        $this->instrument->add($amount, $this->resolveAttributes($attributes), $context);
    }
}
