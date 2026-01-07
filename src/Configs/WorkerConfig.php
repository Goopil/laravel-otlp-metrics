<?php

namespace Goopil\OtlpMetrics\Configs;

use Goopil\OtlpMetrics\Contracts\MetricsContextConfigInterface;
use Goopil\OtlpMetrics\Contracts\ValidatableConfigInterface;
use Goopil\OtlpMetrics\Support\Traits\HasConfigValidation;

class WorkerConfig implements MetricsContextConfigInterface, ValidatableConfigInterface
{
    use HasConfigValidation;

    public function __construct(
        public readonly bool $enabled,
        public readonly string $endpoint,
        public readonly string $protocol,
        public readonly array $headers = [],
        public readonly ?string $logChannel = null,
        public readonly bool $trackJobs = true,
        public readonly bool $horizonEnabled = true,
        public readonly int $horizonCollectionInterval = 15,
        public readonly WorkerFeaturesConfig $features = new WorkerFeaturesConfig()
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
            trackJobs: $config['track_jobs'] ?? true,
            horizonEnabled: $config['horizon']['enabled'] ?? true,
            horizonCollectionInterval: $config['horizon']['collection_interval'] ?? 15,
            features: WorkerFeaturesConfig::fromArray($config['features'] ?? [])
        );
    }
}
