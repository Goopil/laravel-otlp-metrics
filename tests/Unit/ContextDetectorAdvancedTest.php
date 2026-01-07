<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Configs\ApiConfig;
use Goopil\OtlpMetrics\Configs\ContextConfig;
use Goopil\OtlpMetrics\Configs\CronJobConfig;
use Goopil\OtlpMetrics\Configs\WorkerConfig;
use Goopil\OtlpMetrics\Services\ContextService;
use Goopil\OtlpMetrics\Tests\TestCase;
use OpenTelemetry\SemConv\ResourceAttributes;

class ContextDetectorAdvancedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset environment and globals to ensure clean detection
        putenv('CRON');
        putenv('SCHEDULED_TASK');
        putenv('QUEUE_WORKER');
        putenv('WORKER');
        $_SERVER['argv'] = ['artisan'];
    }

    protected function getAttributes(): array
    {
        return [
            ResourceAttributes::SERVICE_NAME => 'test-service',
            ResourceAttributes::SERVICE_NAMESPACE => 'test-namespace',
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME => 'testing',
        ];
    }

    public function test_is_enabled_returns_correct_value(): void
    {
        $config = ContextConfig::fromArray([
            'attributes' => $this->getAttributes(),
            'api' => [
                'enabled' => true,
                'endpoint' => 'http://localhost',
                'protocol' => 'http/protobuf',
            ],
            'worker' => ['enabled' => false],
            'cronjob' => [
                'enabled' => true,
                'endpoint' => 'http://localhost',
                'protocol' => 'http/protobuf',
            ],
        ]);

        $detector = new ContextService($config, $this->app->runningInConsole());

        // API context
        $this->assertTrue($detector->isContextEnabled('api'));
        $this->assertFalse($detector->isContextEnabled('worker'));
        $this->assertTrue($detector->isContextEnabled('cronjob'));
        $this->assertFalse($detector->isContextEnabled('unknown'));
    }

    public function test_get_active_context_config_returns_correct_object(): void
    {
        $config = ContextConfig::fromArray([
            'attributes' => $this->getAttributes(),
            'api' => [
                'enabled' => true,
                'endpoint' => 'http://api',
                'protocol' => 'http/protobuf',
            ],
            'worker' => [
                'enabled' => true,
                'endpoint' => 'http://worker',
                'protocol' => 'http/protobuf',
            ],
            'cronjob' => [
                'enabled' => true,
                'endpoint' => 'http://cron',
                'protocol' => 'http/protobuf',
            ],
        ]);

        $detector = new ContextService($config, $this->app->runningInConsole());

        // Force API
        $this->assertInstanceOf(ApiConfig::class, $detector->getActiveContextConfig());
        $this->assertEquals('http://api', $detector->getActiveContextConfig()->endpoint);

        // Mock worker detection
        putenv('QUEUE_WORKER=1');
        $detector->detect(true);
        $this->assertInstanceOf(WorkerConfig::class, $detector->getActiveContextConfig());
        $this->assertEquals('http://worker', $detector->getActiveContextConfig()->endpoint);
        putenv('QUEUE_WORKER');

        // Mock cronjob detection
        putenv('CRON=1');
        $detector->detect(true);
        $this->assertInstanceOf(CronJobConfig::class, $detector->getActiveContextConfig());
        $this->assertEquals('http://cron', $detector->getActiveContextConfig()->endpoint);
        putenv('CRON');
    }

    public function test_is_enabled_uses_detected_context(): void
    {
        $config = ContextConfig::fromArray([
            'attributes' => $this->getAttributes(),
            'api' => [
                'enabled' => true,
                'endpoint' => 'http://localhost',
                'protocol' => 'http/protobuf',
            ],
            'worker' => ['enabled' => false],
        ]);

        $detector = new ContextService($config, $this->app->runningInConsole());

        // API
        $this->assertTrue($detector->isEnabled());

        // Worker
        putenv('QUEUE_WORKER=1');
        $detector->detect(true);
        $this->assertFalse($detector->isEnabled());
        putenv('QUEUE_WORKER');
    }
}
