<?php

namespace Goopil\OtlpMetrics\Configs;

use Goopil\OtlpMetrics\Exceptions\ConfigurationException;
use OpenTelemetry\SemConv\ResourceAttributes;

class CommonConfig
{
    public const MANDATORY_ATTRIBUTES = [
        'ResourceAttributes::SERVICE_NAME' => ResourceAttributes::SERVICE_NAME,
        'ResourceAttributes::SERVICE_NAMESPACE' => ResourceAttributes::SERVICE_NAMESPACE,
        'ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME' => ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME,
    ];

    public function __construct(
        public readonly int $batchExportInterval = 60,
        public readonly int $instrumentCacheLimit = 1000,
        public readonly int $timeout = 10,
        public readonly bool $suppressExceptions = true,
        public readonly array $attributes = []
    ) {}

    public static function fromArray(array $config, array $attributes = []): self
    {
        foreach (static::MANDATORY_ATTRIBUTES as $name => $attribute) {
            if (! isset($attributes[$attribute])) {
                throw new ConfigurationException("The OTLP resource attribute {$name} is mandatory.");
            }
        }

        if (isset($config['timeout']) && $config['timeout'] < 0) {
            throw new ConfigurationException('The OTLP timeout must be a positive integer.');
        }

        return new self(
            batchExportInterval: $config['batch_export_interval'] ?? 60,
            instrumentCacheLimit: $config['instrument_cache_limit'] ?? 1000,
            timeout: $config['timeout'] ?? 10,
            suppressExceptions: $config['suppress_exceptions'] ?? true,
            attributes: $attributes
        );
    }
}
