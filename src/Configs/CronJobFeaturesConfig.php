<?php

namespace Goopil\OtlpMetrics\Configs;

class CronJobFeaturesConfig
{
    public function __construct(
        public readonly bool $trackScheduledTaskGlobalStateCount = true,
        public readonly bool $trackScheduledTaskGlobalTiming = true,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            trackScheduledTaskGlobalStateCount: $config['track_scheduled_task_global_state_count'] ?? true,
            trackScheduledTaskGlobalTiming: $config['track_scheduled_task_global_timing'] ?? true,
        );
    }
}
