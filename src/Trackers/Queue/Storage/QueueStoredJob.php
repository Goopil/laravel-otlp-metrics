<?php

namespace Goopil\OtlpMetrics\Trackers\Queue\Storage;

class QueueStoredJob
{
    public function __construct(
        public readonly float $startTime,
        public readonly string $name,
        public readonly string $queue,
    ) {}

    public function duration()
    {
        return (microtime(true) - $this->startTime) * 1000;
    }
}
