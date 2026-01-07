<?php

namespace Goopil\OtlpMetrics\Support\Traits;

use Goopil\OtlpMetrics\Enums\Protocol;
use Goopil\OtlpMetrics\Exceptions\ConfigurationException;
use Goopil\OtlpMetrics\Exceptions\ProtocolException;

trait HasConfigValidation
{
    /**
     * Validate the configuration
     *
     * @throws \Goopil\OtlpMetrics\Exceptions\ConfigurationException
     */
    public function validate(): void
    {
        $context = $this->getValidationContext();

        if (empty($this->endpoint)) {
            throw new ConfigurationException("Metrics {$context} push endpoint cannot be empty");
        }

        if (! filter_var($this->endpoint, FILTER_VALIDATE_URL) && ! str_starts_with($this->endpoint, 'http')) {
            throw new ConfigurationException("Metrics {$context} push endpoint must be a valid URL");
        }

        try {
            $protocol = Protocol::fromString($this->protocol);
            $protocol->validateRequirements();
        } catch (ProtocolException $e) {
            throw new ConfigurationException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the context name for validation messages
     */
    protected function getValidationContext(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        $context = str_replace('Config', '', $className);

        return $context === 'Api' ? 'API' : $context;
    }
}
