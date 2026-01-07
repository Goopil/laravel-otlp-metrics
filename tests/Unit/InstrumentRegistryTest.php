<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Support\Metrics\InstrumentRegistry;
use Goopil\OtlpMetrics\Tests\TestCase;

class InstrumentRegistryTest extends TestCase
{
    public function test_it_can_be_instantiated_with_a_limit(): void
    {
        $registry = new InstrumentRegistry([], 500);
        $this->assertEquals(500, $registry->getLimit());
    }

    public function test_it_evicts_lru_item_when_limit_is_exceeded(): void
    {
        $registry = new InstrumentRegistry([], 3);

        $registry->put('key1', 'val1');
        $registry->put('key2', 'val2');
        $registry->put('key3', 'val3');

        $this->assertEquals(3, $registry->count());

        // Access key1 to make it most recently used
        $registry->get('key1');

        // Adding a 4th key should trigger eviction of key2 (least recently used)
        $registry->put('key4', 'val4');

        $this->assertEquals(3, $registry->count());
        $this->assertTrue($registry->has('key1'));
        $this->assertFalse($registry->has('key2')); // Key2 was the oldest/LRU
        $this->assertTrue($registry->has('key3'));
        $this->assertTrue($registry->has('key4'));
    }

    public function test_it_moves_item_to_end_on_update(): void
    {
        $registry = new InstrumentRegistry([], 3);

        $registry->put('key1', 'val1');
        $registry->put('key2', 'val2');
        $registry->put('key3', 'val3');

        // Update key1, it should become the most recently used
        $registry->put('key1', 'new_val1');

        // Adding key4 should now evict key2
        $registry->put('key4', 'val4');

        $this->assertFalse($registry->has('key2'));
        $this->assertTrue($registry->has('key1'));
    }

    public function test_it_does_not_clear_when_updating_existing_key(): void
    {
        $registry = new InstrumentRegistry([], 3);

        $registry->put('key1', 'val1');
        $registry->put('key2', 'val2');
        $registry->put('key3', 'val3');

        $this->assertEquals(3, $registry->count());

        // Updating key3 should not trigger a clear even if count is at limit
        $registry->put('key3', 'new_val3');

        $this->assertEquals(3, $registry->count());
        $this->assertEquals('new_val3', $registry->get('key3'));
    }
}
