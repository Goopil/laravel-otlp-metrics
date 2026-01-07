<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Facades\OtlpContext;
use Goopil\OtlpMetrics\Tests\TestCase;

class ContextDetectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset environment and globals
        putenv('CRON');
        putenv('SCHEDULED_TASK');
        putenv('QUEUE_WORKER');
        putenv('WORKER');
        $_SERVER['argv'] = ['artisan'];
    }

    public function test_detects_api_by_default(): void
    {
        $this->assertEquals('api', OtlpContext::detect(true));
        $this->assertTrue(OtlpContext::isApi());
    }

    public function test_detects_cronjob_via_env(): void
    {
        putenv('CRON=1');
        $this->assertEquals('cronjob', OtlpContext::detect(true));
        $this->assertTrue(OtlpContext::isCronJob());
        putenv('CRON');

        putenv('SCHEDULED_TASK=1');
        $this->assertEquals('cronjob', OtlpContext::detect(true));
        putenv('SCHEDULED_TASK');
    }

    public function test_detects_cronjob_via_argv(): void
    {
        $_SERVER['argv'] = ['artisan', 'schedule:run'];
        $this->assertEquals('cronjob', OtlpContext::detect(true));

        $_SERVER['argv'] = ['artisan', 'schedule:work'];
        $this->assertEquals('cronjob', OtlpContext::detect(true));
    }

    public function test_detects_worker_via_env(): void
    {
        putenv('QUEUE_WORKER=1');
        $this->assertEquals('worker', OtlpContext::detect(true));
        $this->assertTrue(OtlpContext::isWorker());
        putenv('QUEUE_WORKER');

        putenv('WORKER=1');
        $this->assertEquals('worker', OtlpContext::detect(true));
        putenv('WORKER');
    }

    public function test_detects_worker_via_argv(): void
    {
        $_SERVER['argv'] = ['artisan', 'queue:work'];
        $this->assertEquals('worker', OtlpContext::detect(true));

        $_SERVER['argv'] = ['artisan', 'queue:listen'];
        $this->assertEquals('worker', OtlpContext::detect(true));
    }

    public function test_detects_horizon_as_worker_via_argv(): void
    {
        $_SERVER['argv'] = ['artisan', 'horizon'];
        $this->assertEquals('worker', OtlpContext::detect(true));
        $this->assertTrue(OtlpContext::isHorizon());
        $this->assertTrue(OtlpContext::isWorker());

        $_SERVER['argv'] = ['artisan', 'horizon:work'];
        $this->assertEquals('worker', OtlpContext::detect(true));
        $this->assertTrue(OtlpContext::isHorizon());
        $this->assertTrue(OtlpContext::isWorker());
    }

    public function test_force_context_works(): void
    {
        config(['otlp-metrics.cronjob.force_context' => 'cronjob']);
        $this->refreshServices();

        $this->assertEquals('cronjob', OtlpContext::detect(true));
    }

    public function test_auto_detect_can_be_disabled(): void
    {
        config(['otlp-metrics.cronjob.auto_detect_context' => false]);
        $this->refreshServices();

        putenv('CRON=1');
        // Should still be API because auto_detect is false and no force_context
        $this->assertEquals('api', OtlpContext::detect(true));
    }

    protected function refreshServices(): void
    {
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Services\ContextService::class);
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);
    }
}
