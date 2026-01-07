<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Configs\CommonConfig;
use Goopil\OtlpMetrics\Exceptions\ConfigurationException;
use Goopil\OtlpMetrics\Tests\TestCase;
use OpenTelemetry\SemConv\ResourceAttributes;

class ResourceAttributesTest extends TestCase
{
    public function test_common_config_requires_standard_attributes(): void
    {
        $config = CommonConfig::fromArray([], [
            ResourceAttributes::SERVICE_NAME => 'test-app',
            ResourceAttributes::SERVICE_NAMESPACE => 'test-ns',
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => 'staging',
        ]);

        $this->assertEquals('test-app', $config->attributes[ResourceAttributes::SERVICE_NAME]);
        $this->assertEquals('test-ns', $config->attributes[ResourceAttributes::SERVICE_NAMESPACE]);
        $this->assertEquals('staging', $config->attributes[ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME]);
    }

    public function test_common_config_throws_exception_if_service_name_missing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('The OTLP resource attribute ResourceAttributes::SERVICE_NAME is mandatory.');

        CommonConfig::fromArray([], [
            ResourceAttributes::SERVICE_NAMESPACE => 'test-ns',
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => 'staging',
        ]);
    }

    public function test_common_config_preserves_custom_attributes(): void
    {
        $config = CommonConfig::fromArray([], [
            ResourceAttributes::SERVICE_NAME => 'test-app',
            ResourceAttributes::SERVICE_NAMESPACE => 'test-ns',
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => 'staging',
            'custom.attr' => 'custom-value',
        ]);

        $this->assertEquals('custom-value', $config->attributes['custom.attr']);
        $this->assertEquals('test-app', $config->attributes[ResourceAttributes::SERVICE_NAME]);
    }
}
