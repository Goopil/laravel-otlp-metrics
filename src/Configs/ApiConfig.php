<?php

namespace Goopil\OtlpMetrics\Configs;

use Goopil\OtlpMetrics\Contracts\MetricsContextConfigInterface;
use Goopil\OtlpMetrics\Contracts\ValidatableConfigInterface;
use Goopil\OtlpMetrics\Support\Traits\HasConfigValidation;

class ApiConfig implements MetricsContextConfigInterface, ValidatableConfigInterface
{
    use HasConfigValidation;

    public function __construct(
        public readonly bool $enabled,
        public readonly string $endpoint,
        public readonly string $protocol,
        public readonly array $headers = [],
        public readonly ?string $logChannel = null,
        public readonly ApiFeaturesConfig $features = new ApiFeaturesConfig()
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
            features: ApiFeaturesConfig::fromArray($config['features'] ?? [])
        );
    }
}
