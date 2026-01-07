<?php

namespace Goopil\OtlpMetrics\Support\Metrics;

class AttributeService
{
    protected array $attributes = [];

    /**
     * Add an attribute that will be applied to all metrics in the current scope
     */
    public function addAttribute(string $key, string $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Add multiple attributes at once
     */
    public function addAttributes(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * Remove an attribute
     */
    public function removeAttribute(string $key): self
    {
        unset($this->attributes[$key]);

        return $this;
    }

    /**
     * Clear all attributes
     */
    public function clear(): self
    {
        $this->attributes = [];

        return $this;
    }

    /**
     * Get all attributes for the current scope
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Merge current scope attributes with provided attribute sets
     * This is the main method to use when recording metrics
     */
    public function mergeAttributes(array ...$attributeSets): array
    {
        if (empty($this->attributes)) {
            $hasMultiple = false;
            $singleSet = [];

            foreach ($attributeSets as $set) {
                if (! empty($set)) {
                    if ($singleSet !== []) {
                        $hasMultiple = true;
                        break;
                    }
                    $singleSet = $set;
                }
            }

            if (! $hasMultiple) {
                return $singleSet;
            }
        }

        // Filter out empty sets to avoid unnecessary array_merge
        $filteredSets = [];
        foreach ($attributeSets as $set) {
            if (! empty($set)) {
                $filteredSets[] = $set;
            }
        }

        if (empty($this->attributes)) {
            if (empty($filteredSets)) {
                return [];
            }
            if (count($filteredSets) === 1) {
                return $filteredSets[0];
            }
        } elseif (empty($filteredSets)) {
            return $this->attributes;
        }

        return array_merge(
            $this->attributes,
            ...$filteredSets
        );
    }

    /**
     * Alias for getAttributes to maintain some compatibility if needed,
     * but we should prefer getAttributes()
     */
    public function getAllAttributes(): array
    {
        return $this->getAttributes();
    }
}
