<?php

namespace Goopil\OtlpMetrics\Configs;

class ContextConfig
{
    public function __construct(
        public readonly CommonConfig $common,
        public readonly ApiConfig $api,
        public readonly WorkerConfig $worker,
        public readonly CronJobConfig $cronjob
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            common: CommonConfig::fromArray($config['common'] ?? [], $config['attributes'] ?? []),
            api: ApiConfig::fromArray($config['api'] ?? []),
            worker: WorkerConfig::fromArray($config['worker'] ?? []),
            cronjob: CronJobConfig::fromArray($config['cronjob'] ?? [])
        );
    }
}
