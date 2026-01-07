<?php

namespace Goopil\OtlpMetrics\Configs;

use Goopil\OtlpMetrics\Contracts\MetricsContextConfigInterface;
use Goopil\OtlpMetrics\Contracts\ValidatableConfigInterface;
use Goopil\OtlpMetrics\Support\Traits\HasConfigValidation;

class CronJobConfig implements MetricsContextConfigInterface, ValidatableConfigInterface
{
    use HasConfigValidation;

    public function __construct(
        public readonly bool $enabled,
        public readonly string $endpoint,
        public readonly string $protocol,
        public readonly array $headers = [],
        public readonly ?string $logChannel = null,
        public readonly bool $trackScheduledTasks = true,
        public readonly bool $autoDetectContext = true,
        public readonly ?string $forceContext = null,
        public readonly CronJobFeaturesConfig $features = new CronJobFeaturesConfig()
    ) {
        if ($this->enabled) {
            $this->validate();
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public static function fromArray(array $config): static
    {
        return new static(
            enabled: $config['enabled'] ?? false,
            endpoint: $config['endpoint'] ?? '',
            protocol: $config['protocol'] ?? '',
            headers: $config['headers'] ?? [],
            logChannel: $config['log_channel'] ?? null,
            trackScheduledTasks: $config['track_scheduled_tasks'] ?? true,
            autoDetectContext: $config['auto_detect_context'] ?? true,
            forceContext: $config['force_context'] ?? null,
            features: CronJobFeaturesConfig::fromArray($config['features'] ?? [])
        );
    }
}
