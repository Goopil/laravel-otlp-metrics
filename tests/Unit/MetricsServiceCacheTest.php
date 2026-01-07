<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Tests\TestCase;

class MetricsServiceCacheTest extends TestCase
{
    public function test_instruments_are_cached(): void
    {
        $service = app(MetricsServiceInterface::class);

        $counter1 = $service->counter('test_counter');
        $counter2 = $service->counter('test_counter');

        $this->assertSame($counter1, $counter2);

        $gauge1 = $service->gauge('test_gauge');
        $gauge2 = $service->gauge('test_gauge');

        $this->assertSame($gauge1, $gauge2);

        $histogram1 = $service->histogram('test_histogram');
        $histogram2 = $service->histogram('test_histogram');

        $this->assertSame($histogram1, $histogram2);
    }

    public function test_instruments_with_different_tags_have_different_cache_entries(): void
    {
        $service = app(MetricsServiceInterface::class);

        $counter1 = $service->counter('test_counter', tags: ['a' => '1']);
        $counter2 = $service->counter('test_counter', tags: ['a' => '2']);
        $counter3 = $service->counter('test_counter', tags: ['a' => '1']);

        $this->assertNotSame($counter1, $counter2);
        $this->assertSame($counter1, $counter3);
    }
}
