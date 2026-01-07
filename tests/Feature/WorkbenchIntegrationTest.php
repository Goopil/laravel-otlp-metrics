<?php

namespace Goopil\OtlpMetrics\Tests\Feature;

use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Services\MetricsService;
use Goopil\OtlpMetrics\Tests\TestCase;
use Goopil\OtlpMetrics\Trackers\CronJob\CronJobTracker;
use Goopil\OtlpMetrics\Trackers\Http\HttpTracker;
use Goopil\OtlpMetrics\Trackers\Queue\QueueTracker;
use Mockery;
use Orchestra\Testbench\Concerns\WithWorkbench;

class WorkbenchIntegrationTest extends TestCase
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function mockMetricsService()
    {
        $counter = Mockery::mock(\OpenTelemetry\API\Metrics\CounterInterface::class);
        $counter->allows('add');

        $histogram = Mockery::mock(\OpenTelemetry\API\Metrics\HistogramInterface::class);
        $histogram->allows('record');

        $metricsService = Mockery::spy(MetricsService::class);
        $metricsService->allows('counter')->andReturns($counter);
        $metricsService->allows('histogram')->andReturns($histogram);
        $metricsService->allows('isEnabled')->andReturns(true);

        $this->app->instance(MetricsServiceInterface::class, $metricsService);
        $this->app->instance(MetricsService::class, $metricsService);

        return $metricsService;
    }

    public function test_http_request_records_metrics(): void
    {
        $metricsService = $this->mockMetricsService();

        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)
            ->prependMiddleware(\Goopil\OtlpMetrics\Http\Middleware\MetricsMiddleware::class);

        $this->app->forgetInstance(HttpTracker::class);
        $this->app->make(HttpTracker::class);

        $this->get('/test/fast')->assertStatus(200);

        $metricsService->shouldHaveReceived('counter')->atLeast()->once();
        $metricsService->shouldHaveReceived('histogram')->atLeast()->once();
    }

    public function test_http_request_fail_records_metrics(): void
    {
        $metricsService = $this->mockMetricsService();

        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)
            ->prependMiddleware(\Goopil\OtlpMetrics\Http\Middleware\MetricsMiddleware::class);

        $this->app->forgetInstance(HttpTracker::class);
        $this->app->make(HttpTracker::class);

        $this->get('/test/fail')->assertStatus(500);

        $metricsService->shouldHaveReceived('counter')->atLeast()->once();
    }

    public function test_queue_job_records_metrics(): void
    {
        $metricsService = $this->mockMetricsService();

        $this->app->forgetInstance(\Goopil\OtlpMetrics\Trackers\Queue\Storage\QueueStorage::class);
        $this->app->forgetInstance(QueueTracker::class);
        $this->app->make(QueueTracker::class)->register();

        dispatch(new \Workbench\App\Jobs\FastJob());

        $metricsService->shouldHaveReceived('counter')->atLeast()->once();
        $metricsService->shouldHaveReceived('histogram')->atLeast()->once();
    }

    public function test_queue_job_fail_records_metrics(): void
    {
        $metricsService = $this->mockMetricsService();

        $this->app->forgetInstance(\Goopil\OtlpMetrics\Trackers\Queue\Storage\QueueStorage::class);
        $this->app->forgetInstance(QueueTracker::class);
        $this->app->make(QueueTracker::class)->register();

        try {
            dispatch(new \Workbench\App\Jobs\FailJob());
        } catch (\Throwable $e) {
            // Expected
        }

        $metricsService->shouldHaveReceived('counter')->atLeast()->once();
    }

    public function test_scheduled_task_records_metrics(): void
    {
        $metricsService = $this->mockMetricsService();

        $this->app->forgetInstance(\Goopil\OtlpMetrics\Trackers\CronJob\Storage\CronjobStorage::class);
        $this->app->forgetInstance(CronJobTracker::class);
        $this->app->make(CronJobTracker::class)->register();

        $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
        $schedule->command('test:fast')->everyMinute();

        $this->artisan('schedule:run');

        $metricsService->shouldHaveReceived('counter')->atLeast()->once();
        $metricsService->shouldHaveReceived('histogram')->atLeast()->once();
    }
}
