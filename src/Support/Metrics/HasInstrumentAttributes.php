<?php

namespace Goopil\OtlpMetrics\Support\Metrics;

trait HasInstrumentAttributes
{
    protected ?AttributeService $attributeManager = null;

    protected array $defaultTags = [];

    protected function resolveAttributes(iterable $attributes): array
    {
        return $this->attributeManager->mergeAttributes($this->defaultTags, (array) $attributes);
    }

    public function isEnabled(): bool
    {
        return $this->instrument->isEnabled();
    }
}
