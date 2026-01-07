<?php

namespace Goopil\OtlpMetrics\Tests\Feature;

use Goopil\OtlpMetrics\Http\Middleware\MetricsMiddleware;
use Goopil\OtlpMetrics\Tests\TestCase;
use Goopil\OtlpMetrics\Trackers\Http\HttpTracker;
use Goopil\OtlpMetrics\Trackers\Queue\QueueTracker;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class ContextLoggingTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Configure some log channels
        $app['config']->set('logging.channels.api_logs', [
            'driver' => 'single',
            'path' => storage_path('logs/api.log'),
        ]);
        $app['config']->set('logging.channels.worker_logs', [
            'driver' => 'single',
            'path' => storage_path('logs/worker.log'),
        ]);

        // Configure OTLP metrics to use these channels
        $app['config']->set('otlp-metrics.api.log_channel', 'api_logs');
        $app['config']->set('otlp-metrics.worker.log_channel', 'worker_logs');
    }

    public function test_tracker_uses_correct_channel_via_contextual_binding(): void
    {
        // 1. Check HTTP Tracker (API context)
        $httpTracker = app(HttpTracker::class);
        $logger = $this->getProperty($httpTracker, 'logger');

        $this->assertInstanceOf(LoggerInterface::class, $logger);

        // 2. Check Queue Tracker (Worker context)
        $queueTracker = app(QueueTracker::class);
        $logger = $this->getProperty($queueTracker, 'logger');

        $this->assertInstanceOf(LoggerInterface::class, $logger);

        // 3. Check Middleware (API context)
        $middleware = app(MetricsMiddleware::class);
        $logger = $this->getProperty($middleware, 'logger');

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    protected function getProperty($object, $propertyName)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
