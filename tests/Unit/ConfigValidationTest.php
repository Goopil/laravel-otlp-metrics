<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Configs\ApiConfig;
use Goopil\OtlpMetrics\Configs\CronJobConfig;
use Goopil\OtlpMetrics\Configs\WorkerConfig;
use Goopil\OtlpMetrics\Exceptions\ConfigurationException;
use Goopil\OtlpMetrics\Tests\TestCase;

class ConfigValidationTest extends TestCase
{
    public function test_api_config_validates_protocol(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Unsupported protocol: invalid');

        new ApiConfig(
            enabled: true,
            endpoint: 'http://localhost',
            protocol: 'invalid'
        );
    }

    public function test_api_config_validates_empty_endpoint(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Metrics API push endpoint cannot be empty');

        new ApiConfig(
            enabled: true,
            endpoint: '',
            protocol: 'http/protobuf'
        );
    }

    public function test_api_config_validates_url_format(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Metrics API push endpoint must be a valid URL');

        new ApiConfig(
            enabled: true,
            endpoint: 'not-a-url',
            protocol: 'http/protobuf'
        );
    }

    public function test_worker_config_validates_protocol(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Unsupported protocol: invalid');

        new WorkerConfig(
            enabled: true,
            endpoint: 'http://localhost',
            protocol: 'invalid'
        );
    }

    public function test_worker_config_validates_url_format(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Metrics Worker push endpoint must be a valid URL');

        new WorkerConfig(
            enabled: true,
            endpoint: 'not-a-url',
            protocol: 'http/protobuf'
        );
    }

    public function test_cronjob_config_validates_protocol(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Unsupported protocol: invalid');

        new CronJobConfig(
            enabled: true,
            endpoint: 'http://localhost',
            protocol: 'invalid'
        );
    }

    public function test_cronjob_config_validates_url_format(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Metrics CronJob push endpoint must be a valid URL');

        new CronJobConfig(
            enabled: true,
            endpoint: 'not-a-url',
            protocol: 'http/protobuf'
        );
    }

    public function test_validation_skipped_if_disabled(): void
    {
        $config = new ApiConfig(
            enabled: false,
            endpoint: '',
            protocol: 'invalid'
        );

        $this->assertInstanceOf(ApiConfig::class, $config);
    }
}
