<?php

namespace Goopil\OtlpMetrics\Trackers\CronJob\Storage;

class CronJobStoredTask
{
    public readonly string $startTime;

    public function __construct(
        public readonly string $command,
    ) {
        $this->startTime = microtime(true);
    }

    public function duration()
    {
        return (microtime(true) - $this->startTime) * 1000;
    }
}
