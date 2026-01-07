<?php

namespace Goopil\OtlpMetrics\Configs;

class WorkerFeaturesConfig
{
    public function __construct(
        public readonly bool $trackJobGlobalStateCount = true,
        public readonly bool $trackJobGlobalTiming = true,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            trackJobGlobalStateCount: $config['track_job_global_state_count'] ?? true,
            trackJobGlobalTiming: $config['track_job_global_timing'] ?? true,
        );
    }
}
