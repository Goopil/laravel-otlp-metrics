<?php

namespace Goopil\OtlpMetrics\Support\Metrics;

class InstrumentRegistry implements \Countable
{
    /**
     * @var array<string, mixed>
     */
    protected array $items = [];

    public function __construct(array $items = [], protected int $limit = 1000)
    {
        $this->items = $items;
    }

    public function get($key, $default = null)
    {
        if (! isset($this->items[$key])) {
            return $default instanceof \Closure ? $default() : $default;
        }

        $value = $this->items[$key];
        unset($this->items[$key]);
        $this->items[$key] = $value;

        return $value;
    }

    public function put($key, $value): static
    {
        if (isset($this->items[$key])) {
            unset($this->items[$key]);
        } elseif (count($this->items) >= $this->limit) {
            $firstKey = array_key_first($this->items);
            if ($firstKey !== null) {
                unset($this->items[$firstKey]);
            }
        }

        $this->items[$key] = $value;

        return $this;
    }

    public function has($key): bool
    {
        return isset($this->items[$key]);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
